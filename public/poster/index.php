<?php

# Prefer to do this via .htaccess, but this works for now
include_once("../../prepend.php");

echo "This page is Poster";
echo "<br>Logged in?";
$page = new \Template($mla_request, $mla_database);
$page->setTemplate("poster/index.tpl.php");
$page->echoToScreen();
echo "This page is Poster";
