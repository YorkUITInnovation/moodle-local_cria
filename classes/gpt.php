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


namespace local_cria;

use local_cria\bot;
use local_cria\logs;
use local_cria\criadex;

class gpt
{

    /**
     * @param $service_url string
     * @param $api_key string
     * @param $data string JSON formatted string
     * @param $call string
     * @param $method string
     * @param $file_path string
     * @param $file_name string
     * @return mixed
     * @throws \dml_exception
     */
    public static function _make_call(
        $service_url,
        $api_key,
        $data,
        $call = '',
        $method = 'GET',
        $file_path = false,
        $file_name = false
    )
    {
        global $CFG;
        $config = get_config('local_cria');
        // Set stacktrace
        $stacktrace = 'X-Api-Stacktrace: false';
        if ($CFG->debug != 0) {
            $stacktrace = 'X-Api-Stacktrace: true';
        }
        $ch = curl_init();
        // Set curl attributes for regular API calls
        // If there is a file path, then it's a file upload
        if ($file_path == false) {
            curl_setopt_array($ch, array(
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_TIMEOUT => 2000,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                )
            );
            // Set headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'x-api-key: ' . $api_key,
                    $stacktrace
                )
            );
        } else {
            // Params for file upload
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

// Set headers
            $headers = [
                'Accept: application/json',
                'x-api-key: ' . $api_key,
                $stacktrace,
                'Content-Type: multipart/form-data',
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Set POST data (multipart/form-data)
            $post_data = [
                'file' => new \CURLFile(
                    $file_path,
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    $file_name
                ),
            ];
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        // Set URL
        $url = $service_url . $call;

        curl_setopt($ch, CURLOPT_URL, $url);
        if ($CFG->debug != 0) {
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        }

        $result = json_decode(curl_exec($ch));

        curl_close($ch);

        return $result;
    }


    /**
     * Build the message to send to the API
     * @param int $bot_id
     * @param $prompt
     * @return object
     * @throws \dml_exception
     */
    protected static function _build_message($bot_id, $prompt, $content = '')
    {
        // Create object that will return the data
        $data = new \stdClass();
        $BOT = new bot($bot_id);
        $system_message = $BOT->get_bot_system_message();
        // Get crai paramaeters
        $params = json_decode($BOT->get_bot_parameters_json());
        $user_content = $content;

        // Remove all lines and replace with space. AKA lower token count
//        $user_content = preg_replace('/\s+/', ' ', trim($user_content));
//        if there is user content, split into chunks
        if ($user_content) {
            // Get number of words in content and split it into chunks if it's too long
            $chunk_text = self::_split_into_chunks($bot_id, $user_content);
            // Determine the context window size (overlap)
            $context_window_size = 50;
            $prompt_tokens = 0;
            $completion_tokens = 0;
            $total_tokens = 0;
            $summary = [];
            $i = 0;
            // Save the chunks to a file for debugging
            // Loop through the chunks and send them to the API
            foreach ($chunk_text as $i => $chunk) {
                // Add the previous response's tail as the context for the current chunk
                if ($i > 0) {
                    $context = substr($chunk_text[$i - 1], -$context_window_size);
                    $chunk = $context . $chunk;
                }
                // Use grounding context
                if ($i == 0) {
                    $full_prompt = $chunk . "\nq: " . $prompt;
                } else {
                    $full_prompt = 'Build off the previous call. Do not start over. ' . $chunk . "\nq: " . $prompt;
                }

                // Use Criadex to make the call
                $result = criadex::query(
                    $params->llm_model_id,
                    $params->system_message,
                    $full_prompt,
                    $params->max_reply_tokens,
                    $params->temperature,
                    $params->top_p
                );
                // Add the number of tokens used for the prompt to the total tokens
                $prompt_tokens = $prompt_tokens + $result->agent_response->usage[0]->prompt_tokens;
                $completion_tokens = $completion_tokens + $result->agent_response->usage[0]->completion_tokens;
                $total_tokens = $total_tokens + $result->agent_response->usage[0]->total_tokens;
                // Capture the response
                $summary[] = $result->agent_response->chat_response->message->content;
            }
            if (count($summary) > 1) {
                $content_prompt = '';
                $sentences = '';
                foreach ($summary as $i => $response) {
                    if ($response != '') {
                        $sentences .= $response . "\n";
                    }
                }
                $content_prompt .= $sentences;
                $content_prompt .= "Question: Please answer with a boolean only to the following question. In the sentences provided above, do all the sentences mean the same thing?\n";
                // Compare results
                $comparison_result = criadex::query(
                    $params->llm_model_id,
                    'You compare text. You only answer with a single boolean. You return the boolean that appears more often.',
                    $content_prompt,
                    $params->max_reply_tokens,
                    $params->temperature,
                    $params->top_p
                );
                // Add the number of tokens used for the comparison to the total tokens
                $prompt_tokens = $prompt_tokens + $comparison_result->agent_response->usage[0]->prompt_tokens;
                $completion_tokens = $completion_tokens + $comparison_result->agent_response->usage[0]->completion_tokens;
                $total_tokens = $total_tokens + $comparison_result->agent_response->usage[0]->total_tokens;

                $answer = $comparison_result->agent_response->chat_response->message->content;
                if ($answer == 'True') {
                    $summaries = $summary[0];
                } else {
                    $summaries = implode('', $summary);
                }
            } else {
                // Implode the chunks into one string
                $summaries = implode('', $summary);
            }
        } else {
            // Use Criadex to make the call
            $result = criadex::query(
                $params->llm_model_id,
                $params->system_message,
                $prompt,
                $params->max_reply_tokens,
                $params->temperature,
                $params->top_p
            );
            $summaries = $result->agent_response->chat_response->message->content;

            // Add the number of tokens used for the prompt to the total tokens
            $prompt_tokens = $result->agent_response->usage[0]->prompt_tokens;
            $completion_tokens = $result->agent_response->usage[0]->completion_tokens;
            $total_tokens = $result->agent_response->usage[0]->total_tokens;
        }

        // Get the cost of the call
        $cost = self::_get_cost($bot_id, $prompt_tokens, $completion_tokens);
        // Add to logs
        logs::insert($bot_id, $prompt, $summaries, $prompt_tokens, $completion_tokens, $total_tokens, $cost);

        $data->prompt_tokens = $prompt_tokens;
        $data->completion_tokens = $completion_tokens;
        $data->total_tokens = $total_tokens;
        $data->cost = $cost;
        $data->message = $summaries;
        return $data;
    }

    /**
     * Split a long text into smaller chunks
     * @param $long_text
     * @return array
     */
    public static function _split_into_chunks($bot_id, $long_text)
    {
        $BOT = new bot($bot_id);
        $max_tokens = $BOT->get_max_tokens();
        $max_context = $BOT->get_max_context();
        $characters_per_token = 3; //For english, but should work for most languages

        // Get the length of the long text
        $text_word_count = mb_strlen($long_text);
        $characters_per_chunk = (int)(($max_tokens - $max_context) * $characters_per_token);

        $long_text = str_replace("\n", ' ', $long_text);
        // split the long text into chunks based on the number of characters_per_chunk, but do not cut a word apart
//        $chunks = explode("\n", wordwrap($long_text, $characters_per_chunk, "\n"));
        $chunks = str_split($long_text, 5000);

        return $chunks;
    }

    /**
     * Take a string and turn any valid URLs into HTML links
     * @param $input
     * @return array|string|string[]|null
     */
    public static function make_link($input)
    {
        $url_pattern = '<https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)>';
        $str = preg_replace($url_pattern, '<a href="$0" target="_blank">$0</a> ', $input);
        // Remove duplicate links
        return preg_replace('/\[.*\]/', '', $str);
    }

    /**
     * Take a string and turn any valid emails into HTML links
     * @param $input
     * @return array|string|string[]|null
     */
    public static function make_email($input)
    {
        //Detect and create email
        $mail_pattern = "/([A-z0-9\._-]+\@[A-z0-9_-]+\.)([A-z0-9\_\-\.]{1,}[A-z])/";
        return preg_replace($mail_pattern, '<a href="mailto:$1$2">$1$2</a>', $input);

    }

    /**
     * Get cost of API call
     * @param $prompt_tokens
     * @param $completion_tokens
     * @return float
     * @throws \dml_exception
     */
    public static function _get_cost($bot_id, $prompt_tokens, $completion_tokens): float
    {
        // plugin config
        $BOT = new bot($bot_id);
        $model = $BOT->get_model_config();

        $prompt_cost = ($prompt_tokens / 1000) * $model->prompt_cost;
        $completion_cost = ($completion_tokens / 1000) * $model->completion_cost;
        $cost = $prompt_cost + $completion_cost;
        return $cost;
    }

    /**
     * Get the response from the API
     * @param $bot_id
     * @param $prompt
     * @param $content
     * @return object
     * @throws \dml_exception
     */
    public static function get_response($bot_id, $prompt, $content = '', $use_bot_server = false): object
    {
        $BOT = new bot($bot_id);
        $full_prompt = '';
        if ($BOT->get_user_prompt()) {
            $full_prompt .= $BOT->get_user_prompt();
        }

        if (!empty($content)) {
            $full_prompt .= $content . ' ' . $prompt;
        } else {
            $full_prompt .= $prompt;
        }

        $params = json_decode($BOT->get_bot_parameters_json());

        // Use Criadex to make the call
        $results = criadex::query(
            $params->llm_model_id,
            $params->system_message,
            $full_prompt,
            $params->max_reply_tokens,
            $params->temperature,
            $params->top_p
        );

        $summaries = $results->agent_response->chat_response->message->content;

        // Add the number of tokens used for the prompt to the total tokens
        $prompt_tokens = $results->agent_response->raw->usage->prompt_tokens;
        $completion_tokens = $results->agent_response->raw->usage->completion_tokens;
        $total_tokens = $results->agent_response->raw->usage->total_tokens;

        $data = new \stdClass();
        // Get the cost of the call
        $cost = self::_get_cost($bot_id, $prompt_tokens, $completion_tokens);
        // Add to logs
        logs::insert($bot_id, $full_prompt, $summaries, $prompt_tokens, $completion_tokens, $total_tokens, $cost);

        $data->prompt_tokens = $prompt_tokens;
        $data->completion_tokens = $completion_tokens;
        $data->total_tokens = $total_tokens;
        $data->cost = $cost;
        $data->message = $summaries;

        return $data;
    }

    /**
     * Used for automatic testing and comparing text
     * @param $bot_id
     * @param $prompt
     * @return string
     * @throws \dml_exception
     */
    public static function compare_text($response, $answer): \stdClass
    {
        $content_prompt = '';
        $sentences = "---\nText 1: " . $response . "\n\nText 2: " . $answer . "\n---\n";
        $content_prompt .= $sentences . "q: Question: Please answer with a boolean only to the following question. 
        In the two texts provided above, do the two texts mean the same thing?\n";


        $comparison_result = criadex::query(
            1,
            'You compare text. You only answer with a single boolean. You return the boolean that appears more often.',
            $content_prompt,
            4000,
            0.1,
            1
        );
        return $comparison_result;
    }

    /**
     *  Used to add content to the prompt.
     *  Although the payload is used for Moodle. It can be used for any other site.
     *  The payload must contain the following: sessionData->name, sessionData->groups, sessionData->grade
     *  Other values can be added.
     * @param $user_prompt string The question or query from the user
     * @param $bot_prompt string If the bot has a defualt prompt to prepend to the user prompt
     * @param $payload array Data that identifies the user
     * @param $person_identifier string The sentence used for identifying the user. The default is I am a student and my name is
     * @return string
     */
    public static function pre_process_prompt(
        $bot_id,
        $user_prompt,
        $bot_prompt = '',
        $payload = false
    ): string
    {
        global $CFG;
        // Get the bot
        $BOT = new bot($bot_id);
        // Store prompt into a variable for use later
        $prompt = '';
        // If there no period (.) at the end of the bot prompt add one
        if ((!str_ends_with($bot_prompt, '.')) && (!str_ends_with($bot_prompt, ': '))) {
            $bot_prompt = $bot_prompt . '.';
        }
        // If the bot prompt does not have q: or Q: at the end, add it
        if ((!str_ends_with($bot_prompt, ' q:')) &&
            (!str_ends_with($bot_prompt, ' q:.')) &&
            (!str_ends_with($bot_prompt, ' q: '))
        ) {
            $bot_prompt = $bot_prompt . ' q: ';
        }
        // Add a space to the end of the user prompt and bot prompt
        $user_prompt = trim($user_prompt) . ' ';

        // If there is a payload, then add the payload to the prompt
        if ($payload) {
            // Get bot variables
            $bot_variables = $BOT->get_variables_array();
            // Get the preprocess rules
            $bot_rules_array = $BOT->get_preprocess_rules_array();
            $bot_rules = '';
            $bot_replace_rules = [];

            foreach ($bot_rules_array as $key => $rule) {
                // only add to bot_rules if the rule does not contain ==
                if (!str_contains($rule, '=>')) {
                    // If there is not a period at the end of teh $rule, add one
                    if (!str_ends_with($rule, '.')) {
                        $rule = $rule . '. ';
                    }
                    $bot_rules .= $rule;
                } else {
                    // If the rule contains ==, then it's a replacement rule
                    $bot_replace_rules[] = $rule;
                }
            }

            foreach ($bot_variables as $key => $variable) {
                $variable = trim($variable);
                if (isset($payload->sessionData->$variable)) {
                    $bot_rules = str_replace('[' . $variable . ']', $payload->sessionData->$variable, $bot_rules);
                    $bot_prompt = str_replace('[' . $variable . ']', $payload->sessionData->$variable, $bot_prompt);
                    $user_prompt = str_replace('[' . $variable . ']', $payload->sessionData->$variable, $user_prompt);
                }
            }
            $prompt = $bot_rules;
        }
        $prompt = $prompt . ' ' . $bot_prompt . $user_prompt;
        $prompt = trim($prompt);
        // Loop through $bot_replace_rules and replace the values
        foreach ($bot_replace_rules as $key => $replace_rule) {
            $replace_options = explode('=>', $replace_rule);
            $prompt = str_replace(trim($replace_options[0]), trim($replace_options[1]), $prompt);
        }

        // If the prompt contains What is this course about, rewrite the prompt as Describe this course.
        // This is only used with AI Course Assistant and is in place until MS fixes it's filter issue.
        if (strpos($prompt, 'What is this course about') !== false) {
            $prompt = rtrim($prompt, '?');
            $prompt = str_replace('What is this course about', 'Describe this course.', $prompt);
        }

        // LLM has difficulty answering questions that start with "I don't know", so it needs to be modified.
        if (str_starts_with($prompt, "I don't know") || str_starts_with($prompt, "I dont know")) {
            $prompt = str_replace(["I don't know", "I dont know"], "I would like to know", $prompt);
        }

        // LLM has difficulty answering questions that start with "I don't know" in French, so it needs to be modified.
        if (str_starts_with($prompt, "Je ne sais pas")) {
            $prompt = str_replace("Je ne sais pas", "Je voudrais savoir", $prompt);
        }

        // Add a question mark if the query doesn't end in a question mark and starts with a question word
        $question_start = ["what", "how", "why", "when", "who", "whom", "whose", "which", "where", "does", "is", "are",
            "can", "could", "will", "would", "should", "may", "might", "have", "must"];
        if (!str_ends_with($prompt, "?")) {
            $first_word = strtolower(strtok($prompt, " "));
            if (in_array($first_word, $question_start)) {
                $prompt = rtrim($prompt) . "?";
            }
        }

        // IF the prompt is in french, add a question mark if it doesn't end in a question mark and starts with a question word
        $question_start_fr = ["que", "comment", "pourquoi", "quand", "qui", "à qui", "de qui", "lequel", "où", "est-ce que", "est-ce", "peut", "pourrait", "va",
            "voudrait", "devrait", "peut-être", "pourrait", "a", "doit"];
        if (!str_ends_with($prompt, "?")) {
            $first_word = strtolower(strtok($prompt, " "));
            if (in_array($first_word, $question_start_fr)) {
                $prompt = rtrim($prompt) . "?";
            }
        }

        // Replace [current_date] placeholder with the current date
        $prompt = str_replace('[current_date]', date('Y-m-d'), $prompt);

        return $prompt;
    }

}