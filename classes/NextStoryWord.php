<?php

class NextStoryWord
{
    private $gitLogEntries;
    private $storyWords;

    public function __construct(
        private string $gitLogCommand,
        private string $storyFile
    ) {
        $this->gitLogEntries = $this->readGitLog(gitLogCommand: $this->gitLogCommand);
        echo "<pre>" . print_r($this->gitLogEntries, true) . "</pre>";

        $this->storyWords = $this->readStory($this->storyFile);

        echo "<pre>" . print_r($this->storyWords, true) . "</pre>";
    }

    /**
     * Designed to retrieve words used so far as commit messages in the story.
     *
     * @param string $gitLogCommand (preferably "git log -31 --pretty=format:'%s'")
     * @return array of single word commit messages
     */
    private function readGitLog(string $gitLogCommand): array
    {
        exec(
            command: $gitLogCommand,
            output: $output,
            result_code: $result_code
        );
        return $output;
    }

    private function readStory(string $storyFile): array
    {
        if (!file_exists($storyFile)) {
            throw new Exception("Story file not found.");
        }

        echo "<p>Reading story file: $storyFile<br>";
        $contents = file_get_contents(filename: $storyFile);
        return explode(separator: " ", string: trim(string: $contents));
    }

    // ... further methods will be implemented in the next subtasks ...
}



