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

        exec(command: "git merge $newBranchName");

        return true;
    }
}
