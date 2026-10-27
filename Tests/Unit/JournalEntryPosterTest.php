<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

class JournalEntryPosterTest extends Unit
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

    private function post(string $title, string $content = 'Body text.', string $tags = 'life'): \JournalEntryPoster
    {
        $poster = new \JournalEntryPoster(debug: 0);
        $created = $poster->createPost($this->config, [
            'date' => 'Saturday 3 February 2024',
            'time' => '20:00',
            'title' => $title,
            'tags' => $tags,
            'post_content' => $content,
        ]);
        $this->assertTrue($created);
        return $poster;
    }

    public function testFilePathIsYearMonthThenDayGluedToUrlTitle(): void
    {
        $poster = $this->post('Creating posts with Quick');

        $this->assertSame('/2024/02/03creating-posts-with-quick.md', $poster->post_path);
        $this->assertFileExists($this->journal . $poster->post_path);
    }

    public function testApostrophesAreDroppedAndPunctuationBecomesSingleHyphens(): void
    {
        $poster = $this->post("I'm here -- it's fine?!");

        $this->assertSame('/2024/02/03im-here-its-fine.md', $poster->post_path);
    }

    /** Current behavior: the regex is ASCII-only, so a Japanese title vanishes. */
    public function testNonAsciiTitleLeavesOnlyTheDay(): void
    {
        $poster = $this->post('日本の朝');

        $this->assertSame('/2024/02/03.md', $poster->post_path);
    }

    public function testFrontmatterAndContentAreWritten(): void
    {
        $poster = $this->post('Hello', "line one\r\nline two", 'life, journal');

        $this->assertSame(
            "---\n"
            . "title: \"Hello\"\n"
            . "tags: [ \"2024\", \"life\", \"journal\" ]\n"
            . "author: Rob Nugen\n"
            . "date: 2024-02-03T20:00:00+09:00\n"
            . "draft: false\n"
            . "---\n"
            . "\n"
            . "line one\nline two",
            file_get_contents($this->journal . $poster->post_path)
        );
    }

    public function testDoubleQuoteInTitleIsEscapedForYaml(): void
    {
        $poster = $this->post('Say "hi"');

        $this->assertStringContainsString(
            "title: \"Say \\\"hi\\\"\"\n",
            file_get_contents($this->journal . $poster->post_path)
        );
    }

    public function testDoubleQuoteInATagIsEscapedForYaml(): void
    {
        $poster = $this->post('Hello', 'text', 'life, "quoted"');

        $this->assertStringContainsString(
            "tags: [ \"2024\", \"life\", \"\\\"quoted\\\"\" ]\n",
            file_get_contents($this->journal . $poster->post_path)
        );
    }

    public function testBrowserOffsetIsUsedInFrontmatterDate(): void
    {
        $poster = new \JournalEntryPoster(debug: 0);
        $poster->createPost($this->config, [
            'date' => 'Wednesday 28 October 2026',
            'time' => '10:38',
            'tz_offset' => '+11:00',
            'title' => 'Sydney',
            'tags' => 'life',
            'post_content' => 'Body.',
        ]);

        $this->assertStringContainsString(
            "date: 2026-10-28T10:38:00+11:00\n",
            file_get_contents($this->journal . $poster->post_path)
        );
    }

    public function testBadOffsetFallsBackToJst(): void
    {
        $poster = new \JournalEntryPoster(debug: 0);
        $poster->createPost($this->config, [
            'date' => 'Saturday 3 February 2024',
            'time' => '20:00',
            'tz_offset' => "+99:00\ndraft: true",
            'title' => 'Odd',
            'tags' => 'life',
            'post_content' => 'Body.',
        ]);

        $this->assertStringContainsString(
            "date: 2024-02-03T20:00:00+09:00\n",
            file_get_contents($this->journal . $poster->post_path)
        );
    }

    public function testEmptyTitleAndContentCreatesNothing(): void
    {
        $poster = new \JournalEntryPoster(debug: 0);

        $created = $poster->createPost($this->config, ['title' => ' ', 'post_content' => '']);

        $this->assertFalse($created);
        $this->assertSame([], glob($this->journal . '/*'));
    }
}
