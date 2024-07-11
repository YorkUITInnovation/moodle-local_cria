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

namespace local_cria\task;

use local_cria\file;
use local_cria\base;
use local_cria\criaparse;
use local_cria\bot;
use local_cria\intent;

class index_files extends \core\task\scheduled_task
{
    public function execute()
    {
        global $DB, $CFG, $USER;
        // Create various objects
        $PARSER = new criaparse();
        $FILE = new file();
        // Get p;lugin config
        $config = get_config('local_cria');
        // Set default path
        $path = $CFG->dataroot . '/temp/cria';
        base::create_directory_if_not_exists($path);
        // Set context
        $context = \context_system::instance();
        // Get all files that are in state other than completed
        $files = $DB->get_records('local_cria_files', ['indexed' => $FILE::INDEXING_PENDING]);

        // Iterate through each file
        foreach ($files as $file) {
            $INTENT = new intent($file->intent_id);
            $BOT = new bot($INTENT->get_bot_id());
            // Set path based on intent_id
            $path = $CFG->dataroot . '/temp/cria/' . $file->intent_id;
            base::create_directory_if_not_exists($path);
            // Get Moodle file
            $fs = get_file_storage();
            $moodle_file = $fs->get_file($context->id, 'local_cria', 'content', $file->intent_id, '/', $file->name);
            // Update the file to indexing pending
            $content_data = [
                'id' => $file->id,
                'indexed' => $FILE::INDEXING_PENDING,
                'timemodified' => time(),
                'usermodified' => $USER->id,
            ];
            $DB->update_record('local_cria_files', $content_data);

            // set bot parsing strategy
            $bot_parsing_strategy = $BOT->get_parse_strategy();
            // Insert file type
            $file_type = $FILE->get_file_type_from_mime_type($moodle_file->get_mimetype());
            // get file name
            $file_name = $moodle_file->get_filename();
            // Convert files to docx based on file type
            // Copy file to path
            $moodle_file->copy_content_to($path . '/' . $file_name);
            // If $BOT->get_parse_strategy() is not equal to $data->parsingstrategy, then update $parsing_strategy
            if ($file->parsingstrategy != $BOT->get_parse_strategy()) {
                $bot_parsing_strategy = $file->parsingstrategy;
            }
            // Set parsing strategy based on file type.
            $parsing_strategy = $PARSER->set_parsing_strategy_based_on_file_type(
                $file_type,
                $bot_parsing_strategy
            );
            // Get bot parameters to use proper model ids
            $bot_parameters = json_decode($BOT->get_bot_parameters_json());

            $results = $PARSER->execute(
                $bot_parameters->llm_model_id,
                $bot_parameters->embedding_model_id,
                $parsing_strategy,
                $path . '/' . $file_name
            );
            if ($results['status'] != 200) {
                // Update file record with error and move on to the next file
                $content_data = [
                    'id' => $file->id,
                    'indexed' => $FILE::INDEXING_FAILED,
                    'error_message' => $results['message'], // 'Error parsing file: ' . $results['message'],
                    'timemodified' => time(),
                    'usermodified' => $USER->id,
                ];
                $DB->update_record('local_cria_files', $content_data);
                // Move to next file
                continue;
            } else {
                $nodes = $results['nodes'];
                // Send nodes to indexing server
                $upload = $FILE->upload_nodes_to_indexing_server($INTENT->get_bot_name(), $nodes, $file_name, $file_type, false);
                if ($upload->status != 200) {
                    // Update file record with error and move on to the next file
                    $content_data = [
                        'id' => $file->id,
                        'indexed' => $FILE::INDEXING_FAILED,
                        'error_message' => 'Error uploading file to indexing server: ' . $upload->message,
                        'timemodified' => time(),
                        'usermodified' => $USER->id,
                    ];
                    $DB->update_record('local_cria_files', $content_data);
                    // Move to next file
                    continue;
                } else {
                    // Update file record with completed
                    $content_data = [
                        'id' => $file->id,
                        'indexed' => $FILE::INDEXING_COMPLETE,
                        'error_message' => '',
                        'timemodified' => time(),
                        'usermodified' => $USER->id,
                    ];
                    $DB->update_record('local_cria_files', $content_data);
                }
            }
        }


    }

    public function get_name(): string
    {
        return get_string('index_files', 'local_cria');
    }

    public function get_run_if_component_disabled()
    {
        return true;
    }
}

