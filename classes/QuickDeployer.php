<?php
class QuickDeployer{
    public function __construct(
        private int $debug,
    ) {
    }

    /**
     * Summary of deployMasterBranch
     * @return bool
     */
    public function deployMasterBranch(): bool
    {
        $output = [];
        exec(
            command: "/home/barefoot_rob/scripts/update_robnugen.com_journal.sh",
            output: $output,
            result_code: $resultCode
        );

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
