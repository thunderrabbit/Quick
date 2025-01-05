<?php

class MrBranch {

    public function __construct(
        private string $branchName,
        private DateTime $latestCommit
    ) {
    }

    public function getBranchName(): string
    {
        return $this->branchName;
    }

    public function getLatestCommit(): DateTime
    {
        return $this->latestCommit;
    }

    public function getLatestCommitAsString(): string
    {
        return $this->latestCommit->format('Y-m-d H:i:s');
    }
    public function returnMostRecentBranchName(MrBranch $otherBranch): string
    {
        if ($this->getLatestCommit() > $otherBranch->getLatestCommit()) {
            return $this->getBranchName();
        } elseif ($otherBranch->getLatestCommit() > $this->getLatestCommit()) {
            return $otherBranch->getBranchName();
        } else {
            // If dates are equal, return this branch's name
            return $this->getBranchName();
        }
    }
}