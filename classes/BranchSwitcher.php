<?php
class BranchSwitcher
{
    public function __construct(
        private int $debugLevel
    ) {
    }

    public function switchToThisBranch(string $branch): bool
    {
        $maxAttempts = 5;
        $delayBetweenAttempts = 5000; // 5 seconds in milliseconds

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $output = [];
                $result_code = 0;
                if ($this->debugLevel > 4) {
                    echo "<p>Switching to branch $branch (Attempt $attempt)\n</p>";
                }
                $git_checkout_command = "git checkout $branch";
                if ($this->debugLevel > 5) {
                    echo "<pre>git_checkout_command: $git_checkout_command</pre>";
                }
                $returnVar = exec(
                    command: $git_checkout_command,
                    output: $output,
                    result_code: $result_code
                );

                if ($this->debugLevel > 5) {
                    echo "<pre>output: " . print_r($output, true) . "</pre>";
                    echo "<pre>returnVar: $returnVar       result_code = $result_code</pre>";
                }
                if (
                    $result_code == 0 ||
                    strpos($returnVar, "Switched to branch '$branch'") !== false ||
                    strpos($returnVar, "Your branch is up to date with 'origin/master'") !== false
                ) {
                    if ($this->debugLevel > 4) {
                        echo "<p>Successfully switched to branch $branch\n</p>";
                    }
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
}
