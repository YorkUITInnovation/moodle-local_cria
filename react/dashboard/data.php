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
            'ip' => $log->ip
        ];
    }

    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => $transformed_data,
        'count' => count($transformed_data),
        'bot_id' => $bot_id,
        'date_range' => $date_range,
        'topicOptions' => $topic_options,
        'topicKeywords' => $topic_keywords
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => []
    ]);
}
