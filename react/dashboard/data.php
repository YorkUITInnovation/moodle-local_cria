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

// Include Moodle config
require_once(__DIR__ . '../../../../../config.php');
require_once($CFG->libdir . '/setuplib.php');

// Require login
require_login();

// Set JSON content type
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

    $topic_keywords = [];
    $topic_options = [];


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
            'timestamp' => date('c', strtotime($log->timecreated)), // Convert to ISO 8601 format
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
