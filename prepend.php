<?php

require_once __DIR__ . '/classes/Mlaphp/Autoloader.php';
// create autoloader instance and register the method with SPL
$autoloader = new \Mlaphp\Autoloader();
spl_autoload_register(array($autoloader, 'load'));

function print_rob($object, $exit = true)
{
    echo ("<pre>");
    if (is_object($object) && method_exists($object, "toArray")) {
        echo "ResultSet => " . print_r($object->toArray(), true);
    } else {
        print_r($object);
    }
    echo ("</pre>");
    if ($exit) {
        exit;
    }
}

$config = new \Config();
echo "Prepend.php loaded.";
echo "<br>" . $config->dbHost;

$mla_database = \Base::getDB($config);

