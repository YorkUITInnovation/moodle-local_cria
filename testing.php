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


require_once('../../config.php');
require_once('lib.php');


use local_cria\markitdown;

// CHECK And PREPARE DATA
global $CFG, $OUTPUT, $SESSION, $PAGE, $DB, $COURSE, $USER;

require_login(1, false);
$context = context_system::instance();

$prompt = optional_param('prompt', '', PARAM_TEXT);

\local_cria\base::page(
    new moodle_url('/local/cria/testing.php'),
    get_string('pluginname', 'local_cria'),
    'Testing',
    $context
);


//**************** ******
//*** DISPLAY HEADER ***
//**********************
echo $OUTPUT->header();
$data = json_decode(file_get_contents($CFG->dirroot . '/local/cria/General_07_10_2025.json'), true);
//print_object($data);

$markdown = "";

foreach ($data as $key => $value) {
    if (is_array($value)) {
       $value = (object)$value;
       // Get the name and explode into an array
         $name = explode(' - ', $value->name);
         // Add a markdown header with $name[1] as the title
        $markdown .= "## " . trim($name[1]) . " ##\n\n";
        // Add all example questions as markdown bullet points
        if (isset($value->examples) && is_array($value->examples)) {
            foreach ($value->examples as $question) {
                $markdown .= trim($question) . "\n\n";
            }
        }
        // Add a new line with The response to any of these questions or prompts is:
        $markdown .= "\nThe response to any of the questions above or prompts is: ";
        // Add the answer.
        if (isset($value->answer)) {
            $markdown .= $value->answer . "\n\n";
        }
    }
}
file_put_contents('/var/www/moodledata/temp/markdown_output.md', $markdown);
// Convert the markdown to HTML
$markdown = markdown_to_html($markdown);



echo $markdown;


//**********************
//*** DISPLAY FOOTER ***
//**********************
echo $OUTPUT->footer();