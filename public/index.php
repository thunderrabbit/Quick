<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once("/home/quill_dh_plasz3gi/quill.plasticaddy.com/prepend.php");

echo "root index.php";
if($is_logged_in->isLoggedIn()){
    echo "<br>Logged in!";
} else {
    echo "<br>Not logged in!";
    $page = new \Template($mla_request, $mla_database);
    $page->setTemplate("index.tpl.php");
    $page->echoToScreen();
}
