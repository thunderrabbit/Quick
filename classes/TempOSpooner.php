<?php

class TempOSpooner
{
    public function __construct()
    {
    }


    public function addAndPushToGit($filePath, $config): void
    {
        // Use the repository path from the config
        $repositoryPath = $config->post_path_journal;

        // Change directory to the repository path
        chdir($repositoryPath);

        // Initialize $output as an empty array
        $output = [];
        $returnVar = 0;

        // Add the file to the git index
        echo "<p>Adding $filePath to git repository at $repositoryPath\n</p>";
        $returnVar = exec(command: "git add " . escapeshellarg(arg: $filePath), output: $output);
        if (!empty($returnVar)) {
            $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
            throw new Exception(message: "Failed to add file to git: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
        }

        // Commit the changes
        echo "<p>Committing changes\n</p>";
        $returnVar = exec(command: "git commit -m 'Add new journal entry'", output: $output);
        if (!empty($returnVar)) {
            $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
            throw new Exception(message: "Failed to commit changes: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
        }

        // Check the current branch
        echo "<p>Checking current branch\n</p>";
        $returnVar = exec("git rev-parse --abbrev-ref HEAD", $currentBranchOutput);
        $currentBranch = trim(implode("\n", $currentBranchOutput));

        // Check if the current branch starts with 'tempo'
        echo "<p>Checking if current branch starts with 'tempo'\n</p>";
        if (strpos($currentBranch, 'tempo') === 0) {
            $oldBranchName = $currentBranch;
        }

        // Create a new random branch name starting with 'tempo'
        $newBranchName = 'tempo_' . uniqid();

        // Switch to the new branch
        echo "<p>Switching to new branch $newBranchName\n</p>";
        $returnVar = exec(command: "git checkout -b $newBranchName");
        if (!empty($returnVar)) {
            throw new Exception("Failed to create and switch to new branch: " . implode("\n", $output));
        }

        // Delete the old branch locally and from the remote
        if (isset($oldBranchName)) {
            // Delete the old branch locally
            echo "<p>Locally deleting old branch named $oldBranchName\n</p>";
            $returnVar = exec(command: "git branch -d $oldBranchName");
            if (!empty($returnVar)) {
                throw new Exception("Failed to delete old branch locally: " . implode("\n", $output));
            }

            // Delete the old branch from the remote
            echo "<p>Remotely deleting old branch named $oldBranchName\n</p>";
            $returnVar = exec(command: "git push origin --delete $oldBranchName");
            if (!empty($returnVar)) {
                throw new Exception("Failed to delete old branch $oldBranchName from remote: " . implode("\n", $output));
            }
        }

        // Push the changes to the new branch
        echo "<p>Pushing changes to remote for new branch $newBranchName\n</p>";
        $returnVar = exec(command: "git push origin $newBranchName");
        if (!empty($returnVar)) {
            throw new Exception("Failed to push >$returnVar< changes to remote: " . implode("\n", $output));
        }
    }
}
