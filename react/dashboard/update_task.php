<?php
/**
 * Update or delete task assignment
 *
 * @package    local_cria
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');

require_login();
require_capability('local/cria:view_bot_logs', context_system::instance());

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

if (empty($data['query_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required field: query_id']);
    exit;
}

try {
    global $DB, $USER;

    // Extract log_id
    $log_id = $data['query_id'];
    if (strpos($log_id, 'query_') === 0) {
        $log_id = substr($log_id, 6);
    }

    // Get existing task for this log
    $existing_task = $DB->get_record('local_cria_tasks', ['log_id' => $log_id]);
    if (!$existing_task && (!isset($data['assignee_id']) && ($data['action'] ?? '') !== 'delete')) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Task not found for this log_id']);
        exit;
    }

    // Determine columns (handle potential legacy typo for priority; we don't change it here, but ensure introspection works)
    try {
        $columns = $DB->get_columns('local_cria_tasks');
    } catch (Exception $e) {
        $columns = [];
    }

    // Handle delete
    if (($data['action'] ?? '') === 'delete') {
        if ($existing_task) {
            $DB->delete_records('local_cria_tasks', ['id' => $existing_task->id]);
        }
        echo json_encode(['success' => true, 'action' => 'deleted']);
        exit;
    }

    $updated = false;

    // Handle status update
    if (isset($data['status'])) {
        $status = strtolower(trim((string)$data['status']));
        $allowed = ['assigned', 'in-progress', 'resolved'];
        if (!in_array($status, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid status value']);
            exit;
        }
        $existing_task->status = $status;
        $existing_task->usermodified = $USER->id;
        $existing_task->timemodified = time();

        // Use raw array update for reliability (mirrors save_task.php approach)
        $existing_task_arr = (array)$existing_task;
        try {
            $DB->update_record_raw('local_cria_tasks', $existing_task_arr);
        } catch (Exception $e) {
            // Fallback to object-based update
            $DB->update_record('local_cria_tasks', $existing_task);
        }
        $updated = true;
    }

    // Handle reassign (change assignee)
    if (isset($data['assignee_id'])) {
        $assignee_id = (int)$data['assignee_id'];
        if (!$DB->record_exists('user', ['id' => $assignee_id])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Assignee user not found']);
            exit;
        }

        if ($existing_task) {
            $existing_task->userid = $assignee_id;
            $existing_task->usermodified = $USER->id;
            $existing_task->timemodified = time();

            // Use raw array update for reliability
            $existing_task_arr = (array)$existing_task;
            try {
                $DB->update_record_raw('local_cria_tasks', $existing_task_arr);
            } catch (Exception $e) {
                $DB->update_record('local_cria_tasks', $existing_task);
            }
        } else {
            // If no existing task, create it with default status 'assigned'
            $task = new stdClass();
            $task->userid = $assignee_id;
            $task->log_id = $log_id;
            $task->status = 'assigned';
            $task->notes = '';
            $task->usermodified = $USER->id;
            $task->timecreated = time();
            $task->timemodified = time();

            // Prefer raw insert with array, fallback to object insert
            $task_arr = (array)$task;
            try {
                $DB->insert_record_raw('local_cria_tasks', $task_arr, true);
            } catch (Exception $e) {
                $DB->insert_record('local_cria_tasks', $task);
            }
        }
        $updated = true;
    }

    echo json_encode(['success' => true, 'action' => $updated ? 'updated' : 'noop']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
