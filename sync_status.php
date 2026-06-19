<?php

/**
 * This file is part of Cria.
 * Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
 *
 * Sync status dashboard and manual repair actions.
 *
 * @package    local_cria
 * @copyright  2024 onwards York University (https://yorku.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use local_cria\base;
use local_cria\sync_manager;

global $CFG, $OUTPUT, $PAGE;

require_login(1, false);
$context = context_system::instance();

if (!has_capability('local/cria:view_providers', $context)) {
    redirect(new moodle_url('/local/cria/index.php'));
}

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'syncmodels') {
    require_sesskey();
    sync_manager::run_model_sync(true);
    redirect(new moodle_url('/local/cria/sync_status.php'));
}

if ($action === 'reconcilemodels') {
    require_sesskey();
    sync_manager::run_model_sync(true, true);
    redirect(new moodle_url('/local/cria/sync_status.php'));
}

if ($action === 'dedupemodels') {
    require_sesskey();
    $result = sync_manager::dedupe_models();
    if ($result->success) {
        \core\notification::success($result->message);
    } else {
        \core\notification::warning($result->message);
    }
    redirect(new moodle_url('/local/cria/sync_status.php'));
}

if ($action === 'cleanupunlinked') {
    require_sesskey();
    $result = sync_manager::cleanup_unlinked_models();
    if ($result->success) {
        \core\notification::success($result->message);
        if (!empty($result->blocked)) {
            \core\notification::warning(get_string(
                'sync_cleanup_unlinked_blocked_detail',
                'local_cria',
                implode(', ', $result->blocked)
            ));
        }
    } else {
        \core\notification::warning($result->message);
    }
    redirect(new moodle_url('/local/cria/sync_status.php'));
}

if ($action === 'autorepair') {
    require_sesskey();
    $result = sync_manager::run_auto_repair();
    foreach ($result->messages as $message) {
        if ($result->success) {
            \core\notification::success($message);
        } else {
            \core\notification::warning($message);
        }
    }
    redirect(new moodle_url('/local/cria/sync_status.php'));
}

if ($action === 'repushbots') {
    require_sesskey();
    $repush = sync_manager::repush_all_bots();
    if ($repush->failed > 0) {
        \core\notification::warning(get_string('sync_repush_bots_partial', 'local_cria', $repush));
        foreach (array_slice($repush->messages, 0, 5) as $message) {
            \core\notification::warning($message);
        }
        if (count($repush->messages) > 5) {
            \core\notification::warning(get_string('sync_repush_more_failures', 'local_cria', count($repush->messages) - 5));
        }
    } else if ($repush->pushed > 0) {
        \core\notification::success(get_string('sync_repush_bots_success', 'local_cria', $repush->pushed));
    } else {
        \core\notification::warning(get_string('sync_repush_bots_none', 'local_cria'));
    }
    redirect(new moodle_url('/local/cria/sync_status.php'));
}

base::page(
    new moodle_url('/local/cria/sync_status.php'),
    get_string('sync_status', 'local_cria'),
    get_string('sync_status', 'local_cria'),
    $context,
    'standard'
);

echo $OUTPUT->header();

$output = $PAGE->get_renderer('local_cria');
$dashboard = new \local_cria\output\sync_status();
echo $output->render_sync_status($dashboard);

echo $OUTPUT->footer();
