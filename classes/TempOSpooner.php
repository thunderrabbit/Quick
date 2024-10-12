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
        exec(command: "git rev-parse --abbrev-ref HEAD", output: $currentBranchOutput, result_code: $returnVar);
        $currentBranch = trim(string: implode(separator: "\n", array: $currentBranchOutput));
        if ($currentBranch !== 'tempospoon') {
            // Switch to the 'tempospoon' branch
            exec(command: "git checkout tempospoon", output: $output, result_code: $returnVar);
            if ($returnVar !== 0) {
                $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
                throw new Exception(message: "Failed to switch to branch 'tempospoon': " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
            }
        }

        // Push the changes to the remote
        exec(command: "git push origin tempospoon", output: $output, result_code: $returnVar);
        if ($returnVar !== 0) {
            $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
            throw new Exception(message: "Failed to push changes to remote: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
        }
    }
}




