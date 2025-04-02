<?php

include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

$page = new \Template(config: $config);

$entries = [];
$year = $mla_request->get['year'] ?? null;
$month = $mla_request->get['month'] ?? null;

if ($year !== null) {
    $lister = new \QuickLister(journalRoot: $config->post_path_journal);
    $entries = $lister->listEntries(year: $year, month: $month);
}

$page->set(name: "entries", value: $entries);
$page->set(name: "year", value: $year);
$page->set(name: "month", value: $month);
$page->setTemplate(template_file: "list/index.tpl.php");
$page->echoToScreen();
