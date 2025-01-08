<?php

class NextStoryWord
{
    private $gitLogFile;
    private $storyFile;
    private $gitLogEntries;
    private $storyWords;

    public function __construct($gitLogFile, $storyFile)
    {
        $this->gitLogFile = $gitLogFile;
        $this->storyFile = $storyFile;
        $this->gitLogEntries = $this->readGitLog();
        $this->storyWords = $this->readStory();
    }

    private function readGitLog()
    {
        if (!file_exists($this->gitLogFile)) {
            throw new Exception("Git log file not found.");
        }

        $contents = file_get_contents($this->gitLogFile);
        return explode("\n", trim($contents));
    }

    private function readStory()
    {
        if (!file_exists($this->storyFile)) {
            throw new Exception("Story file not found.");
        }

        $contents = file_get_contents($this->storyFile);
        return explode(" ", trim($contents));
    }

    // ... further methods will be implemented in the next subtasks ...
}
}


