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

use local_cria\cria;
use local_cria\bot;
use local_cria\criabot;

/**
 * External Web Service Template
 *
 * @package    localwstemplate
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once("$CFG->dirroot/config.php");

class local_cria_external_criabot extends external_api
{

    /****** Start chat session ******/
    /**
     *
     * /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function chat_start_parameters()
    {
        return new external_function_parameters(
            array()
        );
    }

    /**
     * @param $id
     * @return true
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function chat_start()
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::chat_start_parameters(), array()
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        $session = criabot::chat_start();
        if ($session->status != 200) {
            return $session->message;
        }
        return $session->chat_id;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function chat_start_returns()
    {
        return new external_value(PARAM_RAW, 'chat id');
    }

    /****** End chat session ******/
    /**
     *
     * /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function chat_end_parameters()
    {
        return new external_function_parameters(
            array(
                'chat_id' => new external_value(PARAM_RAW, 'ID of the chat session', false, ''),
            )
        );
    }
    /**
     * @param $id
     * @return true
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function chat_end($chat_id)
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::chat_end_parameters(), array(
                'chat_id' => $chat_id,
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        // End chat session
        criabot::chat_end($params['chat_id']);

        return true;
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function chat_end_returns()
    {
        return new external_value(PARAM_BOOL, 'true');
    }

    public static function chat_exists_parameters()
    {
        return new external_function_parameters(
            array(
                'chat_id' => new external_value(PARAM_RAW, 'ID of the chat session', false, ''),
            )
        );
    }

    public static function chat_exists($chat_id)
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::chat_exists_parameters(), array(
                'chat_id' => $chat_id,
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        // Returns the following:
//        stdClass Object
//          (
//              [status] => 200
//              [message] =>
//              [timestamp] => 1753410528
//              [code] => SUCCESS
//              [exists] =>
//          )

        // Check if chat session exists
        return criabot::chat_exists($params['chat_id']);
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function chat_exists_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'HTTP status code'),
                'message' => new external_value(PARAM_TEXT, 'Response message'),
                'timestamp' => new external_value(PARAM_INT, 'Timestamp of the response'),
                'code' => new external_value(PARAM_TEXT, 'Response code'),
                'exists' => new external_value(PARAM_BOOL, 'True if chat session exists', VALUE_OPTIONAL),
            )
        );
    }


    /*** Send message to chat session ******/
    /**
     *
     * /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function chat_send_parameters()
    {
        return new external_function_parameters(
            array(
                'chat_id' => new external_value(PARAM_RAW, 'ID of the chat session', VALUE_REQUIRED, ''),
                'prompt' => new external_value(PARAM_RAW, 'Message to send', VALUE_REQUIRED, ''),
                'bot_name' => new external_value(PARAM_TEXT, 'Name of the bot', VALUE_REQUIRED, 0)
            )
        );
    }

    /**
     * @param $id
     * @return true
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function chat_send($chat_id, $prompt, $bot_name)
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::chat_send_parameters(), array(
                'chat_id' => $chat_id,
                'prompt' => $prompt,
                'bot_name' => $bot_name
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        // Send message to chat session
        $results = criabot::chat_send($params['chat_id'], $params['bot_name'],$params['prompt']);
        $response  = [];
        if ($results->status != 200) {
            $response['status'] = $results->status;
            $response['chat_id'] = $params['chat_id'];
            $response['bot_name'] = $params['bot_name'];
            $response['prompt'] = $params['prompt'];
            $response['content'] = 'Error sending message: ' . $results->message;
            $response['timestamp'] = time();
            $response['code'] = 'ERROR';
        } else {
            $response['status'] = 200;
            $response['chat_id'] = $params['chat_id'];
            $response['bot_name'] = $params['bot_name'];
            $response['prompt'] = $results->reply->prompt;
            $response['content'] = markdown_to_html($results->reply->content->content);
            $response['timestamp'] = time();
            $response['code'] = 'SUCCESS';
        }

        return $response;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function chat_send_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'HTTP status code'),
                'code' => new external_value(PARAM_TEXT, 'Response code'),
                'chat_id' => new external_value(PARAM_RAW, 'ID of the chat session', VALUE_OPTIONAL),
                'bot_name' => new external_value(PARAM_TEXT, 'Name of the bot', VALUE_OPTIONAL),
                'prompt' => new external_value(PARAM_RAW, 'Prompt sent to the bot', VALUE_OPTIONAL),
                'content' => new external_value(PARAM_RAW, 'Response from LLM'),
                'timestamp' => new external_value(PARAM_INT, 'Timestamp of the response'),

            )
        );
    }

    /****** Get chat history ******/
    /**
     *
     * /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function chat_history_parameters()
    {
        return new external_function_parameters(
            array(
                'chat_id' => new external_value(PARAM_RAW, 'ID of the chat session', false, ''),
            )
        );
    }
    /**
     * @param $id
     * @return true
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function chat_history($chat_id)
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::chat_history_parameters(), array(
                'chat_id' => $chat_id,
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        // Get chat history
        $history_response = criabot::chat_history($params['chat_id']);
        // Convert all objects to arrays recursively
        $history = json_encode($history_response);
        $data = [
            'status' => $history_response->status,
            'code' => $history_response->code,
            'chat_id' => $params['chat_id'],
            'history' => $history
        ];
        return $data;
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function chat_history_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'HTTP status code'),
                'code' => new external_value(PARAM_TEXT, 'Response code'),
                'chat_id' => new external_value(PARAM_RAW, 'ID of the chat session', VALUE_OPTIONAL),
                'history' => new external_value(PARAM_RAW, 'Chat history in JSON format'),
            )
        );
    }



    /****** Check to see if BOT exists ******/
    /**
     *
     * /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function bot_exists_parameters()
    {
        return new external_function_parameters(
            array(
                'bot_id' => new external_value(PARAM_INT, 'ID of the bot being used', false, 0),
                'bot_api_key' => new external_value(PARAM_RAW, 'BOT api key', false, ''),
            )
        );
    }

    /**
     * @param $id
     * @return true
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function bot_exists($bot_id, $bot_api_key)
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::bot_exists_parameters(), array(
                'bot_id' => $bot_id,
                'bot_api_key' => $bot_api_key
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        // Check if bot exists
        if ($bot = $DB->get_record('local_cria_bot', array('id' => $bot_id))) {
            if ($intent = $DB->get_record('local_cria_intents', $params)) {
                return 200;
            } else {
                return 401;
            }
        } else {
            return 404;
        }

    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function bot_exists_returns()
    {
        return new external_value(PARAM_INT, 'Int');
    }
}
