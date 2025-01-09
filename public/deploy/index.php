<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

if ($mla_request->post) {
    $deployer = new \QuickDeployer(debug: $mla_request->post['debug_deploy']);
    $okay = $deployer->pushToBranch(newBranchName: $mla_request->post['branch']);
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
$page->set(name: "text", value: "");
$page->echoToScreen();
