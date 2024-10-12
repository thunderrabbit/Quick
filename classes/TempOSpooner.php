<?php

class TempOSpooner
{
    public function __construct()
    {
    }


    public function addAndPushToGit($filePath, $config)
    {
        // Use the repository path from the config
        $repositoryPath = $config->post_path_journal;

        // Change directory to the repository path
        chdir($repositoryPath);

        // Add the file to the git index
        exec("git add " . escapeshellarg($filePath), $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Failed to add file to git: " . implode("\n", $output));
        }

        // Commit the changes
        exec("git commit -m 'Add new journal entry'", $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Failed to commit changes: " . implode("\n", $output));
        }

        // Switch to the 'tempospoon' branch
        exec("git checkout tempospoon", $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Failed to switch to branch 'tempospoon': " . implode("\n", $output));
        }

        // Push the changes to the remote
        exec("git push origin tempospoon", $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Failed to push changes to remote: " . implode("\n", $output));
        }
    }
}



