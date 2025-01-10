<?php
class QuickDeployer{
    public function __construct(
        private int $debug,
    ) {
    }

    /**
     * Summary of mergeMasterToBranch
     * @param string $newBranchName
     * @return bool
     */
    public function mergeMasterToBranch(string $newBranchName): bool
    {
        $mrBranchSwitcher = new BranchSwitcher(debugLevel: $this->debug);

        if($this->debug > 2){
            print_rob(object: "inside mergeMasterToBranch", exit: false);
            print_rob(object: $newBranchName, exit: false);
        }

        $mrBranchSwitcher->switchToThisBranch(branch: 'master');

        $output = [];
        exec(
            command: "git merge $newBranchName",
            output: $output,
            result_code: $resultCode
        );

        if ($this->debug > 1) {
            echo "<pre>Git merge output:\n";
            foreach ($output as $line) {
                echo "$line\n";
            }
            echo "</pre>";
        }

        if ($resultCode !== 0) {
            throw new Exception("Failed to merge branch. Return value: $resultCode\nOutput:\n" . implode("\n", $output));
        } else {
            return true;
        }
    }
}
