<?php
function autoload($object) {
    $source = '/var/www/html/local/cria/classes/' . str_replace("\\", "//", $object) . '.php';
    require_once($source);
}

spl_autoload_register('autoload');
?>
