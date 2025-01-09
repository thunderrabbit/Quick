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

        // Use the repository path from the config
        $repositoryPath = $config->post_path_journal;

        // Change directory to the repository path
        chdir($repositoryPath);

        // Instantiate TempOSpooner without parameters
        $tempOSpooner = new TempOSpooner(
            debugLevel: $mla_request->post['debug']
        );
        $nextStoryWord = new NextStoryWord(
            gitLogCommand: "git log -15 --pretty=format:'%s'",
            storyFile: "/home/barefoot_rob/x0x0x0/x0x0x0.txt",
            debugLevel: $mla_request->post['debug'],
        );

        try {
            // Add and push the saved file to the git branch 'tempospoon'
            $newBranchName = $tempOSpooner->addAndPushToGit(
                filePath: $post_path,
                commitMessage: $nextStoryWord,
            );
            $correctlyMatchedWords = implode(separator: ' ', array: $nextStoryWord->getCorrectlyMatchedWords());
            echo "<br>✅ <b>$nextStoryWord</b> ";  // assumes $i > 0 (meaning we are not at the beginning of the story)
            echo $correctlyMatchedWords;
            echo " ...<br>";
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


