<?php

# write errors to screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

# Prefer to do this via .htaccess, but this works for now
include_once("../prepend.php");

if($is_logged_in->isLoggedIn()){
    echo "<br>Logged in!";
} else {
    $page = new \Template($mla_request, $mla_database);
    $page->setTemplate("login/index.tpl.php");
    $page->echoToScreen();
}
