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


// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External Web Service Template
 *
 * @package    localwstemplate
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once("$CFG->dirroot/config.php");

use local_cria\base;
use local_cria\gpt;
use local_cria\criabot;
use local_cria\criadex;
use local_cria\logs;
use local_cria\bot;
use local_cria\criaembed;

class local_cria_external_gpt extends external_api
{
    //**************************** SEARCH USERS **********************

    /*     * ***********************
     * Delete Record
     */

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function response_parameters()
    {
        return new external_function_parameters(
            array(
                'bot_id' => new external_value(PARAM_INT, 'ID of the bot being used', false, 0),
                'chat_id' => new external_value(PARAM_RAW, 'Chat ID from indexing server', false, 'none'),
                'prompt' => new external_value(PARAM_RAW, 'Question asked by user', false, ''),
                'content' => new external_value(PARAM_RAW, 'User content', false, ''),
                'filters' => new external_value(PARAM_RAW,
                    'Filters as a JSON object containing the following keys {"must":[], "must_not": [], "should": []}',
                    false, ''),
                'log_other' => new external_value(PARAM_RAW, 'Other data to log in JSON format', false, '')
            )
        );
    }

    /**
     * @param $bot_id
     * @param $chat_id
     * @param $prompt
     * @param $content
     * @param $filters
     * @param $log_other
     * @return string
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function response($bot_id, $chat_id, $prompt, $content, $filters, $log_other = '')
    {
        global $CFG, $USER, $DB, $PAGE, $OUTPUT;
        require_once($CFG->dirroot . '/local/cria/classes/Michelf/Markdown.inc.php');
        //Parameter validation
        $params = self::validate_parameters(self::response_parameters(), array(
                'bot_id' => $bot_id,
                'chat_id' => $chat_id,
                'prompt' => $prompt,
                'content' => $content,
                'filters' => $filters,
                'log_other' => $log_other
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);
        $BOT = new bot($bot_id);

        try {
            $payload = '';
            if ($chat_id != 'none') {
                $payload = criaembed::sessions_get_data($bot_id, $chat_id);
            }

            //If $filters is not empty then convert into array
            if (!empty($filters)) {
                $filters = json_decode($filters);
            }

            // Does this bot use criabot server?
            if ($BOT->use_bot_server() && $chat_id != 'none') {
                // Find out how many intents the bot has
                // If more than one then make a call to criadex to get the best intent (child bot) to use
                if ($BOT->get_number_of_intents() > 1) {
                    // Make call to criadex to return the best intent to use.
                    $intents_result = criadex::get_top_intent($bot_id, $prompt);
                    $bot_name = $intents_result->agent_response->ranked_intents[0]->name;
                    // Get cost of call
                    $cost = gpt::_get_cost(
                        $bot_id,
                        $intents_result->agent_response->usage[0]->prompt_tokens,
                        $intents_result->agent_response->usage[0]->completion_tokens
                    );
                    // Enter into logs
                    logs::insert(
                        $bot_id,
                        $prompt,
                        json_encode($intents_result),
                        $intents_result->agent_response->usage[0]->prompt_tokens,
                        $intents_result->agent_response->usage[0]->completion_tokens,
                        $intents_result->agent_response->usage[0]->total_tokens,
                        $cost,
                    );
                } else {
                    $bot_name = $BOT->get_bot_name();
                }
            }

            // Always get user prompt if there is one.
            $bot_prompt = '';
            if ($BOT->get_user_prompt()) {
                $bot_prompt = $BOT->get_user_prompt();
            }
            // Preprocess the prompt
            $prompt = gpt::pre_process_prompt($bot_id, $prompt, $bot_prompt, $payload);

            // Check to see if the prompt ends with a question mark. If not add a question mark at the end
            if (substr($prompt, -1) != '?') {
                $prompt = $prompt . '?';
            }

            if ($chat_id != 'none') {
                $result = criabot::chat_send($chat_id, $bot_name, $prompt, $filters);
                // If no errors have occured
                if ($result->status == 200) {
                    // Get token usage
                    $token_usage = $result->reply->total_usage;
                    // Check if the context type is a question
                    if (empty($result->reply->context)) {
                        $content = nl2br($result->reply->content->content);
                        $file_name = "LLM Generated";
                        $BOT->send_no_context_email($prompt, $content);
                    } else if ($result->reply->context->context_type == "QUESTION") {
                        // Return llm_reply or DB reply
                        if ($result->reply->context->node->node->metadata->llm_reply == true) {
                            $content = nl2br($result->reply->content->content);
                        } else {
                            include_once($CFG->dirroot . '/lib/filelib.php');
                            $context = \context_system::instance();
                            $question_id = $result->reply->context->node->node->metadata->question_id;
                            $question = $DB->get_record('local_cria_question', ['id' => (int)$question_id]);
                            $content = file_rewrite_pluginfile_urls(
                                $question->answer,
                                'pluginfile.php',
                                $context->id,
                                'local_cria',
                                'answer',
                                $question->id);
                            $content = format_text($content, FORMAT_HTML, base::getEditorOptions($context), $context);
                            file_put_contents('/var/www/moodledata/temp/question.html', $content);
                        }
                        $file_name = $result->reply->context->node->node->metadata->file_name;
                    } else {
                        $content = nl2br($result->reply->content->content);
                        $nodes = $result->reply->context->nodes;
                        $file_name_array = [];
                        foreach ($nodes as $n) {
                            if (isset($n->node->metadata->filename) && !empty($n->node->metadata->filename)) {
                                if (!in_array($n->node->metadata->filename, $file_name_array)) {
                                    $file_name_array[] = $n->node->metadata->filename;
                                }
                            }
                        }
                        $file_name = implode(', ', $file_name_array);
                    }
                } else {
                    switch ($result->code) {
                        case 'OPENAI_FILTER':
                            $content = nl2br(get_string('openai_filter', 'local_cria'));
                            $file_name = "LLM Generated";
                            break;
                        default :
                            $content = nl2br(get_string('default_error_message', 'local_cria'));
                            $file_name = "LLM Generated";
                            break;
                    }
                    //  Send email to support
                    $BOT->send_email_to_support($prompt, json_encode($result, JSON_PRETTY_PRINT));
                }
                // Parse content with Markdown

                $content = \Michelf\Markdown::defaultTransform($content);
                // Convert html entities into html code
                $content = html_entity_decode($content);
                // Replace href and add traget blank
                $content = str_replace('href=', 'target="_blank" href=', $content);
                // Get related prompts and add to the content
                $related_prompts = $result->reply->related_prompts;
                $related_prompts_html = $OUTPUT->render_from_template('local_cria/test_bot_related_prompts', ['related_prompts' => $related_prompts]);
                // Build message object
                $message = new \stdClass();
                $message->message = $content . '<div>' . $related_prompts_html . '</div>';
                $message->prompt_tokens = $token_usage->prompt_tokens;
                $message->completion_tokens = $token_usage->completion_tokens;
                $message->total_tokens = $token_usage->total_tokens;
                // File name
                $message->file_name = $file_name;
                $file_name_for_logs = 'file name: ' . $file_name . "<br>\n";
                $message->criabot_response = json_encode($result);
                $message->stacktrace = json_encode($result);
                $message->cost = gpt::_get_cost($bot_id, $token_usage->prompt_tokens, $token_usage->completion_tokens);

                // Insert logs
                logs::insert(
                    $bot_id,
                    $prompt,
                    $file_name_for_logs . $content,
                    $token_usage->prompt_tokens,
                    $token_usage->completion_tokens,
                    $token_usage->total_tokens,
                    $message->cost,
                    json_encode($result),
                    json_encode($payload),
                    $log_other
                );
            } else {
                $message = gpt::get_response($bot_id, $prompt, $content, false);
                $message->stacktrace = '[]';
                $message->file_name = '';
            }

            if ($prompt == false) {
                $message->status = 422;
                $details = new \stdClass();
                $details->msg = 'Prompt is required';
                $details->type = 'error';
                $message->detail = $details;
            }

            $data = [
                (array)$message
            ];
            return (array)$message;
        } catch (Exception $e) {
            file_put_contents('/var/www/moodledata/temp/catch.json', json_encode($e, JSON_PRETTY_PRINT));
        }
    }


    /**
     * Returns users result value
     * @return external_description
     */
    public static function response_returns()
    {
//        return new external_multiple_structure(self::response_details());
        $fields = array(
            'prompt_tokens' => new external_value(PARAM_INT, 'Number of prompt tokens', false),
            'completion_tokens' => new external_value(PARAM_INT, 'Number of completion tokens used', true),
            'total_tokens' => new external_value(PARAM_INT, 'Total tokens used', true),
            'cost' => new external_value(PARAM_FLOAT, 'Cost of GTP call', true),
            'file_name' => new external_value(PARAM_TEXT, 'File name from which response was generated', true),
            'message' => new external_value(PARAM_RAW, 'ID Number', true),
            'criabot_response' => new external_value(PARAM_RAW, 'Criabot Response', false),
            'stacktrace' => new external_value(PARAM_RAW, 'Stacktrace data', false)
        );
        return new external_single_structure($fields);
    }
}
