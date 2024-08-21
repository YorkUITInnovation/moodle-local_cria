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

    if ($oldversion < 2024070902) {

        // Define field lang to be dropped from local_cria_files.
        $table = new xmldb_table('local_cria_files');
        $field = new xmldb_field('lang');

        // Conditionally launch drop field lang.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }


        // Define field faculty to be dropped from local_cria_files.
        $table = new xmldb_table('local_cria_files');
        $field = new xmldb_field('faculty');

        // Conditionally launch drop field faculty.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field program to be dropped from local_cria_files.
        $table = new xmldb_table('local_cria_files');
        $field = new xmldb_field('program');

        // Conditionally launch drop field program.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field indexed to be added to local_cria_files.
        $table = new xmldb_table('local_cria_files');
        $field = new xmldb_field('indexed', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'keywords');

        // Conditionally launch add field indexed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Cria savepoint reached.
        upgrade_plugin_savepoint(true, 2024070902, 'local', 'cria');
    }

//    if ($oldversion < 20240701000) {
//
//        // Define field lang to be dropped from local_cria_files.
//        $table = new xmldb_table('local_cria_files');
//        $field = new xmldb_field('lang');
//
//        // Conditionally launch drop field lang.
//        if ($dbman->field_exists($table, $field)) {
//            $dbman->drop_field($table, $field);
//        }
//
//        // Define field faculty to be dropped from local_cria_files.
//        $table = new xmldb_table('local_cria_files');
//        $field = new xmldb_field('faculty');
//
//        // Conditionally launch drop field faculty.
//        if ($dbman->field_exists($table, $field)) {
//            $dbman->drop_field($table, $field);
//        }
//
//        // Define field program to be dropped from local_cria_files.
//        $table = new xmldb_table('local_cria_files');
//        $field = new xmldb_field('program');
//
//        // Conditionally launch drop field program.
//        if ($dbman->field_exists($table, $field)) {
//            $dbman->drop_field($table, $field);
//        }
//
//        // Define field parsingstrategy to be added to local_cria_files.
//        $table = new xmldb_table('local_cria_files');
//        $field = new xmldb_field('parsingstrategy', XMLDB_TYPE_CHAR, '50', null, null, null, 'GENERIC', 'intent_id');
//
//        // Conditionally launch add field parsingstrategy.
//        if (!$dbman->field_exists($table, $field)) {
//            $dbman->add_field($table, $field);
//        }
//
//        // Cria savepoint reached.
//        upgrade_plugin_savepoint(true, 20240701000, 'local', 'cria');
//    }
//
//    if ($oldversion < 20240701001) {
//
//        // Define field error_message to be added to local_cria_files.
//        $table = new xmldb_table('local_cria_files');
//        $field = new xmldb_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null, 'keywords');
//
//        // Conditionally launch add field error_message.
//        if (!$dbman->field_exists($table, $field)) {
//            $dbman->add_field($table, $field);
//        }
//
//        // Cria savepoint reached.
//        upgrade_plugin_savepoint(true, 20240701001, 'local', 'cria');
//    }
//
//    if ($oldversion < 20240701200) {
//
//        // Define field debugging to be added to local_cria_bot.
//        $table = new xmldb_table('local_cria_bot');
//        $field = new xmldb_field('debugging', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'bot_locale');
//
//        // Conditionally launch add field debugging.
//        if (!$dbman->field_exists($table, $field)) {
//            $dbman->add_field($table, $field);
//        }
//
//
//        // Define field related_prompts to be added to local_cria_bot.
//        $table = new xmldb_table('local_cria_bot');
//        $field = new xmldb_field('related_prompts', XMLDB_TYPE_TEXT, null, null, null, null, null, 'bot_locale');
//
//        // Conditionally launch add field related_prompts.
//        if (!$dbman->field_exists($table, $field)) {
//            $dbman->add_field($table, $field);
//        }
//
//
//        // Cria savepoint reached.
//        upgrade_plugin_savepoint(true, 20240701200, 'local', 'cria');
//    }
//
//    if ($oldversion < 20240701600) {
//
//        // Changing precision of field top_k on table local_cria_bot to (4).
//        $table = new xmldb_table('local_cria_bot');
//        $field = new xmldb_field('top_k', XMLDB_TYPE_INTEGER, '4', null, null, null, '50', 'top_p');
//
//        // Launch change of precision for field top_k.
//        $dbman->change_field_precision($table, $field);
//
//
//        // Changing the default of field top_k on table local_cria_bot to 50.
//        $table = new xmldb_table('local_cria_bot');
//        $field = new xmldb_field('top_k', XMLDB_TYPE_INTEGER, '4', null, null, null, '50', 'top_p');
//
//        // Launch change of default for field top_k.
//        $dbman->change_field_default($table, $field);
//
//        // Cria savepoint reached.
//        upgrade_plugin_savepoint(true, 20240701600, 'local', 'cria');
//    }
//
//    if ($oldversion < 20240701900) {
//
//        // Define field nodes to be added to local_cria_files.
//        $table = new xmldb_table('local_cria_files');
//        $field = new xmldb_field('nodes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'error_message');
//
//        // Conditionally launch add field nodes.
//        if (!$dbman->field_exists($table, $field)) {
//            $dbman->add_field($table, $field);
//        }
//
//        // Cria savepoint reached.
//        upgrade_plugin_savepoint(true, 20240701900, 'local', 'cria');
//    }
//    if ($oldversion < 20240702705) {
//
//        // Define field indexed to be added to local_cria_files.
//        $table = new xmldb_table('local_cria_files');
//        $field = new xmldb_field('indexed', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'parsingstrategy');
//
//        // Conditionally launch add field indexed.
//        if (!$dbman->field_exists($table, $field)) {
//            $dbman->add_field($table, $field);
//        }
//
//        // Cria savepoint reached.
//        upgrade_plugin_savepoint(true, 20240702705, 'local', 'cria');
//    }

    if ($oldversion < 2024080900) {

        // Define field llm_generate_related_prompts to be added to local_cria_bot.
        $table = new xmldb_table('local_cria_bot');
        $field = new xmldb_field('llm_generate_related_prompts', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'related_prompts');

        // Conditionally launch add field llm_generate_related_prompts.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field ms_app_id to be added to local_cria_bot.
        $table = new xmldb_table('local_cria_bot');
        $field = new xmldb_field('ms_app_id', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'llm_generate_related_prompts');

        // Conditionally launch add field ms_app_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field ms_app_password to be added to local_cria_bot.
        $table = new xmldb_table('local_cria_bot');
        $field = new xmldb_field('ms_app_password', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'ms_app_id');

        // Conditionally launch add field ms_app_password.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field integrations_no_context_reply to be added to local_cria_bot.
        $table = new xmldb_table('local_cria_bot');
        $field = new xmldb_field('integrations_no_context_reply', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'ms_app_password');

        // Conditionally launch add field integrations_no_context_reply.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field integration_first_email_only to be added to local_cria_bot.
        $table = new xmldb_table('local_cria_bot');
        $field = new xmldb_field('integrations_first_email_only', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'integrations_no_context_reply');

        // Conditionally launch add field integration_first_email_only.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cria savepoint reached.
        upgrade_plugin_savepoint(true, 2024080900, 'local', 'cria');
    }
    if ($oldversion < 2024080901) {

        // Define field integrations_disclaimer_text to be added to local_cria_bot.
        $table = new xmldb_table('local_cria_bot');
        $field = new xmldb_field('integrations_disclaimer_text', XMLDB_TYPE_CHAR, '150', null, null, null, null, 'integration_first_email_only');

        // Conditionally launch add field integrations_disclaimer_text.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cria savepoint reached.
        upgrade_plugin_savepoint(true, 2024080901, 'local', 'cria');
    }

    if ($oldversion < 2024081002) {

        // Define field bot_trust_warning to be added to local_cria_bot.
        $table = new xmldb_table('local_cria_bot');
        $field = new xmldb_field('bot_trust_warning', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'integrations_disclaimer_text');

        // Conditionally launch add field bot_trust_warning.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cria savepoint reached.
        upgrade_plugin_savepoint(true, 2024081002, 'local', 'cria');
    }

    if ($oldversion < 2024082100) {

        // Define field other to be added to local_cria_logs.
        $table = new xmldb_table('local_cria_logs');
        $field = new xmldb_field('other', XMLDB_TYPE_TEXT, null, null, null, null, null, 'ip');

        // Conditionally launch add field other.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cria savepoint reached.
        upgrade_plugin_savepoint(true, 2024082100, 'local', 'cria');
    }


    return true;
}