<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;
use Tests\Support\TempGitRepo;

/**
 * TempOSpooner runs git in the current directory, so each test chdir()s into
 * the temp repo. Failure paths are not tested here: each failed attempt
 * sleeps a second and retries up to 10 times.
 */
class TempOSpoonerTest extends Unit
{
    private ?TempGitRepo $repo = null;
    private string $startDir;

    protected function _before(): void
    {
        if (!TempGitRepo::gitAvailable()) {
            $this->markTestSkipped('git is not installed');
        }
        $this->startDir = getcwd();
        $this->repo = TempGitRepo::create();
        chdir($this->repo->path());
    }

    protected function _after(): void
    {
        if ($this->repo !== null) {
            chdir($this->startDir);
            $this->repo->remove();
        }
    }

    public function testAddCommitPushNewFile(): void
    {
        mkdir('2026');
        file_put_contents('2026/entry.md', 'hello');
        $spooner = new \TempOSpooner();

        $this->assertTrue($spooner->addFileToGit('2026/entry.md'));
        $this->assertTrue($spooner->commitChanges('word'));
        $this->assertTrue($spooner->pushChangesToCurrentBranch());

        $this->assertSame(['word', 'start'], $this->repo->originSubjects());
    }

    public function testCommitOfAnEditedFile(): void
    {
        file_put_contents('entry.md', "first\n");
        $this->repo->git(['add', 'entry.md']);
        $this->repo->commitWord('first');
        file_put_contents('entry.md', "second\n");
        $spooner = new \TempOSpooner();

        $this->assertTrue($spooner->addFileToGit('entry.md'));
        $this->assertTrue($spooner->commitChanges('edited'));

        $this->assertSame('edited', $this->repo->subjects()[0]);
    }

    public function testAddAndPushToGitUsesTheStoryWordAsMessage(): void
    {
        file_put_contents('entry.md', 'hello');

        ob_start();
        $pushed = (new \TempOSpooner())->addAndPushToGit('entry.md', "\u{3000}");
        ob_end_clean();

        $this->assertTrue($pushed);
        $this->assertSame(["\u{3000}", 'start'], $this->repo->originSubjects());
    }
}
