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

    public function isTempBranch(): bool
    {
        return str_starts_with(haystack: $this->branchName, needle: 'temp');
    }
    public function getLatestCommit(): DateTime
    {
        return $this->commitDate;
    }

    public function __toString(): string
    {
        return $this->getBranchName();
    }
    public function compareTo(MrBranch $other): int
    {
        if (!$other instanceof self) {
            throw new TypeError('Argument must be an instance of MrBranch');
        }

        return $this->commitDate <=> $other->commitDate;
    }

    public static function compare(MrBranch $a, MrBranch $b): int
    {
        return $a->compareTo($b);
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