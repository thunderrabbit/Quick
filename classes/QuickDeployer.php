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

        if($this->debug > 2)
        {
            echo "<pre>Git merge output:\n";
            foreach ($output as $line) {
                echo "$line\n";
            }
            echo "</pre>";
        }

        /**
         * Sample output:
         * Updating b5d81805..d7faf77e
         * Fast-forward
         */
        $successful_merge = false;
        // look for sample output in $output
        if(str_starts_with(haystack: $output[0], needle: "Updating") &&
            str_starts_with(haystack: $output[1], needle: "Fast-forward")
        ) {
            $successful_merge = true;
        }

        if($successful_merge){
            $output = [];
            exec(
                command: "git push",
                output: $output,
                result_code: $resultCode
            );
        }

        if($this->debug > 2)
        {
            echo "<pre>Git push output:\n";
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
