<?php
define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

use local_cria\intent;

// Define the input options.
$longparams = array(
    'help' => false,
    'intentid' => '',
);

$shortparams = array(
    'h' => 'help',
    'i' => 'intentid',

);

// now get cli options
list($options, $unrecognized) = cli_get_params($longparams, $shortparams);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}
file_put_contents('/var/www/moodledata/temp/intent_log.txt', date('Y-m-d H:i:s') . " - Script executed ". $options['intentid'] . " \n", FILE_APPEND);
if ($options['help']) {
    $help =
        "Index file for an intent.

There are no security checks here because anybody who is able to
execute this file may execute any PHP too.

Options:
-h, --help                    Print out this help
-i, --intentid=intentid       The intent id to index files for

Example:
\$sudo -u www-data /usr/bin/php local/cria/cli/index_files.php -i=45
\$sudo -u www-data /usr/bin/php local/cria/cli/index_files.php --intentid=45
";

    echo $help;
    die;
}

if ($options['intentid'] == '') {
    cli_heading('Index files');
    $prompt = "Enter intent id";
    $intentid = cli_input($prompt);
} else {
    $intentid = $options['intentid'];
}

$INTENT = new intent($intentid);

$INTENT->index_files();