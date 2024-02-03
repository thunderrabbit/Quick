<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once("/home/quill_dh_plasz3gi/quill.plasticaddy.com/prepend.php");

if ($mla_request->post) {
    print_rob($mla_request->post, false);
}

$page = new \Template($mla_request, $mla_database);
$page->setTemplate("poster/index.tpl.php");
$page->echoToScreen();
