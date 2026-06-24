<?php

/**
 * Integration helper: sync Ragflow models into Moodle, create parent/child bots,
 * upload a document, and emit JSON for test_endpoints.py Ragflow verification.
 *
 * @package    local_cria
 * @copyright  2024 onwards York University (https://yorku.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

$moodleroot = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;
$configpath = $moodleroot . 'config.php';
if (!file_exists($configpath)) {
    $configpath = __DIR__ . '/../../../config.php';
}
if (!file_exists($configpath)) {
    fwrite(STDERR, "config.php not found. Run inside the Cria container.\n");
    exit(2);
}

require($configpath);
require_once($CFG->libdir . '/clilib.php');

use local_cria\api_response;
use local_cria\bot;
use local_cria\criabot;
use local_cria\intent;
use local_cria\ragflow;
use local_cria\sync_manager;

/**
 * @return array{llm:int,embedding:int,rerank:int}|null
 */
function local_cria_ragflow_e2e_pick_models(): ?array {
    global $DB;

    sync_manager::ensure_providers_for_remote_types();
    $sync = sync_manager::sync_models_from_criadex(false);
    if (empty($sync->success)) {
        return null;
    }

    $sql = "SELECT m.id, m.criadex_model_id, m.is_embedding, m.name, p.type AS provider_type
              FROM {local_cria_models} m
              JOIN {local_cria_providers} p ON p.id = m.provider_id
             WHERE m.criadex_model_id > 0";
    $rows = $DB->get_records_sql($sql);

    $llm = null;
    $embedding = null;
    $rerank = null;

    foreach ($rows as $row) {
        $ptype = strtolower((string) ($row->provider_type ?? ''));
        if ($ptype === 'ragflow' && empty($row->is_embedding) && $llm === null) {
            $llm = $row;
        }
        if (!empty($row->is_embedding) && $embedding === null) {
            $embedding = $row;
        }
        if ($ptype === 'cohere' && $rerank === null) {
            $rerank = $row;
        }
    }

    if ($llm === null || $embedding === null) {
        return null;
    }

    if ($rerank === null) {
        foreach ($rows as $row) {
            if (strtolower((string) ($row->provider_type ?? '')) === 'ragflow' && empty($row->is_embedding)) {
                $rerank = $row;
                break;
            }
        }
    }

    if ($rerank === null) {
        return null;
    }

    return [
        'llm' => (int) $llm->id,
        'embedding' => (int) $embedding->id,
        'rerank' => (int) $rerank->id,
    ];
}

/**
 * @return int
 */
function local_cria_ragflow_e2e_bot_type_id(): int {
    global $DB, $USER;

    $existing = $DB->get_record('local_cria_type', ['use_bot_server' => 1], 'id', IGNORE_MULTIPLE);
    if ($existing) {
        return (int) $existing->id;
    }

    $bottype = new \stdClass();
    $bottype->name = 'Ragflow E2E Bot Type';
    $bottype->description = 'Automated Ragflow integration bot type';
    $bottype->use_bot_server = 1;
    $bottype->system_message = 'You are a Ragflow integration test assistant. ';
    $bottype->usermodified = $USER->id ?? 2;
    $bottype->timecreated = time();
    $bottype->timemodified = time();
    return (int) $DB->insert_record('local_cria_type', $bottype);
}

/**
 * @param string[] $criabotnames
 * @return void
 */
function local_cria_ragflow_e2e_cleanup_ragflow(array $criabotnames): void {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        foreach ($criabotnames as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            ragflow::cleanup_bot_sync_targets($name);
        }
        ragflow::cleanup_numeric_bot_artifacts();
        if ($attempt < 4) {
            sleep(2);
        }
    }
}

/**
 * @param int $botid
 * @return void
 */
function local_cria_ragflow_e2e_delete_bot(int $botid): void {
    if ($botid <= 0) {
        return;
    }
    try {
        $cleanup = new bot($botid);
        $cleanup->delete_record();
    } catch (\Throwable $e) {
        debugging('Ragflow E2E bot delete failed for id=' . $botid . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

$longparams = [
    'help' => false,
    'keep' => false,
    'cleanup-parent' => 0,
    'cleanup-child' => 0,
    'cleanup-empty' => 0,
    'purge-ragflow-numeric' => false,
];
list($options) = cli_get_params($longparams, ['h' => 'help']);

if (!empty($options['help'])) {
    echo "Ragflow integration E2E for test_endpoints.py\n\n";
    echo "  php local/cria/cli/ragflow_integration_e2e.php\n";
    echo "  php local/cria/cli/ragflow_integration_e2e.php --keep   # leave bots for manual inspection\n";
    echo "  php local/cria/cli/ragflow_integration_e2e.php --cleanup-parent=12 --cleanup-child=13\n";
    echo "  php local/cria/cli/ragflow_integration_e2e.php --purge-ragflow-numeric  # remove botid-intentid Ragflow leftovers\n";
    exit(0);
}

if (!empty($options['purge-ragflow-numeric'])) {
    $removed = ragflow::cleanup_numeric_bot_artifacts();
    echo "RAGFLOW_E2E_JSON:" . json_encode(['purged' => $removed]) . PHP_EOL;
    exit(0);
}

$cleanupparent = (int) ($options['cleanup-parent'] ?? 0);
$cleanupchild = (int) ($options['cleanup-child'] ?? 0);
$cleanupempty = (int) ($options['cleanup-empty'] ?? 0);
if ($cleanupparent > 0 || $cleanupchild > 0 || $cleanupempty > 0) {
    global $DB;
    $cleanupnames = [];
    foreach ([
        'parent' => $cleanupparent,
        'child' => $cleanupchild,
        'empty' => $cleanupempty,
    ] as $botid) {
        $botid = (int) $botid;
        if ($botid <= 0) {
            continue;
        }
        $intentid = (int) $DB->get_field(
            'local_cria_intents',
            'id',
            ['bot_id' => $botid, 'is_default' => 1],
            IGNORE_MISSING
        );
        if ($intentid > 0) {
            $cleanupnames[] = $botid . '-' . $intentid;
        }
    }
    local_cria_ragflow_e2e_delete_bot($cleanupempty);
    local_cria_ragflow_e2e_delete_bot($cleanupchild);
    local_cria_ragflow_e2e_delete_bot($cleanupparent);
    sleep(2);
    local_cria_ragflow_e2e_cleanup_ragflow($cleanupnames);
    echo "RAGFLOW_E2E_JSON:" . json_encode(['cleaned' => true]) . PHP_EOL;
    exit(0);
}

global $DB, $USER;

$tag = gmdate('Ymd-His');
$marker = 'RAGFLOW_E2E_MARKER_' . $tag;
$filename = "ragflow-e2e-{$tag}.md";

$payload = [
    'ok' => false,
    'tag' => $tag,
    'marker' => $marker,
    'filename' => $filename,
];

$created = [
    'parentid' => 0,
    'childid' => 0,
    'emptyid' => 0,
];
$ragflowtargets = [];
$success = false;

try {
    $models = local_cria_ragflow_e2e_pick_models();
    if ($models === null) {
        $payload['error'] = 'No usable Ragflow-linked Moodle models after sync';
        throw new \RuntimeException($payload['error']);
    }

    $bottypeid = local_cria_ragflow_e2e_bot_type_id();

    $parentdata = new \stdClass();
    $parentdata->name = "Ragflow E2E Parent {$tag}";
    $parentdata->description = 'Parent bot for Ragflow integration test';
    $parentdata->bot_type = $bottypeid;
    $parentdata->model_id = $models['llm'];
    $parentdata->embedding_id = $models['embedding'];
    $parentdata->rerank_model_id = $models['rerank'];
    $parentdata->bot_system_message = 'Answer briefly.';
    $parentdata->requires_user_prompt = 1;
    $parentdata->requires_content_prompt = 0;
    $parentdata->temperature = 0.1;
    $parentdata->max_tokens = 500;
    $parentdata->top_p = 0;
    $parentdata->top_k = 50;
    $parentdata->min_k = 0.2;
    $parentdata->top_n = 10;
    $parentdata->min_relevance = 0.0;
    $parentdata->max_context = 8000;
    $parentdata->no_context_message = get_string('default_no_context_message', 'local_cria');
    $parentdata->no_context_use_message = 1;
    $parentdata->no_context_llm_guess = 0;
    $parentdata->parse_strategy = 'PARAGRAPH';
    $parentdata->fine_tuning = 1;
    $parentdata->theme_color = '#e31837';
    $parentdata->bot_locale = 'en-US';
    $parentdata->child_bots = [];

    $parentbot = new bot();
    $parentid = (int) $parentbot->insert_record($parentdata);
    $created['parentid'] = $parentid;
    $parentobj = new bot($parentid);
    $parentintentid = (int) $parentobj->get_default_intent_id();
    $parentcriabot = $parentobj->get_bot_name();
    $ragflowtargets[] = $parentcriabot;

    $childdata = clone $parentdata;
    $childdata->name = "Ragflow E2E Child {$tag}";
    $childdata->description = 'Child bot for Ragflow integration test';
    $childdata->child_bots = [];

    $childbot = new bot();
    $childid = (int) $childbot->insert_record($childdata);
    $created['childid'] = $childid;
    $childobj = new bot($childid);
    $childintentid = (int) $childobj->get_default_intent_id();
    $childcriabot = $childobj->get_bot_name();
    $ragflowtargets[] = $childcriabot;

    // Create a bot with no uploaded content to verify "create bot only" Ragflow visibility.
    $emptydata = clone $parentdata;
    $emptydata->name = "Ragflow E2E Empty {$tag}";
    $emptydata->description = 'No-content bot for Ragflow dataset visibility';
    $emptydata->child_bots = [];

    $emptybot = new bot();
    $emptyid = (int) $emptybot->insert_record($emptydata);
    $created['emptyid'] = $emptyid;
    $emptyobj = new bot($emptyid);
    $emptyintentid = (int) $emptyobj->get_default_intent_id();
    $emptycriabot = $emptyobj->get_bot_name();
    $ragflowtargets[] = $emptycriabot;

    $parentobj = new bot($parentid);
    $parentobj->update_record((object) [
        'id' => $parentid,
        'child_bots' => [(string) $childid],
    ]);

    $parentabout = criabot::bot_about($parentcriabot);
    $childabout = criabot::bot_about($childcriabot);
    $emptyabout = criabot::bot_about($emptycriabot);
    if (!api_response::is_success($parentabout) || !api_response::is_success($childabout) || !api_response::is_success($emptyabout)) {
        $payload['error'] = 'Bots not registered on Criabot after sync';
        $payload['parent_bot_id'] = $parentid;
        $payload['child_bot_id'] = $childid;
        $payload['empty_bot_id'] = $emptyid;
        throw new \RuntimeException($payload['error']);
    }

    $context = \context_system::instance();
    $fs = get_file_storage();
    $filecontent = "# Ragflow integration test\n\nMarker: {$marker}\n";
    $filerecord = [
        'contextid' => $context->id,
        'component' => 'local_cria',
        'filearea' => 'content',
        'itemid' => $parentintentid,
        'filepath' => '/',
        'filename' => $filename,
    ];
    $fs->create_file_from_string($filerecord, $filecontent);

    $fileid = $DB->insert_record('local_cria_files', [
        'intent_id' => $parentintentid,
        'name' => $filename,
        'file_type' => 'md',
        'content' => '',
        'indexed' => 0,
        'parsingstrategy' => 'PARAGRAPH',
        'usermodified' => $USER->id ?? 2,
        'timemodified' => time(),
        'timecreated' => time(),
    ]);

    intent::schedule_index_file((int) $parentintentid, (int) $fileid);

    $childfilename = "child-{$filename}";
    $childfilerecord = [
        'contextid' => $context->id,
        'component' => 'local_cria',
        'filearea' => 'content',
        'itemid' => $childintentid,
        'filepath' => '/',
        'filename' => $childfilename,
    ];
    $fs->create_file_from_string($childfilerecord, $filecontent);

    $childfileid = $DB->insert_record('local_cria_files', [
        'intent_id' => $childintentid,
        'name' => $childfilename,
        'file_type' => 'md',
        'content' => '',
        'indexed' => 0,
        'parsingstrategy' => 'PARAGRAPH',
        'usermodified' => $USER->id ?? 2,
        'timemodified' => time(),
        'timecreated' => time(),
    ]);
    intent::schedule_index_file((int) $childintentid, (int) $childfileid);

    $payload = [
        'ok' => true,
        'tag' => $tag,
        'marker' => $marker,
        'filename' => $filename,
        'child_filename' => $childfilename,
        'parent_bot_id' => $parentid,
        'child_bot_id' => $childid,
        'empty_bot_id' => $emptyid,
        'parent_intent_id' => $parentintentid,
        'child_intent_id' => $childintentid,
        'empty_intent_id' => $emptyintentid,
        'parent_criabot_name' => $parentcriabot,
        'child_criabot_name' => $childcriabot,
        'empty_criabot_name' => $emptycriabot,
        'parent_document_group' => $parentcriabot . '-document-index',
        'child_document_group' => $childcriabot . '-document-index',
        'empty_document_group' => $emptycriabot . '-document-index',
        'parent_bot_display_name' => $parentcriabot,
        'child_bot_display_name' => $childcriabot,
        'empty_bot_display_name' => $emptycriabot,
        'file_id' => (int) $fileid,
        'models' => $models,
    ];
    $success = true;
} catch (\Throwable $e) {
    if (empty($payload['error'])) {
        $payload['error'] = $e->getMessage();
    }
    if (!empty($created['parentid'])) {
        $payload['parent_bot_id'] = $created['parentid'];
    }
    if (!empty($created['childid'])) {
        $payload['child_bot_id'] = $created['childid'];
    }
    if (!empty($created['emptyid'])) {
        $payload['empty_bot_id'] = $created['emptyid'];
    }
} finally {
    if (empty($options['keep'])) {
        local_cria_ragflow_e2e_delete_bot((int) $created['emptyid']);
        local_cria_ragflow_e2e_delete_bot((int) $created['childid']);
        local_cria_ragflow_e2e_delete_bot((int) $created['parentid']);
        if (!empty($ragflowtargets)) {
            local_cria_ragflow_e2e_cleanup_ragflow($ragflowtargets);
        }
        if ($success) {
            $payload['cleaned'] = true;
        }
    }
}

echo 'RAGFLOW_E2E_JSON:' . json_encode($payload) . PHP_EOL;
exit($success ? 0 : 1);
