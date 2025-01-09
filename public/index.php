<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

if($is_logged_in->isLoggedIn()){
    $page = new \Template($mla_request, $mla_database, $config);
    $page->set("text", "");  // index.tpl.php expects this
    $page->setTemplate("poster/index.tpl.php");
    $page->echoToScreen();
} else {
    $page = new \Template($mla_request, $mla_database, $config);
    $page->setTemplate("login/index.tpl.php");
    $page->echoToScreen();
}
