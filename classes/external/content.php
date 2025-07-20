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

use local_cria\base;
use local_cria\bot;
use local_cria\intent;
use local_cria\file;
use local_cria\files;
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

class local_cria_external_content extends external_api {
    //**************************** SEARCH USERS **********************

    /*     * ***********************
     * Delete Record
     */

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_parameters() {
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
    public static function delete($id) {
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

        $FILE = new file($id);
        // Delete file from indexing server
        $result = criabot::document_delete($FILE->get_bot_name(), $FILE->get_name());
        // Delete file from database
        $FILE->delete_record();
       return $result->status;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function delete_returns() {
        return new external_value(PARAM_INT, 'return code');
    }

    //**************************** PUBLISH URLS **********************
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function publish_urls_parameters() {
        return new external_function_parameters(
            array(
                'intent_id' => new external_value(PARAM_INT, 'Intent id', false, 0),
                'urls' => new external_value(PARAM_RAW, 'Web page URLs', false, '')
            )
        );
    }

    /**
     * @param $intent_id
     * @param $urls
     * @return string
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function publish_urls($intent_id, $urls) {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::publish_urls_parameters(), array(
                'intent_id' => $intent_id,
                'urls' => $urls
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        // Convert urls to an array of urls seperated by new line
        $urls = explode("\n", $urls);
        $FILES = new files($intent_id);

        $result = $FILES->publish_urls($urls);
        if ($result->status == 200) {
            return $result->status;
        } else {
            $error = 'Status = ' . $result->status .
                '<br> Message =  ' . $result->message;
            return  $error;
        }

    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function publish_urls_returns() {
        return new external_value(PARAM_RAW, 'return code');
    }

    //**************************** Republish all files **********************
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function publish_files_parameters() {
        return new external_function_parameters(
            array(
                'intent_id' => new external_value(PARAM_INT, 'Intent id', false, 0),
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
    public static function publish_files($intent_id) {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::publish_files_parameters(), array(
                'intent_id' => $intent_id
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        $FILES = new files($intent_id);
        $FILES->publish_all_files();

        return true;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function publish_files_returns() {
        return new external_value(PARAM_BOOL, 'True');
    }

    //**************************** Upload a file **********************
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function upload_file_parameters()
    {
        return new external_function_parameters(
            array(
                'intentid' => new external_value(PARAM_INT, 'Intent id'),
                'filename' => new external_value(PARAM_TEXT, 'name of the file'),
                'filecontent' => new external_value(PARAM_RAW, 'Content of the file encoded in base64'),
                'parsingstrategy' => new external_value(PARAM_TEXT, 'Parsing strategy', false, '')
            )
        );
    }

    /**
     * @param $intent_id
     * @param $filename
     * @param $filecontent
     * @return string
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function upload_file($intent_id, $filename, $filecontent, $parsingstrategy = '')
    {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::upload_file_parameters(), array(
                'intentid' => $intent_id,
                'filename' => $filename,
                'filecontent' => $filecontent,
                'parsingstrategy' => $parsingstrategy
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);
        $context = \context_system::instance();
        $INTENT = new intent($intent_id);
        $BOT = new bot($INTENT->get_bot_id());
        $FILE = new file();
        // Create temp directory
        $path = $CFG->dataroot . '/temp/cria/';
        base::create_directory_if_not_exists($path);
        $path .= $intent_id . '/';
        base::create_directory_if_not_exists($path);
        $path .= 'uploads/';
        base::create_directory_if_not_exists($path);
        // Save file to temporary folder
        $file = $path . $filename;
        $file_content = base64_decode($filecontent);
        file_put_contents($file, $file_content);

        // Get moodle file storage
        $fs = get_file_storage();
        // save file to moodle file storage
        $file_record = $fs->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => 'local_cria',
            'filearea' => 'content',
            'keywords' => '',
            'itemid' => $intent_id,
            'filepath' => '/',
            'filename' => $filename,
        ], $file);

        // Get newly created file from moodle file storage
        $moodle_file = $fs->get_file(
            $context->id,
            'local_cria',
            'content',
            $intent_id,
            '/',
            $filename
        );
        // Get file type
        $file_type = $FILE->get_file_type_from_mime_type($moodle_file->get_mimetype());
        // Set parsing strategy
        if (empty($parsingstrategy)) {
            $parsingstrategy = $BOT->get_parse_strategy();
        }

        // Get bot from intent
        $BOT = new bot($INTENT->get_bot_id());
        // Prepare file record paramters
        $content_data = [
            'intent_id' => $intent_id,
            'name' => $filename,
            'file_type' => $file_type,
            'content' => '',
            'indexed' => 0,
            'parsingstrategy' => $parsingstrategy,
            'usermodified' => $USER->id,
            'timemodified' => time(),
            'timecreated' => time(),
        ];

        $new_file_id = $DB->insert_record('local_cria_files', $content_data);
        // Delete temporary file
        unlink($file);
        exec('php ' . $CFG->dirroot . '/local/cria/cli/index_files.php --intentid=' . $intent_id .' > /dev/null 2>&1 &');
        return $new_file_id;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function upload_file_returns()
    {
        return new external_value(PARAM_INT, 'File id');
    }

    /*********************************** Training Status ***********************************/
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function training_status_parameters() {
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
    public static function training_status($id) {
        global $CFG, $USER, $DB, $PAGE;

        //Parameter validation
        $params = self::validate_parameters(self::training_status_parameters(), array(
                'id' => $id
            )
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = \context_system::instance();
        self::validate_context($context);

        $FILE = new file($id);
        return $FILE->get_indexed();
    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function training_status_returns() {
        return new external_value(PARAM_INT, 'Return integer for training status');
    }
}
