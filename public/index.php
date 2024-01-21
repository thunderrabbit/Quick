<?php

# Prefer to do this via .htaccess, but this works for now
include_once("../prepend.php");

echo "root index.php";
if($is_logged_in->isLoggedIn()){
    echo "<br>Logged in!";
} else {
    echo "<br>Not logged in!";
    $page = new \Template($mla_request, $mla_database);
    $page->setTemplate("index.tpl.php");
    $page->echoToScreen();
}
