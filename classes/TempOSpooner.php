<?php

class TempOSpooner
{
    public function __construct()
    {
    }

    /**
     * Find date of current HEAD and create a date type based on strings like 2025-01-01 20:58:05 -0800
     * @return MrBranch
     * TODO allow return null and deal with it in the calling function
     */
    private function getMrBranchOfCurrentHEAD(): MrBranch
    {
        $maxAttempts = 5;
        $delayBetweenAttempts = 5000; // 5 seconds in milliseconds

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Check the current branch
                echo "<p>Checking current branch (Attempt $attempt)</p>";
                exec("git rev-parse --abbrev-ref HEAD", $currentBranchOutput);
                $currentBranch = trim(implode("\n", $currentBranchOutput));

                // Get the date of the current HEAD
                echo "<p>Getting the date of the current branch ($currentBranch)</p>";
                exec("git show -s --format=%ci HEAD", $dateOutput);
                $date = trim(implode("\n", $dateOutput));
                echo "<p>Date of $currentBranch: $date</p>";

                return new MrBranch(
                    branchName: $currentBranch,
                    commitDate: new DateTime($date)
                );
            } catch (Exception $e) {
                if ($attempt == $maxAttempts) {
                    throw $e;
                }
                echo "<p>Attempt $attempt failed. Retrying in $delayBetweenAttempts milliseconds...</p>";
                usleep($delayBetweenAttempts * 1000); // Convert milliseconds to microseconds
            }
        }
    }

    private function switchToThisBranch(string $branch): bool
    {
        $maxAttempts = 5;
        $delayBetweenAttempts = 5000; // 5 seconds in milliseconds

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $output = [];
                $result_code = 0;
                echo "<p>Switching to branch $branch (Attempt $attempt)\n</p>";
                $git_checkout_command = "git checkout $branch";
                echo "<pre>git_checkout_command: $git_checkout_command</pre>";
                $returnVar = exec(
                    command: $git_checkout_command,
                    output: $output,
                    result_code: $result_code
                );

                echo "<pre>output: " . print_r($output, true) . "</pre>";
                echo "<pre>returnVar: $returnVar       result_code = $result_code</pre>";
                if ($result_code == 0 ||
                    strpos($returnVar, "Switched to branch '$branch'") !== false ||
                    strpos($returnVar, "Your branch is up to date with 'origin/master'") !== false
                ) {
                    echo "<p>Successfully switched to branch $branch\n</p>";
                    return true;
                } else {
                    throw new Exception("Unexpected output from git checkout: $returnVar");
                }
            } catch (Exception $e) {
                echo "<p>Attempt $attempt failed. Retrying in $delayBetweenAttempts milliseconds...</p>";
                usleep($delayBetweenAttempts * 1000); // Convert milliseconds to microseconds
            }
        }

        throw new Exception("Failed to switch to branch $branch after $maxAttempts attempts");
    }

    private function createNewBranch(string $newBranchName): bool
    {
        // Create a new random branch name starting with 'tempo'


        // Switch to the new branch
        echo "<p>Creating new branch $newBranchName\n</p>";
        $returnVar = exec(command: "git checkout -b $newBranchName");
        if (!empty($returnVar)) {
            throw new Exception("Failed to create and switch to new branch: " . implode("\n", $output));
        } else {
            echo "<p>Successfully created new branch $newBranchName\n</p>";
            return true;
        }
    }
    private function deleteOldBranchIfItsATempBranch(MrBranch $oldBranch): void
    {
        // Delete the old branch locally and from the remote
        if ($oldBranch->isTempBranch()) {
            // Delete the old branch locally
            echo "<p>Locally deleting old branch named $oldBranch\n</p>";
            $returnVar = exec(command: "git branch -d $oldBranch");
            if (!str_starts_with(haystack: $returnVar, needle: "Deleted branch $oldBranch")) {
                throw new Exception("Failed >$returnVar< to delete old branch locally: " . implode("\n", $output));
            }

            // Delete the old branch from the remote
            echo "<p>Remotely deleting old branch named $oldBranch\n</p>";
            exec(command: "git push origin --delete $oldBranch");
        }
    }
    private function getOntoCorrectLatestBranch(): string
    {
        $probablyTempBranch = $this->getMrBranchOfCurrentHEAD();

        $onMasterBranch = false;
        // Check if the current branch starts with 'tempo'
        echo "<p>Checking if $probablyTempBranch is actually a temp branch\n</p>";
        if (strpos($probablyTempBranch, needle: 'tempo') === 0) {
            echo "<p>Yes; $probablyTempBranch starts with 'tempo'.\n</p>";
        } elseif (strpos(haystack: $probablyTempBranch, needle: 'master') === 0) {
            echo "<p>Nope!  We are on the master branch already.";
            $onMasterBranch = true;
        } else {
            throw new Exception(message: "You are not on a branch starting with 'tempo' nor 'master'.");
        }

        if (! $onMasterBranch) {
            // Switch to the master branch so we can pull it and compare its date to temp
            $onMasterBranch = $this->switchToThisBranch(branch: 'master');
        }

        echo "<p>Checked out master branch and now pulling it\n</p>";
        exec("git pull", $output);
        $mrMasterBranch = $this->getMrBranchOfCurrentHEAD();

        echo "<p>Date of master branch: " . $mrMasterBranch->getBranchDateAsString() . "\n</p>";

        // check if the master branch is newer than the tempo branch
        if($mrMasterBranch->getLatestCommit() >= $probablyTempBranch->getLatestCommit()) {
            echo "<p>Master branch is newer or same age as $probablyTempBranch\n</p>";
            $this->deleteOldBranchIfItsATempBranch(oldBranch: $probablyTempBranch);
            $newBranchName = 'tempo_' . uniqid();
            $created_new_branch = $this->createNewBranch(newBranchName: $newBranchName);
            return $newBranchName;
        } else {
            echo "<p>Master branch is older than tempo branch\n</p>";
            // Switch to the $tempoBranchName branch
            $onTempBranch = $this->switchToThisBranch(branch:$probablyTempBranch);
            if($onTempBranch) {
                return $probablyTempBranch;
            } else {
                throw new Exception("Failed to switch to newer branch $probablyTempBranch");
            }
        }

    }

    private function addFileToGit($filePath): bool
    {
        $maxRetries = 10; // 回
        $retryDelay = 1;  // 秒

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                    // Add the file to the git index
                    echo "<p>Adding $filePath to git repository\n</p>";
                    $returnVar = exec(command: "git add " . escapeshellarg(arg: $filePath), output: $output);
                    if (!empty($returnVar)) {
                        $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
                        throw new Exception(message: "Failed to add file to git: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
                    } else {
                        echo "<p>Successfully added $filePath to git repository\n</p>";
                        return true;
                    }
            } catch (Exception $e) {
                echo "<p>Attempt $attempt failed. Retrying in $retryDelay seconds...</p>";
                if ($attempt == $maxRetries) {
                    return false;
                }
                sleep(seconds: $retryDelay);
            }
        }
        return false;
    }

    private function commitChanges(string $commitMessage): bool
    {
        $maxRetries = 10; // 回
        $retryDelay = 1;  // 秒

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Commit the changes
                echo "<p>Committing changes\n</p>";
                $returnVar = exec(command: "git commit -m '$commitMessage'", output: $output);
                if (!str_starts_with(haystack: $returnVar, needle: " create mode 100644")) {
                    $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
                    throw new Exception(message: "Failed >$returnVar< to commit changes: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
                }
                return true;
            } catch (Exception $e) {
                echo "<p>Attempt $attempt failed. Retrying in $retryDelay seconds...</p>";
                if ($attempt == $maxRetries) {
                    return false;
                }
                sleep(seconds: $retryDelay);
            }
        }
        return false;
    }

    private function pushChanges(string $branchName): bool
    {
        $maxRetries = 3; // 回
        $retryDelay = 1;  // 秒

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Push the changes
                echo "<p>Pushing changes to remote\n</p>";
                $command = "git push --set-upstream origin $branchName";
                echo "<pre>command: $command</pre>";
                exec(command: $command, output: $output, result_code: $resultCode);

                if ($resultCode !== 0) {
                    throw new Exception("Failed to push changes to remote: " . implode("\n", $output));
                }

                return true;
            } catch (Exception $e) {
                echo "<p>Attempt $attempt failed. Retrying in $retryDelay seconds...</p>";
                if ($attempt == $maxRetries) {
                    return false;
                }
                sleep(seconds: $retryDelay);
            }
        }

        return false;
    }


    public function addAndPushToGit($filePath, $config): string
    {
        // Use the repository path from the config
        $repositoryPath = $config->post_path_journal;

        // Change directory to the repository path
        chdir($repositoryPath);

        try {
            $newBranchName = $this->getOntoCorrectLatestBranch();

            $success =
                $this->addFileToGit(filePath: $filePath) &&
                $this->commitChanges(commitMessage: "happy new year") &&
                $this->pushChanges(branchName: $newBranchName);

            if ($success) {
                echo "<p>Successfully added and pushed changes to branch $newBranchName\n</p>";
                return $newBranchName;
            } else {
                throw new Exception("Failed to commit and push changes after multiple attempts");
            }
        } catch (Exception $e) {
            echo "<p>Error: " . $e->getMessage() . "</p>";
            throw $e;
        }
    }
}
