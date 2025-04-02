<?php
/**
 * Class QuickLister
 *
 * This class is responsible for finding and listing journal entries
 * in a specified directory structure.
 */
declare(strict_types=1);
class QuickLister {
    public function __construct(
        private readonly string $journalRoot,
        private readonly int $debugLevel = 0,
        private readonly array $allowedExtensions = ['md', 'html']
    ) {}

    /**
     * List journal entries for a given year, and optionally a month.
     *
     * @param string $year
     * @param string|null $month
     * @return array
     */
    public function listEntries(string $year, ?string $month = null): array
    {
        $entries = [];

        $baseDir = $this->journalRoot . "/$year";
        if ($month !== null) {
            $baseDir .= "/$month";
        }

        if (!is_dir($baseDir)) {
            return [];
        }

        $directory = new RecursiveDirectoryIterator($baseDir);
        $iterator = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                if ($this->debugLevel >= 5) {
                    print_rob("Skipping what is not a file", false);
                    print_rob($fileInfo, false);
                }
                continue;
            }

            $extension = strtolower($fileInfo->getExtension());

            if (!in_array($extension, $this->allowedExtensions)) {
                if ($this->debugLevel >= 5) {
                    print_rob("Skipping invalid extension", false);
                    print_rob($extension, false);
                }
                continue;
            }

            $fullPath = $fileInfo->getRealPath();
            $relativePath = str_replace($this->journalRoot . '/', '', $fullPath);

            if ($this->debugLevel >= 4) {
                print_rob("Valid file found", false);
                print_rob($fullPath, false);
            }

            $entries[] = [
                'path' => $relativePath,
                'filename' => basename($fullPath),
                'year' => $year,
                'month' => $month ?? substr($relativePath, 5, 2),
                'title' => $this->extractTitle(basename($fullPath)),
            ];
        }

        // Sort by filename descending (i.e., day and title)
        usort($entries, fn($a, $b) => strcmp($b['filename'], $a['filename']));

        return $entries;
    }

    /**
     * Extract the title from the filename.
     * This is a simple way to get the title without parsing the file.
     * The title is actually defined in the frontmatter of .md files
     * and in the <title> tag of .html files.
     *
     * @param string $filename
     * @return string
     */
    private function extractTitle(string $filename): string {
        $name = preg_replace('/^\d{2}/', '', pathinfo($filename, PATHINFO_FILENAME));
        $name = str_replace('-', ' ', $name);
        return ucfirst($name);
    }
}
