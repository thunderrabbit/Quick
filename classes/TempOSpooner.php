<?php

class TempOSpooner
{
    private static $gitStatusCache = null;
    private static $gitStatusCacheTime = 0;

    /**
     * @param int $debugLevel
     */
    public function __construct(
        private int $debugLevel = 0,
    ) {
    }

    public function addFileToGit($filePath): bool
    {
        $maxRetries = 10; // 回
        $retryDelay = 1;  // 秒

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                    // Add the file to the git index
                    if($this->debugLevel > 5) {
                        echo "<p>Adding $filePath to git repository\n</p>";
                    }
                    // apparently, exec should not have named parameters (`command`, `output`)
                    $returnVar = exec("git add " . escapeshellarg(arg: $filePath), $output);
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

    public function commitChanges(string $commitMessage): bool
    {
        $maxRetries = 10; // 回
        $retryDelay = 1;  // 秒

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Commit the changes
                if($this->debugLevel > 4) {
                    echo "<p>Committing changes\n</p>";
                }
                // apparently, exec should not have named parameters (`command`, `output`)
                $returnVar = exec("git commit -m '$commitMessage'", $output);
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

    public function pushChangesToCurrentBranch(): bool
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
                // apparently, exec should not have named parameters (`command`, `output`)
                exec($command, $output, $resultCode);

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
        // apparently, exec should not have named parameters (`command`, `output`)
        exec("git log -25 --pretty=format:'%h %s %d'", $output);
        return implode(separator: "\n", array: $output);
    }

    public function getGitStatus(): string
    {
        $cacheLifetime = 10; // seconds - cache for 10 seconds to reduce resource usage

        // Return cached result if still valid
        if (self::$gitStatusCache !== null &&
            (time() - self::$gitStatusCacheTime) < $cacheLifetime) {
            if ($this->debugLevel > 2) {
                echo "<p>Using cached git status</p>";
            }
            return self::$gitStatusCache;
        }

        // Try lightweight check first
        $result = $this->getGitStatusLight();

        // Cache the result
        self::$gitStatusCache = $result;
        self::$gitStatusCacheTime = time();

        if ($this->debugLevel > 2) {
            echo "<p>Fresh git status cached</p>";
        }

        return $result;
    }

    private function getGitStatusLight(): string
    {
        $output = [];
        $returnCode = 0;

        // Check for untracked files first
        exec("timeout 5s /usr/bin/git ls-files --others --exclude-standard 2>/dev/null", $output, $returnCode);

        if ($returnCode === 124) {
            return "Git status check timed out (server busy)";
        }

        $hasUntrackedFiles = ($returnCode === 0 && !empty($output));

        // Check if working tree is clean (no unstaged changes)
        exec("timeout 5s /usr/bin/git diff --quiet 2>/dev/null", $output, $returnCode);

        if ($returnCode === 124) {
            return "Git status check timed out (server busy)";
        }

        $hasUnstagedChanges = ($returnCode === 1);

        // Check if index is clean (no staged changes)
        exec("timeout 5s /usr/bin/git diff --cached --quiet 2>/dev/null", $output, $stagedReturnCode);

        if ($stagedReturnCode === 124) {
            return "Git status check timed out (server busy)";
        }

        $hasStagedChanges = ($stagedReturnCode === 1);

        // Return appropriate status message
        if ($hasUntrackedFiles && ($hasUnstagedChanges || $hasStagedChanges)) {
            return "New files and other changes present.";
        } elseif ($hasUntrackedFiles) {
            return "New untracked files present.";
        } elseif ($hasStagedChanges) {
            return "Changes staged for commit.";
        } elseif ($hasUnstagedChanges) {
            return "Uncommitted changes present.";
        } else {
            return "All changes committed.";
        }
    }

    public function pullLatestChanges(): array
    {
        $maxRetries = 3;
        $retryDelay = 2; // seconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                if ($this->debugLevel > 3) {
                    echo "<p>Attempting git pull (attempt $attempt)</p>";
                }

                $output = [];
                $returnCode = 0;

                // Use timeout to prevent hanging on shared hosting
                $command = "timeout 30s /usr/bin/git pull origin 2>&1";
                exec($command, $output, $returnCode);

                if ($returnCode === 124) {
                    throw new Exception("Git pull timed out (server busy)");
                }

                if ($returnCode !== 0) {
                    $errorOutput = implode("\n", $output);
                    throw new Exception("Git pull failed: " . $errorOutput);
                }

                $result = implode("\n", $output);

                if ($this->debugLevel > 2) {
                    echo "<p>Git pull successful</p>";
                }

                // Clear git status cache since repository state may have changed
                self::$gitStatusCache = null;
                self::$gitStatusCacheTime = 0;

                return [
                    'success' => true,
                    'message' => 'Successfully pulled latest changes',
                    'output' => $result
                ];

            } catch (Exception $e) {
                if ($this->debugLevel > 0) {
                    echo "<p>Pull attempt $attempt failed: " . $e->getMessage() . "</p>";
                }

                if ($attempt == $maxRetries) {
                    return [
                        'success' => false,
                        'message' => 'Failed to pull after ' . $maxRetries . ' attempts: ' . $e->getMessage(),
                        'output' => ''
                    ];
                }

                sleep($retryDelay);
            }
        }

        return [
            'success' => false,
            'message' => 'Unexpected error in pullLatestChanges',
            'output' => ''
        ];
    }

    public function checkIfPullNeeded(): bool
    {
        try {
            $output = [];
            $returnCode = 0;

            // Fetch latest refs without merging
            exec("timeout 10s /usr/bin/git fetch origin 2>/dev/null", $output, $returnCode);

            if ($returnCode !== 0) {
                if ($this->debugLevel > 1) {
                    echo "<p>Could not fetch to check for updates</p>";
                }
                return false; // Can't determine, assume no pull needed
            }

            // Check if local branch is behind remote
            exec("timeout 5s /usr/bin/git rev-list HEAD..origin/master --count 2>/dev/null", $output, $returnCode);

            if ($returnCode === 0 && !empty($output) && intval($output[0]) > 0) {
                return true; // There are commits to pull
            }

            return false;

        } catch (Exception $e) {
            if ($this->debugLevel > 1) {
                echo "<p>Error checking if pull needed: " . $e->getMessage() . "</p>";
            }
            return false;
        }
    }
}

