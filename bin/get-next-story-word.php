#!/usr/bin/env php
<?php
// bin/get-next-story-word.php
// Called via SSH to decide the next story step for the journal's commit messages.
// Originally run from Lemur13: ssh quick "php quick.robnugen.com/bin/get-next-story-word.php"
//
// Decides ONE step (it does not commit — the caller executes it):
//   HASH:<head sha>
//   ACTION:COMMIT|OPEN|CLOSE|DONE
//   WORD:<word>          (absent for DONE)
//   CHILD:<label>        (OPEN/CLOSE only — the hold-ref label)
//
// Flags (for safe testing off-server):
//   --dry-run            skip the git pull/push sync; just print the step
//   --story=/path        override the story file (else Config->storyFile)
//   --repo=/path         override the journal repo (else Config->post_path_journal)
// With both --story and --repo given, Config is never loaded (testable on Lemur).

ini_set("display_errors", "stderr");   // keep stdout clean for the parseable step
error_reporting(E_ALL);

$appRoot = dirname(__DIR__);            // portable: works on server and on Lemur

require_once $appRoot . "/classes/Mlaphp/Autoloader.php";
$autoloader = new \Mlaphp\Autoloader();
spl_autoload_register(array($autoloader, "load"));
require_once $appRoot . "/classes/X0x0x0StoryParser.php";
require_once $appRoot . "/classes/X0x0x0StoryStepper.php";

// --- args ---
$dryRun = in_array("--dry-run", $argv, true);
$storyOverride = null;
$repoOverride  = null;
foreach ($argv as $a) {
    if (str_starts_with($a, "--story=")) $storyOverride = substr($a, 8);
    if (str_starts_with($a, "--repo="))  $repoOverride  = substr($a, 7);
}

if ($storyOverride !== null && $repoOverride !== null) {
    $storyFile      = $storyOverride;   // no Config needed (used for local dry-run)
    $repositoryPath = $repoOverride;
} else {
    chdir($appRoot);
    $config = new \Config();
    $storyFile      = $storyOverride ?? $config->storyFile;
    $repositoryPath = $repoOverride  ?? $config->post_path_journal;
}

chdir($repositoryPath);

// --- sync (skipped in dry-run so nothing touches the remote) ---
if (!$dryRun) {
    $output = []; $returnCode = 0;
    exec("/usr/bin/git pull origin 2>&1", $output, $returnCode);
    if ($returnCode !== 0) {
        fwrite(STDERR, "Git pull failed:\n" . implode("\n", $output) . "\n");
        exit(1);
    }
    $output = []; $returnCode = 0;
    exec("/usr/bin/git push origin 2>&1", $output, $returnCode);
    if ($returnCode !== 0) {
        fwrite(STDERR, "Git push failed:\n" . implode("\n", $output) . "\n");
        exit(1);
    }
}

// --- HASH (for the caller's sync check) ---
$output = []; $returnCode = 0;
exec("/usr/bin/git rev-parse HEAD", $output, $returnCode);
if ($returnCode === 0 && isset($output[0])) {
    echo "HASH:" . $output[0] . "\n";
} else {
    fwrite(STDERR, "Failed to get git hash\n");
    exit(1);
}

// --- decide the next step (read-only; never commits here) ---
try {
    $parser  = new \X0x0x0StoryParser($storyFile);
    $stepper = new \X0x0x0StoryStepper($repositoryPath, $parser, 15);
    $step    = $stepper->nextStep();
} catch (\Throwable $e) {
    fwrite(STDERR, "Story engine error: " . $e->getMessage() . "\n");
    exit(1);
}

if ($step["done"]) {
    echo "ACTION:DONE\n";
} else {
    echo "ACTION:" . $step["action"] . "\n";
    echo "WORD:"   . $step["word"]   . "\n";
    if (isset($step["child"])) {
        echo "CHILD:" . $step["child"] . "\n";
    }
}
