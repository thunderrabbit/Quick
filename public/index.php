<?php

# write errors to screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

# Prefer to do this via .htaccess, but this works for now
include_once("../prepend.php");


echo "Hello World!";
