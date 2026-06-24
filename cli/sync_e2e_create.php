<?php

/**
 * This file is part of Cria.
 * Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
 *
 * End-to-end test: create provider, model, bot type, and bot; list or purge orphaned E2E data.
 *
 * @package    local_cria
 * @copyright  2024 onwards York University (https://yorku.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

// Locate Moodle config.php relative to this script. If missing, print a helpful message
$moodleroot = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;
$configpath = $moodleroot . 'config.php';
if (!file_exists($configpath)) {
    $configpath = __DIR__ . '/../../../config.php';
}
if (!file_exists($configpath)) {
    fwrite(STDERR, "config.php not found. Run inside the Cria container:\n");
    fwrite(STDERR, "  docker exec criabot-cria-1 php /var/www/html/local/cria/cli/sync_e2e_create.php\n");
    exit(2);
}

require($configpath);
require_once($CFG->libdir . '/clilib.php');

use local_cria\api_response;
use local_cria\bot;
use local_cria\criabot;
use local_cria\criadex;
use local_cria\ragflow;
use local_cria\sync_manager;

/**
 * Pick linked embedding + rerank models after syncing from Criadex.
 *
 * @return array{embedding:int,rerank:int}|null
 */
function local_cria_sync_e2e_pick_support_models(): ?array {
    global $DB;

    sync_manager::ensure_providers_for_remote_types();
    $sync = sync_manager::sync_models_from_criadex(false);
    if (empty($sync->success)) {
        return null;
    }

    $sql = "SELECT m.id, m.criadex_model_id, m.is_embedding, m.name, m.value, p.type AS provider_type
              FROM {local_cria_models} m
              JOIN {local_cria_providers} p ON p.id = m.provider_id
             WHERE m.criadex_model_id > 0
          ORDER BY p.id ASC, m.is_embedding DESC, m.name ASC, m.id ASC";
    $rows = $DB->get_records_sql($sql);

    $embedding = null;
    $rerank = null;

    foreach ($rows as $row) {
        if (!empty($row->is_embedding) && $embedding === null) {
            $embedding = $row;
            continue;
        }
        $ptype = strtolower((string) ($row->provider_type ?? ''));
        $value = json_decode((string) ($row->value ?? ''), true);
        $modeltype = is_array($value) ? strtolower((string) ($value['model_type'] ?? '')) : '';
        if ($ptype === 'cohere' && $rerank === null) {
            $rerank = $row;
            continue;
        }
        if ($modeltype === 'rerank' && $rerank === null) {
            $rerank = $row;
        }
    }

    if ($rerank === null) {
        foreach ($rows as $row) {
            $ptype = strtolower((string) ($row->provider_type ?? ''));
            if ($ptype === 'ragflow' && empty($row->is_embedding)) {
                $rerank = $row;
                break;
            }
        }
    }

    if ($embedding === null || $rerank === null) {
        return null;
    }

    return [
        'embedding' => (int) $embedding->id,
        'rerank' => (int) $rerank->id,
    ];
}

/**
 * Find orphaned E2E entities in Moodle.
 *
 * @return array{providers: array, models: array, types: array, bots: array, intents: array}
 */
function local_cria_e2e_find_leftovers(): array {
    global $DB;

    return [
        'providers' => $DB->get_records_sql(
            "SELECT id, name, idnumber, type FROM {local_cria_providers} WHERE name LIKE ? OR idnumber LIKE ?",
            ['%E2E%', '%e2e%']
        ),
        'models' => $DB->get_records_sql(
            "SELECT id, name, criadex_model_id, provider_id FROM {local_cria_models} WHERE name LIKE ? OR value LIKE ?",
            ['%E2E%', '%e2e%']
        ),
        'types' => $DB->get_records_sql(
            "SELECT id, name FROM {local_cria_type} WHERE name LIKE ?",
            ['%E2E%']
        ),
        'bots' => $DB->get_records_sql(
            "SELECT id, name, bot_type, model_id FROM {local_cria_bot} WHERE name LIKE ?",
            ['%E2E%']
        ),
        'intents' => $DB->get_records_sql(
            "SELECT i.id, i.bot_id FROM {local_cria_intents} i
              JOIN {local_cria_bot} b ON b.id = i.bot_id
             WHERE b.name LIKE ?",
            ['%E2E%']
        ),
    ];
}

/**
 * Print orphaned E2E entities.
 *
 * @param array $leftovers
 * @return void
 */
function local_cria_e2e_list_leftovers(array $leftovers): void {
    cli_writeln('Providers: ' . count($leftovers['providers']));
    foreach ($leftovers['providers'] as $p) {
        cli_writeln("  id={$p->id} name={$p->name} idnumber={$p->idnumber} type={$p->type}");
    }

    cli_writeln('Models: ' . count($leftovers['models']));
    foreach ($leftovers['models'] as $m) {
        cli_writeln("  id={$m->id} name={$m->name} criadex={$m->criadex_model_id} provider={$m->provider_id}");
    }

    cli_writeln('Bot types: ' . count($leftovers['types']));
    foreach ($leftovers['types'] as $t) {
        cli_writeln("  id={$t->id} name={$t->name}");
    }

    cli_writeln('Bots: ' . count($leftovers['bots']));
    foreach ($leftovers['bots'] as $b) {
        cli_writeln("  id={$b->id} name={$b->name} type={$b->bot_type} model={$b->model_id}");
    }

    cli_writeln('Intents on E2E bots: ' . count($leftovers['intents']));
    foreach ($leftovers['intents'] as $i) {
        cli_writeln("  intent={$i->id} bot={$i->bot_id}");
    }
}

/**
 * Remove E2E entities from Moodle and Criadex.
 *
 * @param int $botid
 * @param int $criadexmodelid
 * @param int $modelid
 * @param int $providerid
 * @param int $bottypeid
 * @return string[] Warnings when cleanup partially failed.
 */
function local_cria_e2e_cleanup_run(
    int $botid,
    int $criadexmodelid,
    int $modelid,
    int $providerid,
    int $bottypeid
): array {
    global $DB;

    $warnings = [];

    if ($botid > 0) {
        try {
            $cleanupbot = new bot($botid);
            if (!$cleanupbot->delete_record()) {
                $warnings[] = "Moodle bot id={$botid} could not be deleted";
            }
        } catch (\Throwable $e) {
            $DB->delete_records('local_cria_bot', ['id' => $botid]);
            $warnings[] = 'Bot delete fell back to DB only: ' . $e->getMessage();
        }
    }

    if ($criadexmodelid > 0) {
        $deleteresponse = criadex::delete_model($criadexmodelid, 'ollama');
        if (!api_response::is_success($deleteresponse)) {
            $warnings[] = 'Criadex model delete failed: ' . api_response::error_message($deleteresponse);
        }
        $DB->delete_records_select('local_cria_models', 'criadex_model_id = :cid', ['cid' => $criadexmodelid]);
    } else if ($modelid > 0) {
        $DB->delete_records('local_cria_models', ['id' => $modelid]);
    }

    if ($providerid > 0) {
        $DB->delete_records('local_cria_providers', ['id' => $providerid]);
    }
    if ($bottypeid > 0) {
        $DB->delete_records('local_cria_type', ['id' => $bottypeid]);
    }

    return $warnings;
}

/**
 * Purge all orphaned E2E entities found in Moodle.
 *
 * @param array $leftovers
 * @return void
 */
function local_cria_e2e_purge_leftovers(array $leftovers): void {
    global $DB;

    $criadexids = [];
    foreach ($leftovers['models'] as $m) {
        if (!empty($m->criadex_model_id)) {
            $criadexids[(int) $m->criadex_model_id] = true;
        }
    }

    foreach ($leftovers['bots'] as $b) {
        $cleanupbot = new bot($b->id);
        if (!$cleanupbot->delete_record()) {
            cli_writeln("Failed to delete bot id={$b->id}");
        } else {
            cli_writeln("Deleted bot id={$b->id}");
        }
    }

    foreach (array_keys($criadexids) as $criadexid) {
        $response = criadex::delete_model($criadexid, 'ollama');
        if (api_response::is_success($response)) {
            cli_writeln("Deleted Criadex model id={$criadexid}");
        } else {
            cli_writeln('Criadex delete failed for id=' . $criadexid . ': ' . api_response::error_message($response));
        }
        $DB->delete_records_select('local_cria_models', 'criadex_model_id = :cid', ['cid' => $criadexid]);
    }

    foreach ($leftovers['providers'] as $p) {
        $DB->delete_records('local_cria_providers', ['id' => $p->id]);
        cli_writeln("Deleted provider id={$p->id}");
    }

    foreach ($leftovers['types'] as $t) {
        $DB->delete_records('local_cria_type', ['id' => $t->id]);
        cli_writeln("Deleted bot type id={$t->id}");
    }

    cli_writeln('Purge complete.');
}

$longparams = [
    'help' => false,
    'keep' => false,
    'list-leftovers' => false,
    'purge-leftovers' => false,
];
list($options) = cli_get_params($longparams, [
    'h' => 'help',
    'list-leftovers' => 'list-leftovers',
    'purge-leftovers' => 'purge-leftovers',
]);

if ($options['help']) {
    echo "Create provider → model → bot type → bot and verify Criadex/Criabot sync.\n\n";
    echo "Run inside Docker:\n";
    echo "  docker exec criabot-cria-1 php /var/www/html/local/cria/cli/sync_e2e_create.php\n\n";
    echo "Options:\n";
    echo "  --keep             Do not delete test entities after a create run\n";
    echo "  --list-leftovers   List orphaned E2E test entities in Moodle\n";
    echo "  --purge-leftovers  Delete orphaned E2E test entities from Moodle and Criadex\n";
    exit(0);
}

if ($options['list-leftovers'] || $options['purge-leftovers']) {
    cli_heading('E2E leftovers in Moodle');
    $leftovers = local_cria_e2e_find_leftovers();
    local_cria_e2e_list_leftovers($leftovers);

    if ($options['purge-leftovers']) {
        cli_heading('Purging E2E leftovers');
        local_cria_e2e_purge_leftovers($leftovers);
    }

    exit(0);
}

global $DB, $USER;

$tag = gmdate('Ymd-His');
$failures = 0;

// Track created entity IDs so we can attempt cleanup in finally block.
$created = [
    'providerid' => 0,
    'modelid' => 0,
    'criadexmodelid' => 0,
    'bottypeid' => 0,
    'botid' => 0,
];

/**
 * @param string $label
 * @param bool $ok
 * @param string $detail
 */
$check = function (string $label, bool $ok, string $detail = '') use (&$failures): void {
    cli_writeln(sprintf('[%s] %s%s', $ok ? 'OK' : 'FAIL', $label, $detail !== '' ? " — {$detail}" : ''));
    if (!$ok) {
        $failures++;
    }
};

cli_heading("Cria E2E create flow ({$tag})");

$config = get_config('local_cria');
$check('Criadex URL configured', !empty($config->criadex_url), (string) ($config->criadex_url ?? ''));
$check('Criabot URL configured', !empty($config->criabot_url), (string) ($config->criabot_url ?? ''));
$check('Criadex API key configured', !empty($config->criadex_api_key));

$ragflowreachable = ragflow::check_reachable();
$check('Ragflow reachable', !empty($ragflowreachable->ok), $ragflowreachable->detail ?? '');

$ragflowcredentials = ragflow::check_credentials();
if (!empty($ragflowcredentials->skipped)) {
    cli_writeln('[SKIP] Ragflow credentials — optional (set in Site admin → Cria plugin settings)');
} else {
    $check('Ragflow credentials', !empty($ragflowcredentials->ok), $ragflowcredentials->detail ?? '');
}

try {
    $supportmodels = local_cria_sync_e2e_pick_support_models();
    if ($supportmodels === null) {
        cli_error(
            'Need at least one linked embedding model and rerank model in Moodle DB after Criadex sync. '
            . 'Run Site admin → Cria → Sync models from Criadex, or check Ragflow tenant model sync.'
        );
    }
    $embedding = $DB->get_record('local_cria_models', ['id' => $supportmodels['embedding']], '*', MUST_EXIST);
    $rerank = $DB->get_record('local_cria_models', ['id' => $supportmodels['rerank']], '*', MUST_EXIST);

    $provider = new \stdClass();
    $provider->name = "E2E Provider {$tag}";
    $provider->idnumber = "e2e-{$tag}";
    $provider->type = 'ollama';
    $provider->llm_models = '';
    $provider->usermodified = $USER->id ?? 2;
    $provider->timecreated = time();
    $provider->timemodified = time();
    $providerid = $DB->insert_record('local_cria_providers', $provider);
    $created['providerid'] = (int) $providerid;
    $check('Create provider in Moodle', $providerid > 0, "id={$providerid}");

    $modelpayload = json_encode([
        'api_base_url' => 'http://ollama:11434',
        'api_key' => 'e2e-test-key',
        'api_model' => "llama3-e2e-{$tag}",
    ]);
    $criadexcreate = criadex::create_model($modelpayload, 'ollama');
    $check('Create model in Criadex', api_response::is_success($criadexcreate), api_response::error_message($criadexcreate));
    $criadexmodelid = (int) ($criadexcreate->model->id ?? 0);
    $created['criadexmodelid'] = $criadexmodelid;

    $localmodel = new \stdClass();
    $localmodel->provider_id = $providerid;
    $localmodel->name = "E2E LLM {$tag}";
    $localmodel->value = $modelpayload;
    $localmodel->max_tokens = 4092;
    $localmodel->is_embedding = 0;
    $localmodel->criadex_model_id = $criadexmodelid;
    $localmodel->prompt_cost = 0;
    $localmodel->completion_cost = 0;
    $localmodel->usermodified = $USER->id ?? 2;
    $localmodel->timecreated = time();
    $localmodel->timemodified = time();
    $modelid = $DB->insert_record('local_cria_models', $localmodel);
    $created['modelid'] = (int) $modelid;
    $check('Create model in Moodle', $modelid > 0 && $criadexmodelid > 0, "local={$modelid}, criadex={$criadexmodelid}");

    $bottype = new \stdClass();
    $bottype->name = "E2E Type {$tag}";
    $bottype->description = 'Automated sync e2e bot type';
    $bottype->use_bot_server = 1;
    $bottype->system_message = 'You are an E2E test assistant. ';
    $bottype->usermodified = $USER->id ?? 2;
    $bottype->timecreated = time();
    $bottype->timemodified = time();
    $bottypeid = $DB->insert_record('local_cria_type', $bottype);
    $created['bottypeid'] = (int) $bottypeid;
    $check('Create bot type in Moodle', $bottypeid > 0, "id={$bottypeid}");

    $botdata = new \stdClass();
    $botdata->name = "E2E Bot {$tag}";
    $botdata->description = 'Automated sync e2e bot';
    $botdata->bot_type = $bottypeid;
    $botdata->model_id = $modelid;
    $botdata->embedding_id = $embedding->id;
    $botdata->rerank_model_id = $rerank->id;
    $botdata->bot_system_message = 'Answer briefly for testing.';
    $botdata->requires_user_prompt = 1;
    $botdata->requires_content_prompt = 0;
    $botdata->temperature = 0.1;
    $botdata->max_tokens = 500;
    $botdata->top_p = 0;
    $botdata->top_k = 50;
    $botdata->min_k = 0.2;
    $botdata->top_n = 10;
    $botdata->min_relevance = 0.0;
    $botdata->max_context = 8000;
    $botdata->no_context_message = get_string('default_no_context_message', 'local_cria');
    $botdata->no_context_use_message = 1;
    $botdata->no_context_llm_guess = 0;
    $botdata->parse_strategy = 'GENERIC';
    $botdata->fine_tuning = 1;
    $botdata->theme_color = '#e31837';
    $botdata->bot_locale = 'en-US';
    $botdata->child_bots = [];

    $BOT = new bot();
    $botid = $BOT->insert_record($botdata);
    $created['botid'] = (int) $botid;
    $check('Create bot in Moodle', $botid > 0, "id={$botid}");

    $botobj = new bot($botid);
    $check('Bot has valid model links', $botobj->has_valid_model_links());
    $check('Bot has bot type assigned', $botobj->get_bot_type() > 0, 'type=' . $botobj->get_bot_type());

    $defaultcount = $DB->count_records('local_cria_intents', ['bot_id' => $botid, 'is_default' => 1]);
    $intentid = $botobj->get_default_intent_id();
    $check('Single default intent', $defaultcount === 1 && $intentid > 0, "intent_id={$intentid}, rows={$defaultcount}");

    $synced = $botobj->sync_to_criabot(false);
    $check('sync_to_criabot succeeded', $synced);

    $criabotname = $botid . '-' . $intentid;
    $about = criabot::bot_about($criabotname);
    $check('Bot registered on Criabot', api_response::is_success($about), api_response::error_message($about, $criabotname));

    $params = json_decode($botobj->get_bot_parameters_json());
    $check(
        'Bot params use new Criadex model id',
        (int) ($params->llm_model_id ?? 0) === $criadexmodelid,
        'llm_model_id=' . ($params->llm_model_id ?? 'null')
    );
    $check(
        'Bot system message includes bot type message',
        strpos((string) ($params->system_message ?? ''), 'E2E test assistant') !== false
    );

    $health = sync_manager::get_health_report();
    $check('Sync health report healthy', !empty($health['healthy']), 'See sync_verify.php for details');

    if (!$options['keep']) {
        cli_heading('Cleanup');
        $cleanwarnings = local_cria_e2e_cleanup_run($botid, $criadexmodelid, $modelid, $providerid, $bottypeid);

        if (!empty($cleanwarnings)) {
            cli_writeln('Cleanup completed with warnings (use --purge-leftovers if entities remain):');
            foreach ($cleanwarnings as $warning) {
                cli_writeln("  - {$warning}");
            }
        } else {
            cli_writeln('Test entities removed.');
        }
    }

    cli_writeln($failures === 0 ? 'E2E PASS' : "E2E FAIL ({$failures} checks failed)");
    exit($failures === 0 ? 0 : 1);
} finally {
    // Always attempt cleanup unless user asked to keep entities. This tries to be defensive
    if (empty($options['keep'])) {
        // Use any created IDs available in $created array
        $botid = $created['botid'] ?? 0;
        $criadexmodelid = $created['criadexmodelid'] ?? 0;
        $modelid = $created['modelid'] ?? 0;
        $providerid = $created['providerid'] ?? 0;
        $bottypeid = $created['bottypeid'] ?? 0;

        if ($botid || $criadexmodelid || $modelid || $providerid || $bottypeid) {
            cli_heading('Post-run cleanup (attempting to remove created test entities)');
            try {
                $warnings = local_cria_e2e_cleanup_run($botid, $criadexmodelid, $modelid, $providerid, $bottypeid);
                if (!empty($warnings)) {
                    cli_writeln('Cleanup completed with warnings:');
                    foreach ($warnings as $w) {
                        cli_writeln("  - {$w}");
                    }
                } else {
                    cli_writeln('Post-run cleanup removed created entities.');
                }
            } catch (\Throwable $e) {
                cli_writeln('Post-run cleanup failed: ' . $e->getMessage());
            }
        }
    }
}
