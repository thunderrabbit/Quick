<?php
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

if (!isset($mla_request->get['file'])) {
    die("Missing file parameter.");
}

$file = $mla_request->get['file'];
$fullPath = realpath($config->post_path_journal . '/' . $file);

// Security check
if (!$fullPath || !str_starts_with($fullPath, $config->post_path_journal)) {
    die("Invalid file path.");
}

// require_once "{$config->app_path}/classes/QuickParser.php";

$parser = new \QuickParser($fullPath);
$data = $parser->parse();

// Store parsed data in session and redirect to editor
$_SESSION['edit_post_data'] = $data;

header("Location: /poster/");
exit;
