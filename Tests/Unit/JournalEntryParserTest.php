<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

/**
 * parser.php loads an entry back into the editor, and saving writes it again,
 * so what JournalEntryPoster writes must parse back to what was typed.
 */
class JournalEntryParserTest extends Unit
{
    private string $journal;
    private \Config $config;

    protected function _before(): void
    {
        $this->journal = sys_get_temp_dir() . '/quick-journal-' . bin2hex(random_bytes(6));
        mkdir($this->journal, 0700);
        $this->config = new \Config();
        $this->config->post_path_journal = $this->journal;
    }

    protected function _after(): void
    {
        exec('rm -rf ' . escapeshellarg($this->journal));
    }

    /**
     * @return array<string, string>
     */
    private function roundTrip(string $title, string $tags): array
    {
        $poster = new \JournalEntryPoster(debug: 0);
        $this->assertTrue($poster->createPost($this->config, [
            'date' => 'Saturday 3 February 2024',
            'time' => '20:00',
            'title' => $title,
            'tags' => $tags,
            'post_content' => 'Body text.',
        ]));
        return (new \JournalEntryParser($this->journal . $poster->post_path))->parse();
    }

    public function testPlainEntryRoundTrips(): void
    {
        $data = $this->roundTrip('Hello', 'life, journal');

        $this->assertSame('Hello', $data['title']);
        $this->assertSame('2024, life, journal', $data['tags']);
        $this->assertSame('20:00', $data['time']);
        $this->assertSame('Saturday 3 February 2024', $data['date']);
        $this->assertSame("Body text.\n", $data['post_content']);
    }

    public function testQuotesAndBackslashesRoundTrip(): void
    {
        $data = $this->roundTrip('Say "hi" \\o/', 'life, "quoted"');

        $this->assertSame('Say "hi" \\o/', $data['title']);
        $this->assertSame('2024, life, "quoted"', $data['tags']);
    }

    public function testBareTagsFromAHandWrittenEntryStillParse(): void
    {
        $file = $this->journal . '/old.md';
        file_put_contents($file, "---\ntitle: \"Old\"\ntags: [ 2019, life ]\ndate: 2019-01-01T09:00:00+09:00\n---\n\nText\n");

        $this->assertSame('2019, life', (new \JournalEntryParser($file))->parse()['tags']);
    }
}
