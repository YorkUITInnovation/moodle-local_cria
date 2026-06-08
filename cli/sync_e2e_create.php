<?php

/**
 * This file is part of Cria.
 * Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
 *
 * End-to-end test: create provider, model, bot type, and bot from Cria APIs.
 *
 * @package    local_cria
 * @copyright  2024 onwards York University (https://yorku.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_cria\api_response;
use local_cria\bot;
use local_cria\criabot;
use local_cria\criadex;
use local_cria\intent;
use local_cria\model;

$longparams = [
    'help' => false,
    'keep' => false,
];
list($options) = cli_get_params($longparams, ['h' => 'help']);

if ($options['help']) {
    echo "Create provider → model → bot type → bot and verify Criadex/Criabot sync.\n";
    echo "Options:\n  --keep  Do not delete test entities after run\n";
    exit(0);
}

global $DB, $USER;

$tag = 'e2e-' . gmdate('Ymd-His');
$failures = 0;

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

$embedding = $DB->get_record('local_cria_models', ['is_embedding' => 1, 'criadex_model_id' => 11]);
$rerank = $DB->get_record('local_cria_models', ['provider_id' => 1], '*', IGNORE_MULTIPLE);
if (!$embedding || !$rerank) {
    cli_error('Need at least one linked embedding model and cohere rerank model in Moodle DB.');
}

$provider = new \stdClass();
$provider->name = "E2E Provider {$tag}";
$provider->idnumber = "e2e-{$tag}";
$provider->type = 'ollama';
$provider->llm_models = '';
$provider->usermodified = $USER->id ?? 2;
$provider->timecreated = time();
$provider->timemodified = time();
$providerid = $DB->insert_record('local_cria_providers', $provider);
$check('Create provider in Moodle', $providerid > 0, "id={$providerid}");

$modelpayload = json_encode([
    'api_base_url' => 'http://ollama:11434',
    'api_key' => 'e2e-test-key',
    'api_model' => "llama3-e2e-{$tag}",
]);
$criadexcreate = criadex::create_model($modelpayload, 'ollama');
$check('Create model in Criadex', api_response::is_success($criadexcreate), api_response::error_message($criadexcreate));
$criadexmodelid = (int) ($criadexcreate->model->id ?? 0);

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
$botdata->child_bots = json_encode([]);

$BOT = new bot();
$botid = $BOT->insert_record($botdata);
$check('Create bot in Moodle', $botid > 0, "id={$botid}");

$botobj = new bot($botid);
$check('Bot has valid model links', $botobj->has_valid_model_links());

$intentrow = $DB->get_record('local_cria_intents', ['bot_id' => $botid, 'is_default' => 1]);
$check('Default intent created', !empty($intentrow->id), 'intent_id=' . ($intentrow->id ?? 0));

$criabotname = $botid . '-' . ($intentrow->id ?? 0);
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

if (!$options['keep']) {
    cli_heading('Cleanup');
    if (!empty($intentrow->id)) {
        $INTENT = new intent($intentrow->id);
        $INTENT->delete_record();
    }
    $DB->delete_records('local_cria_bot', ['id' => $botid]);
    if ($criadexmodelid > 0) {
        criadex::delete_model($criadexmodelid, 'ollama');
    }
    $DB->delete_records('local_cria_models', ['id' => $modelid]);
    $DB->delete_records('local_cria_providers', ['id' => $providerid]);
    $DB->delete_records('local_cria_type', ['id' => $bottypeid]);
    cli_writeln('Test entities removed.');
}

cli_writeln($failures === 0 ? 'E2E PASS' : "E2E FAIL ({$failures} checks failed)");
exit($failures === 0 ? 0 : 1);
