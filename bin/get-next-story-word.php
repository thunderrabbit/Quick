#!/usr/bin/env php
<?php
// /home/barefoot_rob/quick.robnugen.com/bin/get-next-story-word.php
// This script is called via SSH to get the next story word for git commit messages
// Originally run from Lemur13: ssh quick "php quick.robnugen.com/bin/get-next-story-word.php"

// 1. Setup environment manually to avoid login check in prepend.php
// write errors to stderr so stdout stays clean
ini_set("display_errors", "stderr");
error_reporting(E_ALL);

// Define path to app root
$appRoot = "/home/barefoot_rob/quick.robnugen.com";

// Load Autoloader
require_once $appRoot . "/classes/Mlaphp/Autoloader.php";
$autoloader = new \Mlaphp\Autoloader();
// We need to set the base directory for the autoloader if it doesn"t default correctly
spl_autoload_register(array($autoloader, "load"));

// Load Config
chdir($appRoot);
$config = new \Config();

// 2. Change to journal repository
$repositoryPath = $config->post_path_journal;
chdir($repositoryPath);

// 3. Execute git pull
// We do not handle errors here intentionally; we report them to stderr.
$output = [];
$returnCode = 0;
// Using stderr redirection for errors so stdout remains clean for the word
exec("/usr/bin/git pull origin 2>&1", $output, $returnCode);

if ($returnCode !== 0) {
    fwrite(STDERR, "Git pull failed:\n" . implode("\n", $output) . "\n");
    exit(1);
}

// 4. Execute git push (to sync local changes back to remote if any)
$output = [];
$returnCode = 0;
exec("/usr/bin/git push origin 2>&1", $output, $returnCode);

if ($returnCode !== 0) {
    fwrite(STDERR, "Git push failed:\n" . implode("\n", $output) . "\n");
    exit(1);
}

// 5. Output current git hash for verification
// Clear output array before reusing
$output = [];
$returnCode = 0;
exec("/usr/bin/git rev-parse HEAD", $output, $returnCode);
if ($returnCode === 0 && isset($output[0])) {
    echo "HASH:" . $output[0] . "\n";
} else {
    fwrite(STDERR, "Failed to get git hash\n");
    exit(1);
}

// 6. Instantiate NextStoryWord
$leNextStoryWord = new NextStoryWord(
    gitLogCommand: "git log -5 --pretty=format:%s",
    storyFile: $config->storyFile,
    debugLevel: 0
);

// 7. Output word as plain text with label
echo "WORD:" . $leNextStoryWord . "\n";

