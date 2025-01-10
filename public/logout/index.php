<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

$is_logged_in->logout();
$page = new \Template(config: $config);
$page->setTemplate(template_file: "logout/index.tpl.php");
$page->echoToScreen();
