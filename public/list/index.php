<?php

include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

$page = new \Template(config: $config);

$debug = isset($mla_request->get['debug']) ? (int) $mla_request->get['debug'] : 0;
$year = $mla_request->get['year'] ?? date(format: 'Y');
$month = $mla_request->get['month'] ?? date(format: 'm');

if ($debug >= 1) {
    echo "<div style='background:#eef;padding:10px;margin-bottom:1em;'>";
    echo "<strong>Debug info:</strong><br>";
    echo "Year: $year<br>";
    echo "Month: $month<br>";
}

$lister = new \QuickLister(
    journalRoot: $config->post_path_journal,
    debugLevel: $debug,
);

if ($debug >= 5) {
    print_rob($lister, false);
}

$entries = $lister->listEntries(year: $year, month: $month !== '' ? $month : null);

if ($debug >= 1) {
    echo "Found " . count($entries) . " entries.<br>";
    if ($debug >= 3) {
        print_rob($entries, false);
    }
    echo "</div>";
}

$page->set(name: "year", value: $year);
$page->set(name: "month", value: $month);
$page->set(name: "entries", value: $entries);
$page->setTemplate(template_file: "list/index.tpl.php");
$page->echoToScreen();
