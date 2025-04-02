<?php

include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

$page = new \Template(config: $config);

$entries = [];
$year = $mla_request->get['year'] ?? date(format: 'Y');
$month = $mla_request->get['month'] ?? '';

if ($year !== '') {
    $lister = new \QuickLister(journalRoot: $config->post_path_journal);
    $entries = $lister->listEntries(year: $year, month: $month !== '' ? $month : null);
}

$page->set(name: "year", value: $year);
$page->set(name: "month", value: $month);
$page->set(name: "entries", value: $entries);
$page->setTemplate(template_file: "list/index.tpl.php");
$page->echoToScreen();
