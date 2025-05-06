<?php

class NextStoryWord
{
    private $gitLogEntries;
    private $storyWords;
    private string $wordBeforeSubset;
    private array $correctlyMatchedWords = [];

    public function __construct(
        private string $gitLogCommand,
        private string $storyFile,
        private int $debugLevel,
    ) {
        $this->gitLogEntries = $this->readGitLog(gitLogCommand: $this->gitLogCommand);
        if ($this->debugLevel > 3) {
            echo "<pre>" . print_r($this->gitLogEntries, true) . "</pre>";
        }

        $this->storyWords = $this->readStory($this->storyFile);

        if ($this->debugLevel > 4) {
            echo "<pre>" . print_r($this->storyWords, true) . "</pre>";
        }

        $this->wordBeforeSubset = $this->findWordBeforeSubset() ?? 'WORD NOT FOUND';
    }

    public function __tostring() : string
    {
        return $this->wordBeforeSubset;
    }

    /**
     * Just helps print what words did NOT match the story
     * @param array $array unless this exists,
     * @param string $word print this
     * @return string
     */
    private function thisArrayOrThisWord(array $array, string $word) : string
    {
        if(count($array) > 1) {
            return implode(
                separator: " ",
                array: $array
            );
        } else {
            return $word;
        }
    }

    private function explainHowToRecoverFromDisaster():void
    {
        $storySnippet = print_r(array_slice(array: $this->storyWords, offset: 0, length: 10),true);
        $logSnippet = print_r(value: $this->gitLogEntries, return: true);
        echo <<<HTML
        <h2>Something really surprising happened; we didn't find a single match.</h2>
        <br>Here is part of the story:<br>
        <pre>$storySnippet</pre>
        <br>Here is the git log entries:<br>
        <pre>$logSnippet</pre>
        <p>If the above looks way off, then probably it's being run in the wrong directory.</p>
        <br>Check the paths in class/Config.php
        <ol>
            <li>From Lemur, `ssh bfr`</li>
            <li>On bfr, `cd Quick`</li>
            <li>Make sure index.php points to correct directory `~/x0x0x0/`.</li>
        </ol>
        <p>If the above looks reasonable, then probably the x0x0x0 file got changed.</p>
        <ol>
            <li>From Lemur, `ssh bfr`</li>
            <li>On bfr, </li>
            <ul>
                <li>`jour` to visit journal directory</li>
                <li>`gitl` to see latest commits</li>
                <li>`cd x0x0x0` to visit story directory</li>
                <li>look for latest commit worss</li>
                <li>Fix the story or fix the git commit hashes</li>
            </ul>
        </ol>
HTML;
    }
    private function findWordBeforeSubset(): ?string
    {
        $subsetStartIndex = null;

        // Find the starting index of the subset in the larger array
        for ($i = 0; $i <= count(value: $this->storyWords) - count(value: $this->gitLogEntries); $i++) {
            if ($this->storyWords[$i] == $this->gitLogEntries[0]) {
                if ($this->debugLevel > 4) {
                    echo "Found a match at index $i   {$this->storyWords[$i]}<br>";
                }
                $matchFound = true;
                for ($j = 1; $j < count(value: $this->gitLogEntries); $j++) {
                    if (trim(string: $this->storyWords[$i + $j]) != trim(string: $this->gitLogEntries[$j])) {
                        $matchFound = false;
                        if ($this->debugLevel > 1) {
                            echo "❌ " . $this->thisArrayOrThisWord($this->correctlyMatchedWords, $this->storyWords[$i + $j - 1]);
                            echo " {$this->storyWords[$i + $j]}<br>";  // specifically this word did not match
                        }
                        $this->correctlyMatchedWords = [];
                        break;
                    }
                    $this->correctlyMatchedWords[] = $this->storyWords[$i + $j - 1];
                }
                if ($matchFound) {
                    if ($this->debugLevel > 0) {
                        echo "<br>✅ <b>{$this->storyWords[$i - 1]}</b> ";  // assumes $i > 0 (meaning we are not at the beginning of the story)
                        echo implode(separator: " ", array: $this->correctlyMatchedWords);
                        echo " ...<br>";
                    }
                    // $this->foundWords = [$this->storyWords[$i - 1], ...$this->correctlyMatchedWords];  // so we can let caller show debug infos
                    if($i < 500) {
                        echo "<br>WE ONLY HAVE $i WORDS BEFORE WE REACH THE BEGINNING OF THE STORY!!!";
                    }
                    $subsetStartIndex = $i;
                    break;
                }
            } else {
                if ($this->debugLevel > 6) {
                    echo "didn't find a match at index $i   {$this->storyWords[$i]}<br>";
                }
            }
        }
        if ($subsetStartIndex === null) {
            $this->explainHowToRecoverFromDisaster();
            return null;
        }
        if($subsetStartIndex > 0) {
            return $this->storyWords[$subsetStartIndex - 1];
        } else {
            return null;
        }
    }

    public function getCorrectlyMatchedWords() : array
    {
        return $this->correctlyMatchedWords;
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

        $handle = fopen(filename: $storyFile, mode: "r");

        if ($handle === false) {
            throw new Exception(message: "Failed to open story file.");
        }

        while (($line = fgets(stream: $handle)) !== false) {
            $trimmedLine = trim(string: $line);

            if (empty($trimmedLine)) {
                // Handle empty lines
                $words[] = "　";    // will be used as commit message as git allows full-width space character, but not ascii space
            } elseif (strpos(haystack: $trimmedLine, needle: ' ') !== false) {
                // Split the line into words
                $wordArray = explode(
                                separator: ' ',
                                string: $trimmedLine
                            );

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
}



