<?php
declare(strict_types=1);

class QuickParser
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function parse(): array
    {
        $content = file_get_contents($this->filePath);
        if ($content === false) {
            throw new RuntimeException("Unable to read file: {$this->filePath}");
        }

        // Split frontmatter and body
        $parts = preg_split('/^-{3,}\R/m', $content, 3);
        if (count($parts) < 3) {
            throw new RuntimeException("Invalid frontmatter format.");
        }

        $frontmatter = trim($parts[1]);
        $body = ltrim($parts[2]);

        // Parse frontmatter
        $fields = $this->parseFrontmatter($frontmatter);

        // Format date & time for the form
        $dt = new DateTime($fields['date'] ?? 'now');
        $formattedDate = $dt->format('l j F Y'); // e.g. Tuesday 11 February 2025
        $formattedTime = $dt->format('H:i');

        return [
            'title' => $fields['title'] ?? '',
            'tags' => isset($fields['tags']) ? implode(', ', $fields['tags']) : '',
            'date' => $formattedDate,
            'time' => $formattedTime,
            'post_content' => rtrim($body) . "\n",  // Ensure trailing newline
        ];
    }

    private function parseFrontmatter(string $text): array
    {
        $lines = explode("\n", $text);
        $data = [];

        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s*(.*)$/', trim($line), $matches)) {
                $key = $matches[1];
                $value = trim($matches[2]);

                // Handle quoted strings
                if (preg_match('/^"(.*)"$/', $value, $m)) {
                    $data[$key] = $m[1];
                }
                // Handle tag arrays
                elseif (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                    $value = trim($value, '[] ');
                    $tags = array_map(
                        fn($t) => trim($t, '" '),
                        explode(',', $value)
                    );
                    $data[$key] = $tags;
                }
                // Handle other values as-is
                else {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }
}
