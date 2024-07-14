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

use local_cria\bot;
use local_cria\cria;

/**
 * External Web Service Template
 *
 * @package    localwstemplate
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once("$CFG->dirroot/config.php");

class local_cria_external_bot extends external_api
{
    //**************************** SEARCH USERS **********************

    /*     * ***********************
     * Delete Record
     */

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_parameters()
    {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Content id', false, 0)
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
    public static function delete($id)
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::delete_parameters(), array(
                'id' => $id
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);
        $BOT = new bot($id);

        // Delete bot on indexing server
        $BOT = new bot($id);
        $BOT->delete_record();

        return true;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function delete_returns()
    {
        return new external_value(PARAM_INT, 'Boolean');
    }

    /******** Create new bot ************/

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function create_bot_parameters()
    {
        return new external_function_parameters(
            array(
                'name' => new external_value(
                    PARAM_TEXT,
                    'Bot name',
                    false,
                    ''
                ),
                'description' => new external_value(
                    PARAM_TEXT,
                    'Bot description',
                    false,
                    ''
                ),
                'bot_type' => new external_value(
                    PARAM_INT,
                    'Bot type',
                    false,
                    0
                ),
                'bot_system_message' => new external_value(
                    PARAM_TEXT,
                    'Bot type',
                    false,
                    0
                ),
                'model_id' => new external_value(
                    PARAM_INT,
                    'Cria GPT model used',
                    false,
                    0
                ),
                'embedding_id' => new external_value(
                    PARAM_INT,
                    'Bot server',
                    false,
                    0
                ),
                'rerank_model_id' => new external_value(
                    PARAM_INT,
                    'Rerank model ID',
                    false,
                    0
                ),
                'requires_content_prompt' => new external_value(
                    PARAM_INT,
                    'Requires content prompt',
                    false,
                    0
                ),
                'requires_user_prompt' => new external_value(
                    PARAM_INT,
                    'Requires user prompt',
                    false,
                    0
                ),
                'user_prompt' => new external_value(
                    PARAM_TEXT,
                    'User prompt',
                    false,
                    ''
                ),
                'welcome_message' => new external_value(
                    PARAM_RAW,
                    'Welcome message for embedded bot',
                    false,
                    ''
                ),
                'theme_color' => new external_value(
                    PARAM_TEXT,
                    'Hex code for embedded bot color. Default Red',
                    false,
                    '#e31837'
                ),
                'max_tokens' => new external_value(
                    PARAM_INT,
                    'Max tokens',
                    false,
                    4000
                ),
                'temperature' => new external_value(
                    PARAM_FLOAT,
                    'Temperature',
                    false,
                    0.1
                ),
                'top_p' => new external_value(PARAM_FLOAT,
                    'Top p',
                    false,
                    0.1
                ),
                'top_k' => new external_value(
                    PARAM_INT,
                    'Number of similarity nodes retrieved',
                    false, 30
                ),
                'top_n' => new external_value(
                    PARAM_INT,
                    'Top number of nodes returned out of the top_k',
                    false, 10
                ),
                'min_k' => new external_value(
                    PARAM_FLOAT,
                    'Min k',
                    false, 0.6
                ),
                'min_relevance' => new external_value(
                    PARAM_FLOAT,
                    'Min relevance',
                    false,
                    0.8
                ),
                'max_context' => new external_value(
                    PARAM_INT,
                    'Max context',
                    false,
                    120000
                ),
                'no_context_message' => new external_value(
                    PARAM_TEXT,
                    'No context message',
                    false,
                    'Nothing found'
                ),
                'no_context_use_message' => new external_value(
                    PARAM_INT,
                    'Should we use the no context message',
                    false,
                    1
                ),
                'no_context_llm_guess' => new external_value(
                    PARAM_INT,
                    'Should we use the LLM to generate an answer if no context found',
                    false,
                    0
                ),
                'email' => new external_value(
                    PARAM_TEXT,
                    'Email of user should revieve notification if an answer was not found in the knowledgebase',
                    false,
                    0
                ),
                'available_child' => new external_value(
                    PARAM_INT,
                    'Should this bot be available to other bots',
                    false,
                    0
                ),
                'parse_strategy' => new external_value(
                    PARAM_TEXT,
                    'Waht parsing strategy shoudl be used by default? Currently, only two available: GENERIC, ALSYLABUS',
                    false,
                    'GENERIC'
                ),
                'botwatermark' => new external_value(
                    PARAM_INT,
                    'Should we add the Cria watermark to the bot',
                    false,
                    0
                ),
                'title' => new external_value(
                    PARAM_TEXT,
                    'The title of the bot for the embed',
                    false,
                    ''
                ),
                'subtitle' => new external_value(
                    PARAM_TEXT,
                    'The subtitle of the bot for the embed',
                    false,
                    ''
                ),
                'embed_position' => new external_value(
                    PARAM_INT,
                    'The position the embed bot will have on a page',
                    false,
                    1
                ),
                'theme_color' => new external_value(
                    PARAM_TEXT,
                    'The color of the embeded bot',
                    false,
                    '#e31837'
                ),
                'icon_url' => new external_value(
                    PARAM_TEXT,
                    'URL of the icon for the embedded bot',
                    false,
                    ''
                ),
                'bot_locale' => new external_value(
                    PARAM_TEXT,
                    'The locale of the bot',
                    false,
                    'en'
                ),
                'child_bots' => new external_value(
                    PARAM_TEXT,
                    'List of bot name',
                    false,
                    '#e31837'
                ),

            )
        );
    }

    /**
     * @param $name
     * @param $description
     * @param $bot_type
     * @param $bot_system_message
     * @param $model_id
     * @param $embedding_id
     * @param $rerank_model_id
     * @param $requires_content_prompt
     * @param $requires_user_prompt
     * @param $user_prompt
     * @param $welcome_message
     * @param $theme_color
     * @param $max_tokens
     * @param $temperature
     * @param $top_p
     * @param $top_k
     * @param $top_n
     * @param $min_k
     * @param $min_relevance
     * @param $max_context
     * @param $no_context_message
     * @param $no_context_use_message
     * @param $no_context_llm_guess
     * @param $email
     * @param $available_child
     * @param $parse_strategy
     * @param $botwatermark
     * @param $title
     * @param $subtitle
     * @param $embed_position
     * @param $icon_url
     * @param $bot_locale
     * @param $child_bots
     * @return int
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function create_bot(
        $name,
        $description = '',
        $bot_type = 1,
        $bot_system_message = '',
        $model_id = 0,
        $embedding_id = 0,
        $rerank_model_id = 0,
        $requires_content_prompt = 0,
        $requires_user_prompt = 0,
        $user_prompt = '',
        $welcome_message = '',
        $theme_color = '#e31837',
        $max_tokens = 4000,
        $temperature = 0.1,
        $top_p = 0.0,
        $top_k = 30,
        $top_n = 10,
        $min_k = 0.6,
        $min_relevance = 0.8,
        $max_context = 120000,
        $no_context_message = 'Nothing found',
        $no_context_use_message = 1,
        $no_context_llm_guess = 0,
        $email = '',
        $available_child = 0,
        $parse_strategy = 'GENERIC',
        $botwatermark = 0,
        $title = '',
        $subtitle = '',
        $embed_position = 1,
        $icon_url = '',
        $bot_locale = 'en',
        $child_bots = ''
    )
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::create_bot_parameters(), array(
                'name' => $name,
                'description' => $description,
                'bot_type' => $bot_type,
                'bot_system_message' => $bot_system_message,
                'model_id' => $model_id,
                'embedding_id' => $embedding_id,
                'rerank_model_id' => $rerank_model_id,
                'requires_content_prompt' => $requires_content_prompt,
                'requires_user_prompt' => $requires_user_prompt,
                'user_prompt' => $user_prompt,
                'welcome_message' => $welcome_message,
                'theme_color' => $theme_color,
                'max_tokens' => $max_tokens,
                'temperature' => $temperature,
                'top_p' => $top_p,
                'top_k' => $top_k,
                'top_n' => $top_n,
                'min_k' => $min_k,
                'min_relevance' => $min_relevance,
                'max_context' => $max_context,
                'no_context_message' => $no_context_message,
                'no_context_use_message' => $no_context_use_message,
                'no_context_llm_guess' => $no_context_llm_guess,
                'email' => $email,
                'available_child' => $available_child,
                'parse_strategy' => $parse_strategy,
                'botwatermark' => $botwatermark,
                'title' => $title,
                'subtitle' => $subtitle,
                'embed_position' => $embed_position,
                'icon_url' => $icon_url,
                'bot_locale' => $bot_locale,
                'child_bots' => $child_bots
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);
        // Create bot
        $BOT = new bot();
        $id = $BOT->insert_record((object)$params);

        unset($BOT);
        unset($NEW_BOT);

        return $id;
    }

    /**
     * Returns new bot id
     * @return external_description
     */
    public static function create_bot_returns()
    {
        return new external_value(PARAM_INT, 'New bot id');
    }





    /***** Get Prompt *****/

    /**
     * Returns description of method parameters for get_prompt methof
     * @return external_function_parameters
     */
    public static function get_prompt_parameters()
    {
        return new external_function_parameters(
            array(
                'bot_id' => new external_value(PARAM_INT, 'Bot id', false, 0)
            )
        );
    }

    /**
     * @param $bot_id
     * @return string The prompt if there is one available
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_prompt($bot_id)
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::get_prompt_parameters(), array(
                'bot_id' => $bot_id
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);
        $BOT = new bot($bot_id);

        return $BOT->get_user_prompt();
    }

    /**
     * Returns prompt if there is one available
     * @return external_description
     */
    public static function get_prompt_returns()
    {
        return new external_value(PARAM_RAW, 'Prompt if there is one available');
    }

    /***** Get Bot Name *****/
    /**
     * Returns description of method parameters for get_prompt methof
     * @return external_function_parameters
     */
    public static function get_bot_name_parameters()
    {
        return new external_function_parameters(
            array(
                'bot_id' => new external_value(PARAM_INT, 'Bot id', false, 0)
            )
        );
    }

    /**
     * @param $bot_id
     * @return string The prompt if there is one available
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_bot_name($bot_id)
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::get_prompt_parameters(), array(
                'bot_id' => $bot_id
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);
        $BOT = new bot($bot_id);

        return $BOT->get_bot_name();
    }

    /**
     * Returns prompt if there is one available
     * @return external_description
     */
    public static function get_bot_name_returns()
    {
        return new external_value(PARAM_RAW, 'Returns the bot name');
    }
}
