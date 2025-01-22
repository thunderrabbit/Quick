<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

// Use the repository path from the config
$repositoryPath = $config->post_path_journal;

// Change directory to the repository path
chdir(directory: $repositoryPath);

if ($mla_request->post) {
    $mrBranchFactory = new MrBranchFactory($mla_request->post["debug_deploy"]);
    $currentMrBranch = $mrBranchFactory->getMrBranchOfCurrentHEAD();

    $expectedBranchName = $mla_request->post['branch'];
    // confirm we are on the correct branch
    if ($currentMrBranch->getBranchName() != $expectedBranchName) {
        throw new Exception(
            message: "You are not on $expectedBranchName, but on " . $currentMrBranch->getBranchName()
        );
    }
    $deployer = new \QuickDeployer(debug: $mla_request->post['debug_deploy']);
    $okay = $deployer->mergeMasterToBranch(newBranchName: $expectedBranchName);
    if($okay)
    {
    }
}

$page = new \Template(config: $config);
if(isset($post_path))
{
    $page->set(name: "post_path", value: $post_path);
}
$page->setTemplate(template_file: "poster/index.tpl.php");
$page->set(name: "show_deploy", value: false);  //no need to deploy after we deploy
$page->set(name: "text", value: "");
$page->echoToScreen();
