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

/**
 * External Web Service Template
 *
 * @package    localwstemplate
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once("$CFG->dirroot/config.php");

class local_cria_external_cria extends external_api
{

    /***** Get cria configuration

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_config_parameters()
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
    public static function get_config()
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::get_config_parameters(), array()
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        $config = get_config('local_cria');

        $cria_config = [];
        $cria_config[0]['bot_server_api_key'] = $config->bot_server_api_key;
        $cria_config[0]['bot_server_url'] = $config->bot_server_url;
        $cria_config[0]['embedding_server_url'] = $config->criaembed_url;
        return $cria_config;
    }

    /**
     * @return external_single_structure
     */
    public static function get_config_details() {
        $fields = array(
            'bot_server_api_key' => new external_value(PARAM_TEXT, 'CriaBot API Key', false),
            'bot_server_url' => new external_value(PARAM_TEXT, 'CriaBot server url', true),
            'embedding_server_url' => new external_value(PARAM_TEXT, 'CriaEmbed server url', true)
        );
        return new external_single_structure($fields);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_config_returns()
    {
        return new external_multiple_structure(self::get_config_details());
    }

    /***** Get cria configuration

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_cria_availability_parameters()
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
    public static function get_cria_availability()
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::get_cria_availability_parameters(), array()
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        $availability = [];
        $availability[0]['status'] = 200;
        $availability[0]['message'] = '';

        // Check to see if we are in maintenance mode
        $maintenance = $DB->get_record('config', array('name' => 'maintenance_enabled'));
        if ($maintenance->value == 1) {
            $maintenance_message = $DB->get_record('config', array('name' => 'maintenance_message'));
            $availability[0]['status'] = 503;
            $availability[0]['message'] = $maintenance_message->value;
        }

        return $availability;
    }

    /**
     * @return external_single_structure
     */
    public static function get_cria_availability_details() {
        $fields = array(
            'status' => new external_value(PARAM_INT, 'Status: 200 or 503', true),
            'message' => new external_value(PARAM_RAW, 'Message provided by maintenance mode', true),
        );
        return new external_single_structure($fields);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_cria_availability_returns()
    {
        return new external_multiple_structure(self::get_cria_availability_details());
    }
}
