<?php

class TempOSpooner
{
    public function __construct(
        private int $debugLevel = 0,
    ) {
    }

    private function createNewBranch(string $newBranchName): bool
    {
        // Create a new random branch name starting with 'tempo'
        // Switch to the new branch

        if($this->debugLevel > 5) {
            echo "<p>Creating new branch $newBranchName\n</p>";
        }
        $returnVar = exec(command: "git checkout -b $newBranchName");
        if (!empty($returnVar)) {
            throw new Exception("Failed to create and switch to new branch: " . implode("\n", $output));
        } else {
            if($this->debugLevel > 4) {
                echo "<p>Successfully created new branch $newBranchName\n</p>";
            }
            return true;
        }
    }
    private function deleteOldBranchIfItsATempBranch(MrBranch $oldBranch): void
    {
        // Delete the old branch locally and from the remote
        if ($oldBranch->isTempBranch()) {
            // Delete the old branch locally
            if($this->debugLevel > 4) {
                echo "<p>Locally deleting old branch named $oldBranch\n</p>";
            }
            $returnVar = exec(command: "git branch -d $oldBranch");
            if (!str_starts_with(haystack: $returnVar, needle: "Deleted branch $oldBranch")) {
                throw new Exception("Failed >$returnVar< to delete old branch locally: " . implode("\n", $output));
            }

            // Delete the old branch from the remote
            if($this->debugLevel > 5) {
                echo "<p>Remotely deleting old branch named $oldBranch\n</p>";
            }
            exec(command: "git push origin --delete $oldBranch");
        }
    }
    private function getOntoCorrectLatestBranch(): string
    {
        $mrBranchFactory = new MrBranchFactory(debugLevel: $this->debugLevel);
        $probablyTempBranch = $mrBranchFactory->getMrBranchOfCurrentHEAD();

        $mrBranchSwitcher = new BranchSwitcher(debugLevel: $this->debugLevel);

        $onMasterBranch = false;
        // Check if the current branch starts with 'tempo'
        if($this->debugLevel > 2) {
            echo "<p>Checking if $probablyTempBranch is actually a temp branch\n</p>";
        }
        if (strpos($probablyTempBranch, needle: 'tempo') === 0) {
            if($this->debugLevel > 3) {
                echo "<p>Yes; $probablyTempBranch starts with 'tempo'.\n</p>";
            }
        } elseif (strpos(haystack: $probablyTempBranch, needle: 'master') === 0) {
            if($this->debugLevel > 3) {
                echo "<p>Nope!  We are on the master branch already.";
            }
            $onMasterBranch = true;
        } else {
            throw new Exception(message: "You are not on a branch starting with 'tempo' nor 'master'.");
        }

        if (! $onMasterBranch) {
            // Switch to the master branch so we can pull it and compare its date to temp
            $onMasterBranch = $mrBranchSwitcher->switchToThisBranch(branch: 'master');
        }

        if (! $onMasterBranch) {
            echo "<p>Failed to switch to master branch\n</p>";
            throw new Exception("Failed to switch to master branch");
        }
        if($this->debugLevel > 2) {
            echo "<p>Checked out master branch and now pulling it\n</p>";
        }
        exec("git pull", $output);
        $mrMasterBranch = $mrBranchFactory->getMrBranchOfCurrentHEAD();

        if($this->debugLevel > 3) {
            echo "<p>Date of master branch: " . $mrMasterBranch->getBranchDateAsString() . "\n</p>";
        }

        // check if the master branch is newer than the tempo branch
        if($mrMasterBranch->getLatestCommit() >= $probablyTempBranch->getLatestCommit()) {
            if($this->debugLevel > 2) {
                echo "<p>Master branch is newer or same age as $probablyTempBranch\n</p>";
            }
            $this->deleteOldBranchIfItsATempBranch(oldBranch: $probablyTempBranch);
            $newBranchName = 'tempo_' . uniqid();
            $created_new_branch = $this->createNewBranch(newBranchName: $newBranchName);
            return $newBranchName;
        } else {
            if($this->debugLevel > 2) {
                echo "<p>Master branch is older than tempo branch\n</p>";
            }
            // Switch to the $tempoBranchName branch
            $onTempBranch = $mrBranchSwitcher->switchToThisBranch(branch:$probablyTempBranch);
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
                    if($this->debugLevel > 5) {
                        echo "<p>Adding $filePath to git repository\n</p>";
                    }
                    $returnVar = exec(command: "git add " . escapeshellarg(arg: $filePath), output: $output);
                    if (!empty($returnVar)) {
                        $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
                        throw new Exception(message: "Failed to add file to git: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
                    } else {
                        if($this->debugLevel > 3) {
                            echo "<p>Successfully added $filePath to git repository\n</p>";
                        }
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
                if($this->debugLevel > 4) {
                    echo "<p>Committing changes\n</p>";
                }
                $returnVar = exec(command: "git commit -m '$commitMessage'", output: $output);
                if (!str_starts_with(haystack: $returnVar, needle: " create mode 100644")) {
                    $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
                    throw new Exception(message: "Failed >$returnVar< to commit changes: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
                }
                if ($this->debugLevel > 3) {
                    echo "<p>Committed with commit message $commitMessage\n</p>";
                }
                return true;
            } catch (Exception $e) {
                if($this->debugLevel > 0) {
                    echo "<p>Attempt $attempt failed to commit file. Retrying in $retryDelay seconds...</p>";
                }
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
                if($this->debugLevel > 4) {
                    echo "<p>Pushing changes to remote\n</p>";
                }
                $command = "git push --set-upstream origin $branchName";
                if($this->debugLevel > 5) {
                    echo "<pre>command: $command</pre>";
                }
                exec(command: $command, output: $output, result_code: $resultCode);

                if ($resultCode !== 0) {
                    throw new Exception("Failed to push changes to remote: " . implode("\n", $output));
                }
                if ($this->debugLevel > 4) {
                    echo "<p>Pushed changes to remote branch $branchName\n</p>";
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


    public function addAndPushToGit(string $filePath, string $commitMessage): string
    {
        try {
            $newBranchName = $this->getOntoCorrectLatestBranch();

            if($this->debugLevel > 1) {
                echo "<br>Commit message is $commitMessage\n";
            }
            $success =
                $this->addFileToGit(filePath: $filePath) &&
                $this->commitChanges(commitMessage: $commitMessage) &&
                $this->pushChanges(branchName: $newBranchName);

            if ($success) {
                if($this->debugLevel > 0) {
                    echo "<p>Successfully commited `$commitMessage` to branch $newBranchName\n</p>";
                }
                return $newBranchName;
            } else {
                throw new Exception("Failed to commit and push changes after multiple attempts");
            }
        } catch (Exception $e) {
            echo "<p>Error: " . $e->getMessage() . "</p>";
            throw $e;
        }
    }

    public function getGitLog(): string
    {
        $output = [];
        exec(command: "git log -25 --pretty=format:'%h %s %d'", output: $output);
        return implode(separator: "\n", array: $output);
    }
}
