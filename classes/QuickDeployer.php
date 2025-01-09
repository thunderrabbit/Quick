<?php
class QuickDeployer{
    public function __construct(
        private int $debug,
    ) {
    }

    /**
     * Create a post
     * @param array $post_array
     * @return bool true if post was created
     */
    public function pushToBranch(string $newBranchName): bool
    {
        if($this->debug > 2){
            print_rob(object: "inside pushToBranch", exit: false);
            print_rob(object: $newBranchName, exit: false);
        }

        return true;
    }
}
