<?php

# Prefer to do this via .htaccess, but this works for now
include_once("../../prepend.php");

$page = new \Template($mla_request, $mla_database);
$page->setTemplate("login/index.tpl.php");
$page->echoToScreen();
