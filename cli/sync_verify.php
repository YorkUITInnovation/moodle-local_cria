<?php

/**
 * This file is part of Cria.
 * Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
 *
 * Verify Cria provider/model/bot-type/bot sync against Criadex and Criabot.
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
use local_cria\sync_manager;

$longparams = [
    'help' => false,
    'sync' => false,
    'dedupe' => false,
    'cleanup-unlinked' => false,
    'auto-repair' => false,
    'repush-bots' => false,
];
list($options) = cli_get_params($longparams, ['h' => 'help']);

if ($options['help']) {
    echo "Verify and optionally repair Cria ↔ Criadex ↔ Criabot sync.\n\n";
    echo "Run inside Docker:\n";
    echo "  docker exec criabot-cria-1 php /var/www/html/local/cria/cli/sync_verify.php\n\n";
    echo "Options:\n";
    echo "  --sync              Pull models from Criadex before checks\n";
    echo "  --dedupe            Merge duplicate models in Criadex and Moodle\n";
    echo "  --cleanup-unlinked  Remove unused local models with no Criadex id\n";
    echo "  --auto-repair       Sync models, cleanup unlinked, dedupe local, repush bots\n";
    echo "  --repush-bots       Re-push all bots with valid model links to Criabot\n";
    exit(0);
}

cli_heading('Cria sync verification');

if ($options['auto-repair']) {
    $repair = sync_manager::run_auto_repair();
    foreach ($repair->messages as $message) {
        cli_writeln(($repair->success ? 'OK' : 'WARN') . ' — ' . $message);
    }
}

if ($options['dedupe']) {
    $deduperesult = sync_manager::dedupe_models();
    cli_writeln(($deduperesult->success ? 'OK' : 'FAIL') . ' — ' . $deduperesult->message);
}

if ($options['sync']) {
    $providerscreated = sync_manager::ensure_providers_for_remote_types();
    if ($providerscreated > 0) {
        cli_writeln("Auto-created {$providerscreated} provider(s) for remote model types.");
    }
    $removedlocal = sync_manager::dedupe_local_models();
    if ($removedlocal > 0) {
        cli_writeln("Removed {$removedlocal} duplicate Moodle model row(s) before sync.");
    }
    $result = sync_manager::sync_models_from_criadex(true);
    cli_writeln(($result->success ? 'OK' : 'FAIL') . ' — ' . $result->message);
}

if ($options['cleanup-unlinked']) {
    $cleanup = sync_manager::cleanup_unlinked_models();
    cli_writeln(($cleanup->success ? 'OK' : 'FAIL') . ' — ' . $cleanup->message);
    if (!empty($cleanup->blocked)) {
        cli_writeln('Blocked (still referenced by bots): ' . implode(', ', $cleanup->blocked));
    }
}

$report = sync_manager::get_health_report();
cli_writeln('Overall: ' . ($report['healthy'] ? 'HEALTHY' : 'ISSUES'));
cli_writeln('Last model sync: ' . $report['last_model_sync']);

foreach ($report['checks'] as $check) {
    $status = $check['ok'] ? 'OK' : 'FAIL';
    cli_writeln(sprintf('[%s] %s — %s', $status, $check['label'], $check['detail']));
    if (!$check['ok'] && !empty($check['solution'])) {
        cli_writeln('      fix: ' . $check['solution']);
    }
}

cli_heading('Database counts');

global $DB;

$providers = $DB->count_records('local_cria_providers');
$models = $DB->count_records('local_cria_models');
$linkedmodels = $DB->count_records_select('local_cria_models', 'criadex_model_id > 0');
$bottypes = $DB->count_records('local_cria_type');
$bots = $DB->count_records('local_cria_bot');
$intents = $DB->count_records('local_cria_intents');

cli_writeln("Moodle providers: {$providers}");
cli_writeln("Moodle models: {$models} (linked: {$linkedmodels})");
cli_writeln("Moodle bot types: {$bottypes}");
cli_writeln("Moodle bots: {$bots}");
cli_writeln("Moodle intents: {$intents}");

$remoteresponse = criadex::list_models();
if (api_response::is_success($remoteresponse)) {
    cli_writeln('Criadex models: ' . count($remoteresponse->models ?? []));
} else {
    cli_writeln('Criadex models: unreachable — ' . api_response::error_message($remoteresponse));
}

cli_heading('Per-bot Criabot registration');

$allbots = $DB->get_records('local_cria_bot');
foreach ($allbots as $botrow) {
    $botobj = new bot($botrow->id);
    $modelsok = $botobj->has_valid_model_links() ? 'models:ok' : 'models:MISSING';
    $published = $DB->get_records('local_cria_intents', ['bot_id' => $botrow->id, 'published' => 1]);
    if (!empty($published)) {
        foreach ($published as $intentrow) {
            $botname = $botrow->id . '-' . $intentrow->id;
            $about = criabot::bot_about((string) $botname);
            $criabotstatus = api_response::is_success($about) ? 'criabot:ok' : ('criabot:' . ($about->status ?? 'err'));
            cli_writeln("Bot {$botrow->id} intent {$intentrow->id} ({$botname}) — {$modelsok}, {$criabotstatus}");
        }
        continue;
    }
    $about = criabot::bot_about((string) $botrow->id);
    $criabotstatus = api_response::is_success($about) ? 'criabot:ok' : ('criabot:' . ($about->status ?? 'err'));
    cli_writeln("Bot {$botrow->id} ({$botrow->id}) — {$modelsok}, {$criabotstatus}");
}

if ($options['repush-bots']) {
    cli_heading('Re-pushing bots to Criabot');
    $repush = sync_manager::repush_all_bots();
    cli_writeln("Re-pushed {$repush->pushed} bot(s), failed {$repush->failed}, skipped {$repush->skipped}");
    foreach ($repush->messages as $message) {
        cli_writeln('  - ' . $message);
    }
}

cli_writeln('Done.');
exit($report['healthy'] ? 0 : 1);
