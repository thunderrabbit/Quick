<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;
use Tests\Support\TempGitRepo;

/**
 * The story is read forward in the story file and backward in git: the newest
 * commit is the story word at index i, and the next commit gets word i - 1.
 */
class NextStoryWordTest extends Unit
{
    private ?TempGitRepo $repo = null;

    protected function _before(): void
    {
        if (!TempGitRepo::gitAvailable()) {
            $this->markTestSkipped('git is not installed');
        }
        $this->repo = TempGitRepo::create();
    }

    protected function _after(): void
    {
        $this->repo?->remove();
    }

    /** Build NextStoryWord against the temp repo; returns [word, echoed output]. */
    private function nextWord(string $storyFile, int $logCount): array
    {
        ob_start();
        $word = (string) new \NextStoryWord(
            gitLogCommand: $this->repo->gitLogCommand($logCount),
            storyFile: $storyFile,
            debugLevel: 0,
        );
        return [$word, ob_get_clean()];
    }

    public function testReturnsTheWordBeforeTheRecentCommitSequence(): void
    {
        $story = $this->repo->writeStory("alpha beta gamma\ndelta epsilon\n");
        foreach (['epsilon', 'delta', 'gamma'] as $word) {
            $this->repo->commitWord($word);
        }

        [$word] = $this->nextWord($story, 3);

        $this->assertSame('beta', $word);
    }

    public function testEmptyLineBecomesFullWidthSpace(): void
    {
        $story = $this->repo->writeStory("one\n\nthree four\n");
        $this->repo->commitWord('four');
        $this->repo->commitWord('three');

        [$word] = $this->nextWord($story, 2);

        $this->assertSame("\u{3000}", $word);
    }

    public function testFullWidthSpaceCommitMatchesTheEmptyLine(): void
    {
        $story = $this->repo->writeStory("one\n\nthree four\n");
        foreach (['four', 'three', "\u{3000}"] as $word) {
            $this->repo->commitWord($word);
        }

        [$word] = $this->nextWord($story, 3);

        $this->assertSame('one', $word);
    }

    public function testWarnsWhenFewerThan500WordsRemain(): void
    {
        $story = $this->repo->writeStory("alpha beta gamma\n");
        $this->repo->commitWord('gamma');

        [, $output] = $this->nextWord($story, 1);

        $this->assertStringContainsString('WE ONLY HAVE 2 WORDS BEFORE WE REACH THE BEGINNING', $output);
    }

    public function testSequenceMissingFromStoryReturnsWordNotFound(): void
    {
        $story = $this->repo->writeStory("alpha beta gamma\n");
        $this->repo->commitWord('zeta');

        [$word, $output] = $this->nextWord($story, 1);

        $this->assertSame('WORD NOT FOUND', $word);
        $this->assertStringContainsString('Story Synchronization Failed', $output);
    }

    /** Current behavior: reaching the first story word looks like a failure. */
    public function testSequenceAtStartOfStoryReturnsWordNotFound(): void
    {
        $story = $this->repo->writeStory("alpha beta gamma\n");
        $this->repo->commitWord('gamma');
        $this->repo->commitWord('beta');
        $this->repo->commitWord('alpha');

        [$word] = $this->nextWord($story, 3);

        $this->assertSame('WORD NOT FOUND', $word);
    }

    public function testMissingStoryFileThrows(): void
    {
        $this->expectExceptionMessage('Story file not found.');

        new \NextStoryWord(gitLogCommand: 'true', storyFile: '/nonexistent/story.txt', debugLevel: 0);
    }
}
