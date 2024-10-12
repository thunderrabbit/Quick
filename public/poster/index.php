<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once("/home/barefoot_rob/quick.robnugen.com/prepend.php");

if ($mla_request->post) {
    $postifier = new \QuickPoster($mla_database);
    $okay = $postifier->createPost($config, $mla_request->post);
    if($okay)
    {
        $post_path = $postifier->post_path;

        // Instantiate TempOSpooner with the path to the git repository
        $tempOSpooner = new TempOSpooner($post_path_journal);

        try {
            // Add and push the saved file to the git branch 'tempospoon'
            $tempOSpooner->addAndPushToGit($post_path);
            echo "File successfully added and pushed to git branch 'tempospoon'.";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

$text = "";

if($mla_request->get)
{
    if($mla_request->get['text'])
    {
        // used by badmin.robnugen.com
        $text = $mla_request->get['text'];
    }
}
$page = new \Template($mla_request, $mla_database, $config);
if(isset($post_path))
{
    $page->set("post_path",$post_path);
}
$page->setTemplate("poster/index.tpl.php");
$page->set("text", $text);
$page->echoToScreen();
