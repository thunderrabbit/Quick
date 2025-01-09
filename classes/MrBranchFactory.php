<?php
class MrBranchFactory
{
    private int $debugLevel;

    public function __construct(int $debugLevel)
    {
        $this->debugLevel = $debugLevel;
    }

    /**
     * Find date of current HEAD and create a date type based on strings like 2025-01-01 20:58:05 -0800
     * @return MrBranch
     */
    public function getMrBranchOfCurrentHEAD(): MrBranch
    {
        $currentBranch = $this->getCurrentBranch();
        $commitDate = $this->getCommitDate();

        return new MrBranch(
            branchName: $currentBranch,
            commitDate: new DateTime($commitDate)
        );
    }

    private function getCurrentBranch(): string
    {
        $maxRetries = 10; // 回
        $retryDelay = 1;  // 秒

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                exec("git rev-parse --abbrev-ref HEAD", $currentBranchOutput);
                return trim(implode("\n", $currentBranchOutput));
            } catch (Exception $e) {
                if ($attempt == $maxRetries) {
                    throw $e;
                }
                if ($this->debugLevel > 1) {
                    echo "<p>Attempt $attempt failed to get current branch. Retrying in $retryDelay second</p>";
                }
                sleep(seconds: $retryDelay);
            }
        }

        throw new Exception("Failed to get current branch after $maxRetries attempts");
    }

    private function getCommitDate(): string
    {
        $maxRetries = 10; // 回
        $retryDelay = 1;  // 秒

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                exec("git show -s --format=%ci HEAD", $dateOutput);
                return trim(implode("\n", $dateOutput));
            } catch (Exception $e) {
                if ($attempt == $maxRetries) {
                    throw $e;
                }
                if ($this->debugLevel > 1) {
                    echo "<p>Attempt $attempt failed to get commit date. Retrying in $retryDelay second</p>";
                }
                sleep(seconds: $retryDelay);
            }
        }

        throw new Exception("Failed to get commit date after $maxRetries attempts");
    }
}
