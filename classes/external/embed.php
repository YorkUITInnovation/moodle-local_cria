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

use local_cria\criaembed;

class local_cria_external_embed extends external_api
{

    /***** Get cria configuration

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function load_session_parameters()
    {
        return new external_function_parameters(
            array(
                'intentid' => new external_value(PARAM_INT, 'Intent id', VALUE_REQUIRED, 0),
                'payload' => new external_value(PARAM_TEXT, 'A json string (not an array) of data', VALUE_OPTIONAL, "{}"),

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
    public static function load_session($intent_id, $payload = "{}")
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::load_session_parameters(),
            array(
                'intentid' => $intent_id,
                'payload' => $payload
            )
        );


        $session = criaembed::sessions_load($intent_id, $payload);
        file_put_contents($CFG->dataroot . '/temp/criaembed.json', json_encode($session, JSON_PRETTY_PRINT));

        return $session;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function load_session_returns()
    {
        return new external_value(PARAM_TEXT, 'Call for the session');
    }
}
