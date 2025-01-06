<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once("/home/barefoot_rob/quick.robnugen.com/prepend.php");

if ($mla_request->post) {
    $postifier = new \QuickPoster(dbase: $mla_database);
    $okay = $postifier->createPost(config: $config, post_array: $mla_request->post);
    if($okay)
    {
        $post_path = $postifier->post_path;
        // remove leading / from post_path
        $post_path = ltrim(string: $post_path, characters: "/");


        // Instantiate TempOSpooner without parameters
        $tempOSpooner = new TempOSpooner();

        try {
            // Add and push the saved file to the git branch 'tempospoon'
            $newBranchName = $tempOSpooner->newaddAndPushToGit(filePath: $post_path, config: $config);
            echo "File successfully added and pushed to git branch $newBranchName.";
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
$page = new \Template(mla_request: $mla_request, dbase: $mla_database, config: $config);
if(isset($post_path))
{
    $page->set(name: "post_path",value: $post_path);
}
$page->setTemplate(template_file: "poster/index.tpl.php");
$page->set(name: "text", value: $text);
$page->echoToScreen();


