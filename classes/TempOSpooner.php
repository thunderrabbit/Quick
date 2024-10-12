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

        echo "Adding $filePath to git repository at $repositoryPath\n";
        // Add the file to the git index
        exec(command: "git add " . escapeshellarg(arg: $filePath), output: $output, result_code: $returnVar);
        if ($returnVar !== 0) {
            $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
            throw new Exception(message: "Failed to add file to git: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
        }

        // Commit the changes
        exec(command: "git commit -m 'Add new journal entry'", output: $output, result_code: $returnVar);
        if ($returnVar !== 0) {
            $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
      throw new Exception(message: "Failed to commit changes: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
        }

        // Check the current branch
        exec("git rev-parse --abbrev-ref HEAD", $currentBranchOutput, $returnVar);
        $currentBranch = trim(implode("\n", $currentBranchOutput));

        // Check if the current branch starts with 'tempo'
        if (strpos($currentBranch, 'tempo') === 0) {
            $oldBranchName = $currentBranch;
        }

        // Create a new random branch name starting with 'tempo'
        $newBranchName = 'tempo_' . uniqid();

        // Switch to the new branch
        exec("git checkout -b " . escapeshellarg($newBranchName), $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Failed to create and switch to new branch: " . implode("\n", $output));
        }

        // Delete the old branch locally and from the remote
        if (isset($oldBranchName)) {
            // Delete the old branch locally
            exec("git branch -d " . escapeshellarg($oldBranchName), $output, $returnVar);
            if ($returnVar !== 0) {
                throw new Exception("Failed to delete old branch locally: " . implode("\n", $output));
            }

            // Delete the old branch from the remote
            exec("git push origin --delete " . escapeshellarg($oldBranchName), $output, $returnVar);
            if ($returnVar !== 0) {
                throw new Exception("Failed to delete old branch from remote: " . implode("\n", $output));
            }
        }

        // Push the changes to the new branch
        exec("git push origin " . escapeshellarg($newBranchName), $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Failed to push changes to remote: " . implode("\n", $output));
        }
    }
}



