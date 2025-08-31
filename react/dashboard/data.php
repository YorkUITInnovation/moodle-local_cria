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

// Prevent any output before JSON
ob_start();

// Include Moodle config
require_once(__DIR__ . '../../../../../config.php');

// Require login
require_login();

global $DB;

// Clear any buffered output and set JSON headers
ob_end_clean();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get bot_id from query parameters
    $bot_id = optional_param('bot_id', 0, PARAM_INT);
    $date_range = optional_param('date_range', null, PARAM_TEXT);

    if (!$bot_id) {
        throw new Exception('Bot ID is required');
    }

    // Get topic_keywords from table local_cria_bot
    $topic_keywords = $DB->get_field('local_cria_bot', 'topic_keywords', ['id' => $bot_id]);
    if ($topic_keywords) {
        $topic_keywords = json_decode($topic_keywords, true);
    } else {
        $topic_keywords = [];
    }
    // Get topic_options from table local_cria_bot
    $topic_options = $DB->get_field('local_cria_bot', 'topic_options', ['id' => $bot_id]);
    if ($topic_options) {
        $topic_options = json_decode($topic_options, true);
    } else {
        $topic_options = [];
    }

    // Use the existing logs class to get data
    $logs = \local_cria\logs::get_logs($bot_id, $date_range);

    // Transform the data to match the expected format for the React dashboard
    $transformed_data = [];

    foreach ($logs as $log) {

        // Get tasks. Return raw epoch then convert to ISO 8601 in PHP to ensure JS Date compatibility
        $tasks_sql = "SELECT
    ct.id,
    ct.userid AS userid,
    ct.priority,
    ct.status,
    ct.notes,
    ct.timecreated AS timecreated,
    u.firstname,
    u.lastname,
    u.email
FROM
    {local_cria_tasks} ct INNER JOIN
    {user} u ON u.id = ct.userid
WHERE
    ct.log_id = ?";
        $tasks = $DB->get_records_sql($tasks_sql, [$log->id]);

        // Normalize task timestamps to ISO 8601 strings for reliable parsing in the React app
        $task_records = array_values($tasks);
        foreach ($task_records as &$task) {
            // Ensure integer then format as ISO 8601 (server timezone)
            $task->timecreated = date('c', (int)$task->timecreated);
        }
        unset($task);

        $transformed_data[] = [
            'id' => $log->id,
            'prompt' => $log->prompt,
            'response' => $log->message,
            'promptTokens' => (int)$log->prompt_tokens,
            'completionTokens' => (int)$log->completion_tokens,
            'totalTokens' => (int)$log->total_tokens,
            'cost' => (float)$log->cost,
            'timestamp' => date('c', $log->timecreated), // timecreated is already a timestamp
            'user' => [
                'id' => $log->userid,
                'firstname' => $log->firstname,
                'lastname' => $log->lastname,
                'email' => $log->email,
                'idnumber' => $log->idnumber
            ],
            'bot' => [
                'id' => $log->bot_id,
                'name' => $log->bot_name
            ],
            'context' => $log->index_context,
            'payload' => $log->payload,
            'other' => $log->other,
            'ip' => $log->ip,
            'tasks' => $task_records
        ];
    }

    $permitted_users_sql = "Select
    u.id,
    u.firstname,
    u.lastname,
    u.email
From
    {local_cria_capability_assign} cc Inner Join
    {local_cria_bot_role} cbr On cbr.id = cc.bot_role_id Inner Join
    {user} u On u.id = cc.user_id 
Where cbr.bot_id = ?";
    $permitted_users = $DB->get_records_sql($permitted_users_sql, [$bot_id]);

    $tasks_sql = "";
    // Return JSON response
    $data = [
        'success' => true,
        'data' => $transformed_data,
        'count' => count($transformed_data),
        'bot_id' => $bot_id,
        'date_range' => $date_range,
        'topicOptions' => $topic_options,
        'topicKeywords' => $topic_keywords,
        'permittedUsers' => array_values($permitted_users)
    ];
    echo json_encode($data);
//\local_cria\base::debug_to_file('react_data.json', $data);
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => []
    ]);
}
