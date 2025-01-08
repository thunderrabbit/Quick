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
        if (!file_exists(filename: $storyFile)) {
            throw new Exception(message: "Story file not found.");
        }

        $words = [];
        $currentWord = '';

        $handle = fopen(filename: $storyFile, mode: "r");

        if ($handle === false) {
            throw new Exception(message: "Failed to open story file.");
        }

        while (($line = fgets(stream: $handle)) !== false) {
            $trimmedLine = trim(string: $line);

            if (empty($trimmedLine)) {
                // Handle empty lines
                $words[] = "　";
            } elseif (strpos(haystack: $trimmedLine, needle: ' ') !== false ||
                    strpos(haystack: $trimmedLine, needle: "\t") !== false) {
                // Split the line into words
                $wordArray = explode(separator: ' ', string: str_replace(search: "\t", replace: ' ', subject: $trimmedLine));

                foreach ($wordArray as $word) {
                    if (!empty($word)) {
                        $words[] = $word;
                    }
                }
            } else {
                // For single-word lines, add them directly
                $words[] = $trimmedLine;
            }
        }

        fclose(stream: $handle);

        return $words;
    }

    // ... further methods will be implemented in the next subtasks ...
}



