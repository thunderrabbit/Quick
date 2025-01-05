<?php

class TempOSpooner
{
    public function __construct()
    {
    }

    private function getDateOfCurrentHEAD(): string
    {
        // Get the date of the current HEAD
        echo "<p>Getting the date of the current HEAD\n</p>";
        exec(command: "git show -s --format=%ci HEAD", output: $dateOutput);
        $date = trim(string: implode(separator: "\n", array: $dateOutput));
        echo "<p>Date of current HEAD: $date\n</p>";
        return $date;
    }

    private function getOntoCorrectBranch(): void
    {
        // Check the current branch
        echo "<p>Checking current branch\n</p>";
        exec("git rev-parse --abbrev-ref HEAD", $currentBranchOutput);
        $currentBranch = trim(implode("\n", $currentBranchOutput));

        $onMasterBranch = false;
        // Check if the current branch starts with 'tempo'
        echo "<p>Checking if current branch $currentBranch starts with 'tempo'\n</p>";
        if (strpos($currentBranch, needle: 'tempo') === 0) {
            $tempoBranchName = $currentBranch;
            $dateTempBranch = $this->getDateOfCurrentHEAD();
        } elseif (strpos(haystack: $currentBranch, needle: 'master') === 0) {
            echo "<p>We are on the master branch already.";
            $onMasterBranch = true;
        } else {
            throw new Exception(message: "You are not on a branch starting with 'tempo' nor 'master'.");
        }

        if (! $onMasterBranch) {
            // Switch to the master branch
            echo "<p>Switching to master branch\n</p>";
            exec("git checkout master", $output);
            if (strpos(implode("\n", $output), needle: "Switched to branch 'master'") === false) {
                throw new Exception(message: "Failed to switch to master branch: " . implode("\n", $output));
            } else {
                echo "<p>Successfully switched to master branch\n</p>";
                $onMasterBranch = true;
            }
        }

        echo "<p>Checking out master branch and pulling it\n</p>";
        exec("git pull", $output);
        $dateMasterBranch = $this->getDateOfCurrentHEAD();

        echo "<p>Date of master branch: $dateMasterBranch\n</p>";


    }
    public function addAndPushToGit($filePath, $config): string
    {
        // Use the repository path from the config
        $repositoryPath = $config->post_path_journal;

        // Change directory to the repository path
        chdir($repositoryPath);

        // Initialize $output as an empty array
        $output = [];
        $returnVar = 0;

        // git checkout master branch and pull it
        // echo "<p>Checking out master branch and pulling it\n</p>";
        // $returnVar = exec("git checkout master", $output);
        // $returnVar = exec("git pull", $output);


        // // Add the file to the git index
        // echo "<p>Adding $filePath to git repository at $repositoryPath\n</p>";
        // $returnVar = exec(command: "git add " . escapeshellarg(arg: $filePath), output: $output);
        // if (!empty($returnVar)) {
        //     $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
        //     throw new Exception(message: "Failed to add file to git: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
        // }

        // // Commit the changes
        // echo "<p>Committing changes\n</p>";
        // $returnVar = exec(command: "git commit -m 'Add new journal entry'", output: $output);
        // if (!str_starts_with(haystack: $returnVar, needle: " create mode 100644")) {
        //     $errorOutput = implode(separator: "\n", array: $output);  // Merge all lines of output into a single string
        //     throw new Exception(message: "Failed >$returnVar< to commit changes: " . ($errorOutput ?: "No output returned") . ($returnVar ? " (Return code: $returnVar)" : ""));
        // }

        $this->getOntoCorrectBranch();

        // Create a new random branch name starting with 'tempo'
        $newBranchName = 'tempo_' . uniqid();

        // Switch to the new branch
        echo "<p>Switching to new branch $newBranchName\n</p>";
        $returnVar = exec(command: "git checkout -b $newBranchName");
        if (!empty($returnVar)) {
            throw new Exception("Failed to create and switch to new branch: " . implode("\n", $output));
        }

        // Delete the old branch locally and from the remote
        if (isset($oldBranchName)) {
            // Delete the old branch locally
            echo "<p>Locally deleting old branch named $oldBranchName\n</p>";
            $returnVar = exec(command: "git branch -d $oldBranchName");
            if (!str_starts_with(haystack: $returnVar, needle: "Deleted branch $oldBranchName")) {
                throw new Exception("Failed >$returnVar< to delete old branch locally: " . implode("\n", $output));
            }

            // Delete the old branch from the remote
            echo "<p>Remotely deleting old branch named $oldBranchName\n</p>";
            $returnVar = exec(command: "git push origin --delete $oldBranchName");
            if (!empty($returnVar)) {
                throw new Exception("Failed to delete old branch $oldBranchName from remote: " . implode("\n", $output));
            }
        }

        // Push the changes to the new branch
        echo "<p>Pushing changes to remote for new branch $newBranchName\n</p>";
        echo "<pre>git push origin $newBranchName</pre>";
        $returnVar = exec(command: "git push origin $newBranchName", output: $outputOfPush);
        echo "<pre>outputOfPush: " . print_r($outputOfPush, true) . "</pre>";
        if (!empty($returnVar)) {
            echo "Failed >$returnVar< to push changes to remote: " . implode("\n", $output);
            throw new Exception("Failed to push >$returnVar< changes to remote: " . implode("\n", $output));
        } else {
            echo "<p>Changes >$returnVar< successfully pushed to remote for new branch $newBranchName\n</p>";
        }

        return $newBranchName;   // to be used in the message displayed to the user
    }
}
