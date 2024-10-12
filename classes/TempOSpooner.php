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

        // Add the file to the git index
        exec(command: "git add " . escapeshellarg(arg: $filePath), output: $output, result_code: $returnVar);
        if ($returnVar !== 0) {
            throw new Exception(message: "Failed to add file to git: " . implode(separator: "\n", array: $output));
        }

        // Commit the changes
        exec(command: "git commit -m 'Add new journal entry'", output: $output, result_code: $returnVar);
        if ($returnVar !== 0) {
            throw new Exception(message: "Failed to commit changes: " . implode(separator: "\n", array: $output));
        }

        // Switch to the 'tempospoon' branch
        exec(command: "git checkout tempospoon", output: $output, result_code: $returnVar);
        if ($returnVar !== 0) {
            throw new Exception(message: "Failed to switch to branch 'tempospoon': " . implode(separator: "\n", array: $output));
        }

        // Push the changes to the remote
        exec(command: "git push origin tempospoon", output: $output, result_code: $returnVar);
        if ($returnVar !== 0) {
            throw new Exception(message: "Failed to push changes to remote: " . implode(separator: "\n", array: $output));
        }
    }
}



