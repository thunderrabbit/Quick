<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
include_once "/home/barefoot_rob/quick.robnugen.com/prepend.php";

// Use the repository path from the config
$repositoryPath = $config->post_path_journal;

// Change directory to the repository path
chdir(directory: $repositoryPath);

$success = false;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Instantiate TempOSpooner
    $tempOSpooner = new TempOSpooner(
        debugLevel: 0,
    );

    try {
        // Get git status before committing
        $gitStatusBefore = $tempOSpooner->getGitStatus();

        // Only proceed if there are uncommitted changes
        if ($gitStatusBefore !== "All changes committed.") {
            // Add all files to git
            $tempOSpooner->addFileToGit(filePath: ".");

            // Get the next story word for the commit message
            $storyWord = new NextStoryWord(
                gitLogCommand: "git log -15 --pretty=format:'%s'",
                storyFile: "/home/barefoot_rob/x0x0x0/x0x0x0.txt",
                debugLevel: 0
            );

            // Use the NextStoryWord as the commit message
            $commitMessage = (string)$storyWord;

            // Commit changes
            $tempOSpooner->commitChanges(commitMessage: $commitMessage);

            // Push changes to current branch
            $tempOSpooner->pushChangesToCurrentBranch();

            $success = true;
            $message = "Changes committed successfully.";
        } else {
            $message = "No changes to commit.";
        }
    } catch (Exception $e) {
        $message = "Error committing changes: " . $e->getMessage();
    }
}

// Get current git status
$tempOSpooner = new TempOSpooner(
    debugLevel: 0,
);
$gitStatus = $tempOSpooner->getGitStatus();
$gitLog = $tempOSpooner->getGitLog();

// Redirect back to the poster page with status message
$redirectUrl = "/poster/";
if ($success) {
    $redirectUrl .= "?success=1&message=" . urlencode($message);
} else {
    $redirectUrl .= "?error=1&message=" . urlencode($message);
}

// Redirect to the poster page
header("Location: $redirectUrl");
exit;

