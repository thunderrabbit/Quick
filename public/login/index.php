<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once("/home/quill_dh_plasz3gi/quill.plasticaddy.com/prepend.php");

if ($is_logged_in->isLoggedIn()) {
    $page = new \Template($mla_request, $mla_database, $config);
    $page->setTemplate("poster/index.tpl.php");
    $page->echoToScreen();
} else {
    $page = new \Template($mla_request, $mla_database, $config);
    $page->setTemplate("login/index.tpl.php");
    $page->echoToScreen();
}
