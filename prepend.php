<?php
# write errors to screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/classes/Mlaphp/Autoloader.php';
// create autoloader instance and register the method with SPL
$autoloader = new \Mlaphp\Autoloader();
spl_autoload_register(array($autoloader, 'load'));

$mla_request = new \Mlaphp\Request($GLOBALS);

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

$mla_database = \Base::getDB($config);
$prepend_site_handler = new \SiteHandler($config, $mla_database);

$da_user = new \Data\User($mla_request, $mla_database);  // da_user is the logged in user (stands for \Data\User)
$is_logged_in = new \Mlaphp\IsLoggedIn($mla_request);

if(!$is_logged_in->isLoggedIn()){
    echo "yall aint logged in";
    $page = new \Template($mla_request, $mla_database);
    $page->setTemplate("login/index.tpl.php");
    $page->echoToScreen();
    exit;
} else {
    echo "yall logged in";
    $page = new \Template($mla_request, $mla_database);
    $page->setTemplate("poster/index.tpl.php");
    $page->echoToScreen();
    exit;
}
