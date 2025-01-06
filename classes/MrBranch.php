<?php

class MrBranch {

    public function __construct(
        private string $branchName,
        private DateTime $commitDate
    ) {
    }

    public function getBranchName(): string
    {
        return $this->branchName;
    }

    public function getLatestCommit(): DateTime
    {
        return $this->commitDate;
    }

    public function __toString(): string
    {
        return $this->getBranchName();
    }
    public function getBranchDateAsString(): string
    {
        return $this->commitDate->format('Y-m-d H:i:s');
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