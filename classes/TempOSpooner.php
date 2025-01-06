<?php

class TempOSpooner
{
    public function __construct()
    {
    }

    /**
     * Find date of current HEAD and create a date type based on strings like 2025-01-01 20:58:05 -0800
     * @return MrBranch
     */
    private function getMrBranchOfCurrentHEAD(): MrBranch
    {
        // Check the current branch
        echo "<p>Checking current branch\n</p>";
        exec("git rev-parse --abbrev-ref HEAD", $currentBranchOutput);
        $currentBranch = trim(implode("\n", $currentBranchOutput));

        // Get the date of the current HEAD
        echo "<p>Getting the date of the current HEAD ($currentBranch)\n</p>";
        exec(command: "git show -s --format=%ci HEAD", output: $dateOutput);
        $date = trim(string: implode(separator: "\n", array: $dateOutput));
        echo "<p>Date of current HEAD: $date\n</p>";
        return new MrBranch(branchName: $currentBranch, latestCommit: new DateTime(datetime: $date));
    }

    private function getOntoCorrectLatestBranch(): void
    {
        $probablyTempBranch = $this->getMrBranchOfCurrentHEAD();

        $onMasterBranch = false;
        // Check if the current branch starts with 'tempo'
        echo "<p>Checking if $probablyTempBranch is actually a temp branch\n</p>";
        if (strpos($probablyTempBranch, needle: 'tempo') === 0) {
            echo "<p>Branch $probablyTempBranch starts with 'tempo'.\n</p>";
        } elseif (strpos(haystack: $probablyTempBranch, needle: 'master') === 0) {
            echo "<p>We are on the master branch already.";
            $onMasterBranch = true;
        } else {
            throw new Exception(message: "You are not on a branch starting with 'tempo' nor 'master'.");
        }

        if (! $onMasterBranch) {
            // Switch to the master branch
            echo "<p>Switching to master branch\n</p>";

            $returnVar = exec("git checkout master", $output);
            if (!str_starts_with(haystack: $returnVar, needle: "Your branch is up to date with 'origin/master'.")) {
                throw new Exception(message: "Failed to switch to master branch: " . implode("\n", $output));
            } else {
                echo "<p>Successfully switched to master branch\n</p>";
                $onMasterBranch = true;
            }
        }

        echo "<p>Checking out master branch and pulling it\n</p>";
        exec("git pull", $output);
        $mrMasterBranch = $this->getMrBranchOfCurrentHEAD();

        echo "<p>Date of master branch: " . $mrMasterBranch->getLatestCommitAsString() . "\n</p>";

        // check if the master branch is newer than the tempo branch
        if($mrMasterBranch->getLatestCommit() < $probablyTempBranch->getLatestCommit()) {
            echo "<p>Master branch is older than tempo branch\n</p>";
            // Switch to the $tempoBranchName branch
            echo "<p>Switching to $probablyTempBranch branch\n</p>";
            echo "<pre>git checkout $probablyTempBranch</pre>";
            $returnVar = exec("git checkout $probablyTempBranch", $output);
            if (!str_starts_with(haystack: $returnVar, needle: "Switched to branch '$probablyTempBranch'")) {
                throw new Exception(message: "Failed to switch to tempo branch: $returnVar");
            } else {
                echo "<p>Successfully switched to tempo branch\n</p>";
            }
        } else {
            echo "<p>Master branch is newer than tempo branch\n</p>";
        }

    }

    private function commitAndPushChanges($filePath, $config): bool
    {
        $maxRetries = 5;
        $retryDelay = 5000; // 5 seconds in milliseconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Add the file to the git index
                echo "<p>Adding $filePath to git repository at $config->post_path_journal\n</p>";
                exec("git add " . escapeshellarg($filePath), $output);

                if (!empty($output)) {
                    throw new Exception("Failed to add file to git: " . implode("\n", $output));
                }

                // Commit the changes
                echo "<p>Committing changes\n</p>";
                $returnVar = exec("git commit -m 'Add new journal entry'", $output);

                if (strpos($returnVar, "create mode 100644") === false) {
                    throw new Exception("Failed to commit changes: " . implode("\n", $output));
                }

                // Push the changes
                echo "<p>Pushing changes to remote\n</p>";
                $returnVar = exec("git push origin master", $output);

                if ($returnVar !== 0) {
                    throw new Exception("Failed to push changes to remote: " . implode("\n", $output));
                }

                return true;
            } catch (Exception $e) {
                if ($attempt == $maxRetries) {
                    throw $e;
                }
                echo "<p>Attempt $attempt failed. Retrying in $retryDelay milliseconds...</p>";
                usleep($retryDelay * 1000); // Convert milliseconds to microseconds
            }
        }

        return false;
    }

    public function newaddAndPushToGit($filePath, $config): string
    {
        // Use the repository path from the config
        $repositoryPath = $config->post_path_journal;

        // Change directory to the repository path
        chdir($repositoryPath);

        // Initialize $output as an empty array
        $output = [];
        $returnVar = 0;

        try {
            $this->getOntoCorrectLatestBranch();


            // $success = $this->commitAndPushChanges($filePath, $config);

            // if ($success) {
            //     echo "<p>Successfully added and pushed changes to branch $newBranchName\n</p>";
            //     return $newBranchName;
            // } else {
            //     throw new Exception("Failed to commit and push changes after multiple attempts");
            // }
        } catch (Exception $e) {
            echo "<p>Error: " . $e->getMessage() . "</p>";
            throw $e;
        }
    }
    public function addAndPushToGit($filePath, $config): string
    {
        // Use the repository path from the config
        $repositoryPath = $config->post_path_journal;

        // Change directory to the repository path
        chdir($repositoryPath);

        // Initialize $output as an empty array
        $output = [];
        $returnVar = 0;

        // git checkout master branch and pull it
        // echo "<p>Checking out master branch and pulling it\n</p>";
        // $returnVar = exec("git checkout master", $output);
        // $returnVar = exec("git pull", $output);


        // // Add the file to the git index
        // echo "<p>Adding $filePath to git repository at $repositoryPath\n</p>";
        // $returnVar = exec(command: "git add " . escapeshellarg(arg: $filePath), output: $output);
        // if (!empty($returnVar)) {
        //     $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
        //     throw new Exception(message: "Failed to add file to git: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
        // }

        // // Commit the changes
        // echo "<p>Committing changes\n</p>";
        // $returnVar = exec(command: "git commit -m 'Add new journal entry'", output: $output);
        // if (!str_starts_with(haystack: $returnVar, needle: " create mode 100644")) {
        //     $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
        //     throw new Exception(message: "Failed >$returnVar< to commit changes: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
        // }

        $this->getOntoCorrectLatestBranch();

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
            if (!str_starts_with(haystack: $returnVar, needle: "Deleted branch $oldBranchName")) {
                throw new Exception("Failed >$returnVar< to delete old branch locally: " . implode("\n", $output));
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
        echo "<pre>git push origin $newBranchName</pre>";
        $returnVar = exec(command: "git push origin $newBranchName", output: $outputOfPush);
        echo "<pre>outputOfPush: " . print_r($outputOfPush, true) . "</pre>";
        if (!empty($returnVar)) {
            echo "Failed >$returnVar< to push changes to remote: " . implode("\n", $output);
            throw new Exception("Failed to push >$returnVar< changes to remote: " . implode("\n", $output));
        } else {
            echo "<p>Changes >$returnVar< successfully pushed to remote for new branch $newBranchName\n</p>";
        }

        return $newBranchName;   // to be used in the message displayed to the user
    }
}
