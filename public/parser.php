<?php

// Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

if (!isset($mla_request->get['file'])) {
    die("Missing file parameter.");
}

$file = $mla_request->get['file'];
$fullPath = is_string($file) ? \JournalPath::resolve($config->post_path_journal, $file) : null;

// Security check
if ($fullPath === null) {
    die("Invalid file path.");
}

$parser = new \JournalEntryParser($fullPath);
$data = $parser->parse();

// Store parsed data in session and redirect to editor
$_SESSION['edit_post_data'] = $data;

header("Location: /poster/");
exit;
