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


/*
 * Author: Admin User
 * Create Date: 27-07-2023
 * License: LGPL 
 * 
 */

namespace local_cria;

use core\notification;
use local_cria\criabot;
use local_cria\crud;
use local_cria\criabdex;
use local_cria\logs;
use local_cria\keywords;
use local_cria\embed;
use local_cria\file;
use local_cria\criaparse;
use local_cria\bot;


class intent extends crud
{


    /**
     *
     * @var int
     */
    private $id;

    /**
     *
     * @var string
     */
    private $name;

    /**
     *
     * @var string
     */
    private $description;

    /**
     *
     * @var int
     */
    private $bot_id;

    /**
     *
     * @var int
     */
    private $is_default;

    /**
     *
     * @var string
     */
    private $bot_api_key;

    /**
     *
     * @var int
     */
    private $published;

    /**
     *
     * @var string
     */
    private $lang;

    /**
     * @var string
     */
    private $faculty;

    /**
     * @var string
     */
    private $program;

    /**
     *
     * @var int
     */
    private $usermodified;

    /**
     *
     * @var int
     */
    private $timecreated;

    /**
     *
     * @var int
     */
    private $timemodified;

    /**
     *
     * @var string
     */
    private $table;


    /**
     *
     *
     */
    public function __construct($id = 0)
    {
        global $CFG, $DB, $DB;

        $this->table = 'local_cria_intents';
        parent::set_table($this->table);

        if ($id) {
            $this->id = $id;
            parent::set_id($this->id);
            $result = $this->get_record($this->table, ['id' => $this->id]);
        } else {
            $result = new \stdClass();
            $this->id = 0;
            parent::set_id($this->id);
        }

        $this->name = $result->name ?? '';
        $this->description = $result->description ?? '';
        $this->bot_id = $result->bot_id ?? 0;
        $this->is_default = $result->is_default ?? 0;
        $this->bot_api_key = $result->bot_api_key ?? '';
        $this->published = $result->published ?? 0;
        $this->lang = $result->lang ?? '';
        $this->faculty = $result->faculty ?? '';
        $this->program = $result->program ?? '';
        $this->usermodified = $result->usermodified ?? 0;
        $this->timecreated = $result->timecreated ?? 0;
        $this->timemodified = $result->timemodified ?? 0;
    }

    /**
     * @param $data
     * @return int
     * @throws \dml_exception
     */
    public function insert_record($data): int
    {
        global $DB;
        $data = $data;
        $new_intent_id = parent::insert_record($data); // TODO: Change the autogenerated stub

        if ($data->published) {
            $NEW_INTENT = new intent($new_intent_id);
            $result = $NEW_INTENT->create_intent_on_bot_server();
            // Update intent bot_api_key
            $params = new \stdClass();
            $params->id = $new_intent_id;
            $params->bot_api_key = $result->bot_api_key;
            $DB->update_record('local_cria_intents', $params);
        }
        return $new_intent_id;
    }

    public function update_record($data): int
    {
        parent::update_record($data); // TODO: Change the autogenerated stub

        if ($data->published) {
            $this->update_intent_on_bot_server();
        }

        return $this->id;
    }

    /**
     * Does the default intetn exist for this bot?
     * @param $bot_id
     * @return bool
     * @throws \dml_exception
     */
    public function default_intent_exists($bot_id)
    {
        global $DB;
        $result = $DB->get_record('local_cria_intents', ['bot_id' => $bot_id, 'is_default' => 1]);
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * Create example questions
     * @param $question_id
     * @return void
     * @throws \dml_exception
     */
    public function generate_example_questions($question_id)
    {
        global $DB, $USER;

        $QUESTION = new question($question_id);
        $QUESTION->generate_example_questions($this->id, $question_id);
    }

    /**
     * Translate question
     * @param $question_text
     * @param $language
     * @return mixed
     * @throws \dml_exception
     */
    public function translate_question($question_text, $language)
    {
        $QUESTION = new question();
        $results = $QUESTION->translate_question($this->id, $question_text, $language);
        return $results;
    }

    /**
     * Insert into logs
     * @param $results
     * @param $prompt
     * @return void
     * @throws \dml_exception
     */
    public function insert_log_record($results, $prompt)
    {
        // Get token usage
        $token_usage = $results->agent_response->chat_response->raw->usage;
        // loop through token usage and add the prompt tokens and completion tokens
        $prompt_tokens = 0;
        $completion_tokens = 0;
        $total_tokens = 0;

        $prompt_tokens = $token_usage->prompt_tokens;
        $completion_tokens = $token_usage->completion_tokens;
        $total_tokens = $token_usage->total_tokens;

        $cost = gpt::_get_cost($this->bot_id, $prompt_tokens, $completion_tokens);
        // Insert logs
        logs::insert(
            $this->bot_id,
            $prompt,
            $results->agent_response->chat_response->message->content,
            $prompt_tokens,
            $completion_tokens,
            $total_tokens,
            $cost,
            '');
    }

    /**
     * Return the id of the intent
     */
    public function get_id(): int
    {
        return $this->id;
    }

    /*
     *  Return the name of the intent
     */
    public function get_name(): string
    {
        return $this->name;
    }

    /**
     * Return the description of the intent
     */
    public function get_description(): string
    {
        return $this->description;
    }

    /**
     * @return Int bot_id
     */
    public function get_bot_id(): int
    {
        return $this->bot_id;
    }

    /**
     * @return String bot_api_key
     */
    public function get_bot_api_key(): string
    {
        return $this->bot_api_key;
    }

    /**
     * Get bot name
     * @return string
     */
    public function get_bot_name(): string
    {
        return $this->bot_id . '-' . $this->id;
    }

    /**
     * @return int default
     */
    public function get_is_default(): int
    {
        return $this->is_default;
    }

    /**
     * @return int publish
     */
    public function get_published(): int
    {
        return $this->published;
    }

    /**
     * @return string lang
     */
    public function get_lang(): string
    {
        return $this->lang;
    }

    /**
     * @return string faculty
     */
    public function get_faculty(): string
    {
        return $this->faculty;
    }

    /**
     * @return string program
     */
    public function get_program(): string
    {
        return $this->program;
    }

    /**
     *  Intent always uses bot parameters.
     *  This function returns the bot parameters in json format
     * @return String
     */
    public function get_bot_parameters_json(): string
    {
        $BOT = new \local_cria\bot($this->bot_id);
        $params = $BOT->get_bot_parameters_json();
        $params = json_decode($params);
        $system_message = $params->system_message;
        $system_message .= 'This bot is used for users asking questions with the following attributes: ';
        $system_message .= 'language: ' . $this->lang;
        if ($this->faculty) {
            $system_message .= ', faculty: ' . $this->faculty;
        }
        if ($this->program) {
            $system_message .= ', program: ' . $this->program;
        }
        $params->system_message = $system_message;

        return json_encode($params);
    }

    /**
     * @return array|false
     * @throws \dml_exception
     */
    public function get_questions(): mixed
    {
        $QUESTIONS = new questions($this->id);
        return $QUESTIONS->get_questions();
    }

    /**
     * Publish question to bot server
     * @return text
     * @throws \dml_exception
     */
    public function publish_question($question_id)
    {
        global $DB;

        // Get the question
        $question = $DB->get_record('local_cria_question', ['id' => $question_id]);
        // Get keywords and synonyms
        $KEYWORDS = new keywords();
        $keywords = $KEYWORDS->get_keywords_for_criabot($question->keywords);
        // Get all related questions
        $related_prompts =  [];
        if (!empty($question->related_questions)) {
            $related_questions = explode("\n", $question->related_questions);
            foreach ($related_questions as $related_question) {
                // Explode on pipe
                $related_question = explode('|', $related_question);
                $related_prompts[] = [
                    'label' => $related_question[0],
                    'prompt' => $related_question[1]
                    ];
            }
        }
        // Get all question examples
        $question_examples = $DB->get_records(
            'local_cria_question_example',
            [
                'questionid' => $question_id,
            ],
            'id'
        );
        // Put questions in a comma seperated string
        $question_examples_string = $question->value;
        foreach ($question_examples as $question_example) {
            $question_examples_string .= ',' . $question_example->value;
        }
        $return_generated_answer = $question->generate_answer ? true : false;
        // Create data set
        $data = array(
            'file_contents' => array(
                'questions' => explode(',', $question_examples_string),
                'answer' => strip_tags($question->answer),
                'llm_reply' => $return_generated_answer,
                'related_prompts' => $related_prompts,
            ),
            'file_metadata' => array(
                'keywords' => $keywords,
                'question_id' => $question_id,
                'return_generated_answer' => $return_generated_answer,
            )
        );

        // publish question to bot server
        if ($question->document_name) {
            $data['file_name'] = $question->document_name;
            $question_content = criabot::question_update($this->get_bot_name(), $data);
        } else {
            $question_content = criabot::question_create($this->get_bot_name(), $data);
        }
        // Update question indexed
        if ($question_content->status == 200) {
            $DB->set_field(
                'local_cria_question',
                'published',
                1,
                ['id' => $question_id]
            );
            // Only add document name if it does not exist
            if (!$question->document_name) {
                $DB->set_field(
                    'local_cria_question',
                    'document_name',
                    $question_content->document_name,
                    ['id' => $question_id]
                );
            }
            // Update all question examples indexed
            foreach ($question_examples as $question_example) {
                $DB->set_field(
                    'local_cria_question_example',
                    'indexed',
                    1,
                    ['id' => $question_example->id]
                );
            }
            return 200;
        } else {
            \core\notification::error(
                'STATUS: ' . $question_content->status . ' CODE: ' . $question_content->code . ' Message: ' . $question_content->message
            );
            return  'STATUS: ' . $question_content->status . ' CODE: ' . $question_content->code . ' Message: ' . $question_content->message;
        }
    }

    /**
     * Return user id
     */
    public function get_usermodified(): int
    {
        return $this->usermodified;
    }

    /**
     * Return time created
     */
    public function get_timecreated(): int
    {
        return $this->timecreated;
    }

    /**
     * Return time modified
     */
    public function get_timemodified(): int
    {
        return $this->timemodified;
    }

    /**
     * @param \local_cria\intent: bigint (18)
     */
    public function set_id($id): void
    {
        $this->id = $id;
    }

    /**
     * @param \local_cria\intent: varchar (255)
     */
    public function set_name($name): void
    {
        $this->name = $name;
    }

    /**
     * @param \local_cria\intent: longtext (-1)
     */
    public function set_description($description): void
    {
        $this->description = $description;
    }

    /**
     * @param $public
     * @return void
     */
    public function set_pulish($publish): void
    {
        $this->publish = $publish;
    }


    /**
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function create_intent_on_bot_server()
    {
        $bot_name = $this->bot_id . '-' . $this->id;
        $result = criabot::bot_create((string)$bot_name, $this->get_bot_parameters_json());
        return $result;
    }

    /**
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function update_intent_on_bot_server()
    {
        $bot_name = $this->bot_id . '-' . $this->id;
        $bot_exists = criabot::bot_about((string)$bot_name);

        if ($bot_exists->status == 404) {
            $result = criabot::bot_create((string)$bot_name, $this->get_bot_parameters_json());
        } else {
            $result = criabot::bot_update((string)$bot_name, $this->get_bot_parameters_json());
        }
        if ($result->status == 200) {
            return true;
        } else {
            \core\notification::error(
                'STATUS: ' . $result->status . ' CODE: ' . $result->code . ' Message: ' . $result->message
            );
        }
    }

    /**
     * Retunr all files associated with this intent
     * @return array
     * @throws \dml_exception
     */
    public function get_files()
    {
        global $DB;
        $files = $DB->get_records('local_cria_files', ['intent_id' => $this->id]);
        return $files;
    }

    /**
     * Delete intent on both bot server and local database
     * @return bool
     */
    public function delete_record(): bool
    {
        // Delete bot on bot server
        criabot::bot_delete($this->get_bot_name());
        // Delete embed
        criaembed::manage_delete($this->id);
        return parent::delete_record(); // TODO: Change the autogenerated stub
    }

    /**
     * Check file state
     * @return int
     * @throws \dml_exception
     */
    public function check_file_state() {
        global $DB;
        $file_state_sql = "SELECT COUNT(*) as count FROM {local_cria_files} WHERE intent_id = ? AND indexed IN (?,?)";
        $file_state = $DB->get_record_sql($file_state_sql, [
            $this->id,
            file::INDEXING_PENDING,
            file::INDEXING_STARTED
        ]);

        return $file_state->count;
    }

    /**
     * Index files for this intent
     * @return void
     * @throws \dml_exception
     */
    public function index_files() {
        global $DB, $USER, $CFG;
        // Create various objects
        $PARSER = new criaparse();
        $FILE = new file();
        // Get plugin config
        $config = get_config('local_cria');
        // Set context
        $context = \context_system::instance();
        // Get all files that are in state other than completed
        $files = $DB->get_records('local_cria_files', ['indexed' => $FILE::INDEXING_PENDING, 'intent_id' => $this->id]);

        // Iterate through each file
        foreach ($files as $file) {
            //            $INTENT = new intent($file->intent_id);
            $BOT = new bot($this->get_bot_id());
            // Set default path
            $path = $CFG->dataroot . '/temp/cria';
            base::create_directory_if_not_exists($path);
            // Set path based on intent_id
            $path = $CFG->dataroot . '/temp/cria/' . $this->id;
            base::create_directory_if_not_exists($path);
            // Get Moodle file
            $fs = get_file_storage();
            $moodle_file = $fs->get_file($context->id, 'local_cria', 'content', $this->id, '/', $file->name);
            // Update the file to indexing pending
            $content_data = [
                'id' => $file->id,
                'indexed' => $FILE::INDEXING_STARTED,
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
//            mtrace('llm_model_id: ' . $bot_parameters->llm_model_id);
//            mtrace('embedding_model_id: ' . $bot_parameters->embedding_model_id);

            $results = $PARSER->execute(
                $bot_parameters->llm_model_id,
                $bot_parameters->embedding_model_id,
                $parsing_strategy,
                $path . '/' . $file_name
            );
            if ($results['status'] != 200) {
                // Update file record with error and move on to the next file
                $content_data['indexed'] = $FILE::INDEXING_FAILED;
                $content_data['error_message'] = json_encode($results, JSON_PRETTY_PRINT);
                $content_data['timemodified'] = time();
                $DB->update_record('local_cria_files', $content_data);
                // Move to next file
                continue;
            } else {
                $nodes = $results['nodes'];
                // Send nodes to indexing server
                $upload = $FILE->upload_nodes_to_indexing_server($this->get_bot_name(), $nodes, $file_name, $file_type, false);
                $content_data['nodes'] = json_encode($nodes, JSON_PRETTY_PRINT);
                $content_data['timemodified'] = time();

                if ($upload->status != 200) {
                    // Update file record with error and move on to the next file
                    $content_data['indexed'] = $FILE::INDEXING_FAILED;
                    $content_data['error_message'] = 'Error uploading file to indexing server: ' . json_encode($upload, JSON_PRETTY_PRINT);
                    $DB->update_record('local_cria_files', $content_data);
                    // Move to next file
                    continue;
                } else {
                    // Update file record with completed
                    $content_data['indexed'] = $FILE::INDEXING_COMPLETE;
                    $DB->update_record('local_cria_files', $content_data);
                }
            }
        }
    }

}