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
require_capability('local/cria:view_bot_logs', context_system::instance());

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
    $task_data->priority = $data['priority']; // Note: field name has typo in DB
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

        // Send email notification to the assigned user
        try {
            // Get the current user (who assigned the task)
            $from_user = $USER;

            // Get the log entry details for context
            $log_entry = $DB->get_record('local_cria_logs', ['id' => $log_id], 'id, prompt, response, bot_id');

            // Get bot name if available
            $bot_name = 'Unknown Bot';
            if ($log_entry && $log_entry->bot_id) {
                $bot_record = $DB->get_record('local_cria_bots', ['id' => $log_entry->bot_id], 'name');
                if ($bot_record) {
                    $bot_name = $bot_record->name;
                }
            }

            // Prepare email content
            $subject = 'Task Assignment: ' . $bot_name . ' - Failed Query Review';

            // Build HTML message
            $message_html = "
                <h3>Task Assignment Notification</h3>
                <p>Hello {$assignee->firstname},</p>
                <p>You have been assigned a new task to review a failed query.</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;'>
                    <h4>Task Details:</h4>
                    <p><strong>Bot:</strong> {$bot_name}</p>
                    <p><strong>Priority:</strong> " . ucfirst($data['priority']) . "</p>
                    <p><strong>Assigned by:</strong> {$from_user->firstname} {$from_user->lastname}</p>
                    <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
                
                <div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;'>
                    <h4>Failed Query:</h4>
                    <p><strong>Question:</strong> " . htmlspecialchars($log_entry->prompt) . "</p>
                    <p><strong>Response:</strong> " . htmlspecialchars(substr($log_entry->response, 0, 200)) . (strlen($log_entry->response) > 200 ? '...' : '') . "</p>
                </div>";

            // Add notes if provided
            if (!empty($data['notes'])) {
                $message_html .= "
                    <div style='background-color: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;'>
                        <h4>Additional Notes:</h4>
                        <p>" . nl2br(htmlspecialchars($data['notes'])) . "</p>
                    </div>";
            }

            $message_html .= "
                <p>Please review this query and take appropriate action to improve the bot's response.</p>
                <p>Best regards,<br>
                CRIA Bot Management System</p>";

            // Plain text version
            $message_text = "Task Assignment Notification\n\n";
            $message_text .= "Hello {$assignee->firstname},\n\n";
            $message_text .= "You have been assigned a new task to review a failed query.\n\n";
            $message_text .= "Task Details:\n";
            $message_text .= "Bot: {$bot_name}\n";
            $message_text .= "Priority: " . ucfirst($data['priority']) . "\n";
            $message_text .= "Assigned by: {$from_user->firstname} {$from_user->lastname}\n";
            $message_text .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
            $message_text .= "Failed Query:\n";
            $message_text .= "Question: " . $log_entry->prompt . "\n";
            $message_text .= "Response: " . substr($log_entry->response, 0, 200) . (strlen($log_entry->response) > 200 ? '...' : '') . "\n\n";

            if (!empty($data['notes'])) {
                $message_text .= "Additional Notes:\n" . $data['notes'] . "\n\n";
            }

            $message_text .= "Please review this query and take appropriate action to improve the bot's response.\n\n";
            $message_text .= "Best regards,\nCRIA Bot Management System";

            // Try to send email notification using Moodle's email_to_user function
            try {
                $email_sent = email_to_user($assignee, $from_user, $subject, $message_text, $message_html);

                if ($email_sent) {
                    error_log("Task assignment email sent successfully to user {$assignee->id}");
                } else {
                    error_log("Failed to send task assignment email to user {$assignee->id}");
                }
            } catch (Exception $email_error) {
                error_log('Error sending task assignment email: ' . $email_error->getMessage());
            }

        } catch (Exception $email_error) {
            // Log email error but don't fail the task creation
            error_log('Error preparing task assignment email: ' . $email_error->getMessage());
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'action' => $action,
            'task_id' => $task_id,
            'assignee' => [
                'id' => $assignee->id,
                'name' => trim($assignee->firstname . ' ' . $assignee->lastname),
                'email' => $assignee->email
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save task to database']);
    }

} catch (Exception $e) {
    error_log('Error in save_task.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
