<?php

/**
* This file is part of Cria.
* Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
* Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
* You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
*
* @package    local_cria
* @author     Patrick Thibaudeau
* @copyright  2024 onwards York University (https://yorku.ca)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/


require_once('../../config.php');

// CHECK And PREPARE DATA
global $CFG, $OUTPUT, $SESSION, $PAGE, $DB, $COURSE, $USER;

require_login(1, false);
$context = context_system::instance();

$bot_id = required_param('bot_id', PARAM_INT);
$date_range = optional_param('daterange', '', PARAM_TEXT);
if (!$date_range) {
    $start_date = date('m/d/Y', strtotime('First day of this month'));
    $end_date = date('m/d/Y', strtotime('Last day of this month'));
    $date_range = $start_date . ' - ' . $end_date;

}
\local_cria\base::page(new moodle_url('/local/cria/bot_logs.php',['id' => $bot_id]), get_string('pluginname', 'local_cria'), '', $context);
//**************** ******
//*** DISPLAY HEADER ***
//**********************
echo $OUTPUT->header();
raise_memory_limit(MEMORY_UNLIMITED);
$output = $PAGE->get_renderer('local_cria');
echo $output->render_from_template('local_cria/bot_logs_dashboard', ['bot_id' => $bot_id]);
//**********************
//*** DISPLAY FOOTER ***
//**********************
raise_memory_limit(MEMORY_STANDARD);
echo $OUTPUT->footer();