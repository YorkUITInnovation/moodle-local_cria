<?php

/**
 * This file is part of Cria.
 * Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
 *
 * Renderable for the sync status dashboard.
 *
 * @package    local_cria
 * @copyright  2024 onwards York University (https://yorku.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cria\output;

use local_cria\sync_manager;

/**
 * Sync status dashboard template data.
 */
class sync_status implements \renderable, \templatable {

    /**
     * Export data for the sync status Mustache template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        $report = sync_manager::get_health_report();

        return [
            'healthy' => $report['healthy'],
            'last_model_sync' => $report['last_model_sync'],
            'checks' => $report['checks'],
            'sync_models_url' => (new \moodle_url('/local/cria/sync_status.php', [
                'action' => 'syncmodels',
                'sesskey' => sesskey(),
            ]))->out(false),
            'reconcile_models_url' => (new \moodle_url('/local/cria/sync_status.php', [
                'action' => 'reconcilemodels',
                'sesskey' => sesskey(),
            ]))->out(false),
            'dedupe_models_url' => (new \moodle_url('/local/cria/sync_status.php', [
                'action' => 'dedupemodels',
                'sesskey' => sesskey(),
            ]))->out(false),
            'repush_bots_url' => (new \moodle_url('/local/cria/sync_status.php', [
                'action' => 'repushbots',
                'sesskey' => sesskey(),
            ]))->out(false),
            'cleanup_unlinked_url' => (new \moodle_url('/local/cria/sync_status.php', [
                'action' => 'cleanupunlinked',
                'sesskey' => sesskey(),
            ]))->out(false),
            'auto_repair_url' => (new \moodle_url('/local/cria/sync_status.php', [
                'action' => 'autorepair',
                'sesskey' => sesskey(),
            ]))->out(false),
            'settings_url' => (new \moodle_url('/admin/settings.php', ['section' => 'local_cria_settings']))->out(false),
            'ragflow_ui_note' => get_string('sync_ragflow_ui_note', 'local_cria'),
        ];
    }
}
