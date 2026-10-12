<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

// Use the repository path from the config
$repositoryPath = $config->post_path_journal;

$debugLevel = intval(value: $_GET['debug']) ?? 0;
if ($debugLevel > 0) {
    echo "<pre>Debug Level: $debugLevel</pre>";
}

// Change directory to the repository path
chdir(directory: $repositoryPath);

if ($is_logged_in->isLoggedIn()) {
    // Handle AJAX pull request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pull') {
        header('Content-Type: application/json');

        $tempOSpooner = new TempOSpooner(debugLevel: $debugLevel);
        $result = $tempOSpooner->pullLatestChanges();

        echo json_encode($result);
        exit;
    }

    // Get the next story word to give me context
    $leNextStoryWord = new NextStoryWord(
        gitLogCount: 5,
        storyFile: $config->storyFile,
        debugLevel: $debugLevel
    );

    $tempOSpooner = new TempOSpooner(
        debugLevel: $debugLevel,
    );

    $gitLog = $tempOSpooner->getGitLog();
    $pullNeeded = $tempOSpooner->checkIfPullNeeded();

    $recentLister = new \JournalEntryLister(
        journalRoot: $config->post_path_journal,
        debugLevel: $debugLevel,
    );
    $recentYear = date(format: 'Y');
    $recentMonth = date(format: 'm');
    $recentEntries = array_slice(
        $recentLister->listEntries(year: $recentYear, month: $recentMonth),
        0,
        10
    );

    $page = new \Template(config: $config);

    if (isset($gitLog)) {
        $page->set(name: "gitLog", value: $gitLog);
    }
    $page->set(name: "entry_time", value: "");  // index.tpl.php expects this
    $page->set(name: "entry_date", value: "");  // index.tpl.php expects this
    $page->set(name: "entry_title", value: "");  // index.tpl.php expects this
    $page->set(name: "entry_tags", value: "");  // index.tpl.php expects this
    $page->set(name: "text", value: "");  // index.tpl.php expects this
    $page->set(name: "leNextStoryWord", value: $leNextStoryWord);
    $page->set(name: "pullNeeded", value: $pullNeeded);
    $page->set(name: "recentEntries", value: $recentEntries);
    $page->set(name: "recentYear", value: $recentYear);
    $page->set(name: "recentMonth", value: $recentMonth);
    $page->setTemplate(template_file: "poster/index.tpl.php");
    $page->echoToScreen();
} else {
    $page = new \Template(config: $config);
    $page->setTemplate(template_file: "login/index.tpl.php");
    $page->echoToScreen();
}
