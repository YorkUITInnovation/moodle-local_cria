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

$id = required_param('id', PARAM_INT);

$sql = "SELECT l.*, u.firstname, u.lastname
        FROM {local_cria_logs} l
        JOIN {user} u ON l.userid = u.id
        WHERE l.id = ?";

$log = $DB->get_record_sql($sql, [$id]);
$index_context = json_decode($log->index_context);
$log->index_context = json_encode($index_context, JSON_PRETTY_PRINT);
$other = json_decode($log->other);
$log->other = json_encode($other, JSON_PRETTY_PRINT);

$BOT = new \local_cria\bot($log->bot_id);

\local_cria\base::page(new moodle_url(
    '/local/cria/bot_log_details.php',['id' => $id]),
    get_string('bot_log_details', 'local_cria'),
    get_string('bot_log_details', 'local_cria') . ' - ' . $BOT->get_name(),
    $context);


//**************** ******
//*** DISPLAY HEADER ***
//**********************
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_cria/bot_log_details', $log);
//**********************
//*** DISPLAY FOOTER ***
//**********************
echo $OUTPUT->footer();