<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

// Use the repository path from the config
$repositoryPath = $config->post_path_journal;

// Change directory to the repository path
chdir(directory: $repositoryPath);

if($is_logged_in->isLoggedIn()){
    // Allow deploy without posting
    $tempOSpooner = new TempOSpooner(
        debugLevel: 0,
    );

    $gitLog = $tempOSpooner->getGitLog();
    $show_deploy = true;
    $page = new \Template(config: $config);

    if (isset($gitLog)) {
        $page->set(name: "gitLog", value: $gitLog);
    }
    $page->set(name: "show_deploy", value: true);  // allow deploy on /
    $page->set(name: "entry_time", value: "");  // index.tpl.php expects this
    $page->set(name: "entry_date", value: "");  // index.tpl.php expects this
    $page->set(name: "entry_title", value: "");  // index.tpl.php expects this
    $page->set(name: "entry_tags", value: "");  // index.tpl.php expects this
    $page->set(name: "text", value: "");  // index.tpl.php expects this
    $page->setTemplate(template_file: "poster/index.tpl.php");
    $page->echoToScreen();
} else {
    $page = new \Template(config: $config);
    $page->setTemplate(template_file: "login/index.tpl.php");
    $page->echoToScreen();
}
