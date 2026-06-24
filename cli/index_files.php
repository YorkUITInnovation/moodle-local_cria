<?php
define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

use local_cria\intent;

$longparams = [
    'help' => false,
    'intentid' => '',
    'fileid' => '',
];

$shortparams = [
    'h' => 'help',
    'i' => 'intentid',
    'f' => 'fileid',
];

list($options, $unrecognized) = cli_get_params($longparams, $shortparams);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
        "Index pending files for an intent.

Options:
-h, --help                    Print out this help
-i, --intentid=intentid       The intent id to index files for
-f, --fileid=fileid           Optional file id; indexes only that pending file

Example:
\$sudo -u www-data /usr/bin/php local/cria/cli/index_files.php --intentid=45
\$sudo -u www-data /usr/bin/php local/cria/cli/index_files.php --intentid=45 --fileid=192
";

    echo $help;
    die;
}

if ($options['intentid'] === '') {
    cli_heading('Index files');
    $intentid = (int) cli_input('Enter intent id');
} else {
    $intentid = (int) $options['intentid'];
}

$fileid = (int) ($options['fileid'] ?? 0);
$INTENT = new intent($intentid);

if ($fileid > 0) {
    $result = $INTENT->index_files($fileid);
    cli_writeln($result ? 'Indexed file ' . $fileid : 'No pending file indexed for id ' . $fileid);
} else {
    $processed = $INTENT->index_pending_files();
    cli_writeln('Indexed ' . $processed . ' pending file(s) for intent ' . $intentid);
}
