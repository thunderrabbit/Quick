<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

// Use the repository path from the config
$repositoryPath = $config->post_path_journal;

// Change directory to the repository path
chdir(directory: $repositoryPath);

if ($mla_request->post) {
    $deployer = new \QuickDeployer(debug: $mla_request->post['debug_deploy']);
    $deployer->deployMasterBranch();
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
