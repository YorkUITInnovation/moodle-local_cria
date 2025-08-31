<?php
/**
 * Save task assignment to database
 *
 * @package    local_cria
 * @author     Patrick Thibaudeau
 * @copyright  2024 onwards York University (https://yorku.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');

// Require login
require_login();

// Check if user has capability to assign tasks
require_capability('local/cria:manage_bots', context_system::instance());

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$required_fields = ['assignee_id', 'query_id', 'priority'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing required field: {$field}"]);
        exit;
    }
}

try {
    global $DB, $USER;

    // Extract query ID from the query_id field (remove 'query_' prefix if present)
    $log_id = $data['query_id'];
    if (strpos($log_id, 'query_') === 0) {
        $log_id = substr($log_id, 6); // Remove 'query_' prefix
    }

    // Validate that the log entry exists
    $log_exists = $DB->record_exists('local_cria_logs', ['id' => $log_id]);
    if (!$log_exists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Log entry not found']);
        exit;
    }

    // Validate that the assignee user exists
    $assignee_exists = $DB->record_exists('user', ['id' => $data['assignee_id']]);
    if (!$assignee_exists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Assignee user not found']);
        exit;
    }

    // Check if task already exists for this log_id
    $existing_task = $DB->get_record('local_cria_tasks', ['log_id' => $log_id]);

    $task_data = new stdClass();
    $task_data->userid = $data['assignee_id'];
    $task_data->log_id = $log_id;
    $task_data->priotrity = $data['priority']; // Note: field name has typo in DB
    $task_data->notes = isset($data['notes']) ? $data['notes'] : '';
    $task_data->usermodified = $USER->id;
    $task_data->timemodified = time();

    if ($existing_task) {
        // Update existing task
        $task_data->id = $existing_task->id;
        $task_data->timecreated = $existing_task->timecreated; // Keep original creation time

        $result = $DB->update_record('local_cria_tasks', $task_data);
        $task_id = $existing_task->id;
        $action = 'updated';
    } else {
        // Create new task
        $task_data->timecreated = time();

        $task_id = $DB->insert_record('local_cria_tasks', $task_data);
        $result = $task_id > 0;
        $action = 'created';
    }

    if ($result) {
        // Get the assignee's name for the response
        $assignee = $DB->get_record('user', ['id' => $data['assignee_id']], 'id, firstname, lastname, email');

        echo json_encode([
            'success' => true,
            'message' => "Task {$action} successfully",
            'task_id' => $task_id,
            'assignee' => [
                'id' => $assignee->id,
                'name' => trim($assignee->firstname . ' ' . $assignee->lastname),
                'email' => $assignee->email
            ],
            'action' => $action
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => "Failed to {$action} task"]);
    }

} catch (Exception $e) {
    error_log('Error saving task: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
