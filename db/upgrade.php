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


defined('MOODLE_INTERNAL') || die();

function xmldb_local_cria_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024062700) {

        // Define table local_cria_question_related to be dropped.
        $table = new xmldb_table('local_cria_question_related');

        // Conditionally launch drop table for local_cria_question_related.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define field related_questions to be added to local_cria_question.
        $table = new xmldb_table('local_cria_question');
        $field = new xmldb_field('related_questions', XMLDB_TYPE_TEXT, null, null, null, null, null, 'keywords');

        // Conditionally launch add field related_questions.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cria savepoint reached.
        upgrade_plugin_savepoint(true, 2024062700, 'local', 'cria');
    }


    return true;
}