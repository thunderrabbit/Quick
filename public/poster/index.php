<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

// Use the repository path from the config
$repositoryPath = $config->post_path_journal;

// Change directory to the repository path
chdir(directory: $repositoryPath);

if ($mla_request->post) {
    $postifier = new \QuickPoster(debug: $mla_request->post['debug']);
    $okay = $postifier->createPost(config: $config, post_array: $mla_request->post);
    $show_deploy = false;
    if($okay)
    {
        $post_path = $postifier->post_path;
        // remove leading / from post_path
        $post_path = ltrim(string: $post_path, characters: "/");

        // Instantiate TempOSpooner without parameters
        $tempOSpooner = new TempOSpooner(
            debugLevel: $mla_request->post['debug'],
        );

        // Get git status to check for uncommitted changes
        $gitStatus = $tempOSpooner->getGitStatus();

        // We no longer automatically commit changes here
        // Instead, we'll show the git status and provide a commit button

        $gitLog = $tempOSpooner->getGitLog();
        $show_deploy = true;
    }
} else {
    // Allow deploy without posting
    $tempOSpooner = new TempOSpooner(
        debugLevel: $mla_request->get['debug'] ?? 0,
    );

    // Get git status to check for uncommitted changes
    $gitStatus = $tempOSpooner->getGitStatus();

    $gitLog = $tempOSpooner->getGitLog();
    $show_deploy = true;
}

// These will be set via $_SESSION via the parser
$title = "";    // keep set() from crying
$time = ""; // keep set() from crying
$date = ""; // keep set() from crying
$tags = ""; // keep set() from crying
$text = "";

if($mla_request->get)
{
    if($mla_request->get['text'])
    {
        // used by badmin.robnugen.com
        $text = $mla_request->get['text'];
    }
} else if (isset($_SESSION['edit_post_data'])) {
    $editData = $_SESSION['edit_post_data'];      // Grab parsed data
    unset($_SESSION['edit_post_data']);           // Prevent reloading on refresh

    $title = $editData['title'] ?? '';
    $time = $editData['time'] ?? '';
    $date = $editData['date'] ?? '';
    $tags = $editData['tags'] ?? '';
    $text = $editData['post_content'] ?? '';       // Set the `$text` var used by your <textarea>
}

$page = new \Template(config: $config);
if(isset($post_path))
{
    $page->set(name: "post_path", value: $post_path);
}
if (isset($storyWordOutput))
{
    $page->set(name: "storyWordOutput", value: $storyWordOutput);
}
if(isset($gitLog))
{
    $page->set(name: "gitLog", value: $gitLog);
}
if(isset($gitStatus))
{
    $page->set(name: "gitStatus", value: $gitStatus);
}
$page->setTemplate(template_file: "poster/index.tpl.php");
$page->set(name: "entry_title", value: $title);
$page->set(name: "entry_time", value: $time);
$page->set(name: "entry_date", value: $date);
$page->set(name: "entry_tags", value: $tags);
$page->set(name: "text", value: $text);
$page->set(name:"show_deploy", value: $show_deploy);
$page->echoToScreen();

