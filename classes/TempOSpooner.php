<?php

class TempOSpooner
{
    /**
     * @param int $debugLevel
     */
    public function __construct(
        private int $debugLevel = 0,
    ) {
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
                if ($this->debugLevel > 4) {
                    print_rob(object: $returnVar, exit: false);
                }
                if (!str_starts_with(haystack: $returnVar, needle: " create mode 100644") /* new file */ &&
                    !str_contains(haystack: $returnVar, needle: "changed,")) /* edited file */ {
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

    private function pushChangesToCurrentBranch(): bool
    {
        $maxRetries = 3; // 回
        $retryDelay = 1;  // 秒

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Push the changes
                if($this->debugLevel > 4) {
                    echo "<p>Pushing changes to remote\n</p>";
                }
                $command = "git push";
                if($this->debugLevel > 5) {
                    echo "<pre>command: $command</pre>";
                }
                exec(command: $command, output: $output, result_code: $resultCode);

                if ($resultCode !== 0) {
                    throw new Exception("Failed to push changes to remote: " . implode("\n", $output));
                }
                if ($this->debugLevel > 4) {
                    echo "<p>Pushed changes\n</p>";
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


    public function addAndPushToGit(string $filePath, string $commitMessage): bool
    {
        try {
            if($this->debugLevel > 1) {
                echo "<br>Commit message is $commitMessage\n";
            }
            $success =
                $this->addFileToGit(filePath: $filePath) &&
                $this->commitChanges(commitMessage: $commitMessage) &&
                $this->pushChangesToCurrentBranch();

            if ($success) {
                if($this->debugLevel > 0) {
                    echo "<p>Successfully commited `$commitMessage` to branch master (harddcoddeddd)\n</p>";
                }
                return true;   // getting rid of all branches because commit messages are working well enough
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
