<?php

require_once __DIR__ . '/classes/Mlaphp/Autoloader.php';
// create autoloader instance and register the method with SPL
$autoloader = new \Mlaphp\Autoloader();
spl_autoload_register(array($autoloader, 'load'));

$config = new Config();
echo "Prepend.php loaded.";
echo "<br>" . $config->dbHost;


