<?php
function autoload($object) {
    print_object($object);
    $source = '/var/www/html/local/cria/classes/' . str_replace("\\", "//", $object) . '.php';
    require_once($source);
}

spl_autoload_register('autoload');
?>
