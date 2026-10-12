<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

/**
 * The path check behind public/parser.php?file=...
 */
class JournalPathTest extends Unit
{
    private string $root;

    protected function _before(): void
    {
        $this->root = realpath(sys_get_temp_dir()) . '/quick-path-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/journal/2024/02', 0700, true);
        file_put_contents($this->root . '/journal/2024/02/03hello.md', 'entry');
        file_put_contents($this->root . '/secret.txt', 'not journal');
    }

    protected function _after(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testResolvesAFileInsideTheJournal(): void
    {
        $this->assertSame(
            $this->root . '/journal/2024/02/03hello.md',
            \JournalPath::resolve($this->root . '/journal', '2024/02/03hello.md')
        );
    }

    public function testMissingFileIsRejected(): void
    {
        $this->assertNull(\JournalPath::resolve($this->root . '/journal', '2024/02/nope.md'));
    }

    public function testDotDotOutOfTheJournalIsRejected(): void
    {
        $this->assertNull(\JournalPath::resolve($this->root . '/journal', '../secret.txt'));
    }

    public function testSymlinkedJournalStillResolves(): void
    {
        symlink($this->root . '/journal', $this->root . '/link');

        $this->assertSame(
            $this->root . '/journal/2024/02/03hello.md',
            \JournalPath::resolve($this->root . '/link', '2024/02/03hello.md')
        );
    }

    /** Current behavior: the prefix check has no trailing slash, so a sibling dir passes. */
    public function testSiblingDirSharingTheJournalPrefixIsAccepted(): void
    {
        mkdir($this->root . '/journal-old');
        file_put_contents($this->root . '/journal-old/x.md', 'outside');

        $this->assertSame(
            $this->root . '/journal-old/x.md',
            \JournalPath::resolve($this->root . '/journal', '../journal-old/x.md')
        );
    }
}
