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
        private readonly array $allowedExtensions = ['md', 'html']
    ) {}

    /**
     * List journal entries for a given year, and optionally a month.
     *
     * @param string $year
     * @param string|null $month
     * @return array
     */
    public function listEntries(string $year, ?string $month = null): array {
        $entries = [];

        $basePath = $this->journalRoot . "/$year";
        if ($month !== null) {
            $basePath .= "/$month";
        }

        foreach ($this->allowedExtensions as $ext) {
            $pattern = $basePath . "/*.$ext";
            $files = glob($pattern, GLOB_NOSORT);
            if ($files) {
                foreach ($files as $fullPath) {
                    if (!is_file($fullPath)) continue;

                    $relativePath = str_replace($this->journalRoot . '/', '', $fullPath);
                    $entries[] = [
                        'path' => $relativePath,
                        'filename' => basename($fullPath),
                        'year' => $year,
                        'month' => $month ?? substr($relativePath, 5, 2),
                        'title' => $this->extractTitle(basename($fullPath)),
                    ];
                }
            }
        }

        // Sort by filename descending (i.e., day and title)
        usort($entries, fn($a, $b) => strcmp($b['filename'], $a['filename']));

        return $entries;
    }

    private function extractTitle(string $filename): string {
        $name = preg_replace('/^\d{2}/', '', pathinfo($filename, PATHINFO_FILENAME));
        $name = str_replace('-', ' ', $name);
        return ucfirst($name);
    }
}
