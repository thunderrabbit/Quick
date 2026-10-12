<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * A throwaway journal repo for tests: a work tree on branch master whose
 * "origin" is a bare repo beside it, plus room for a fixture story file.
 * Everything lives under one temp dir that remove() deletes.
 *
 * TempOSpooner and NextStoryWord shell out to plain `git` in the current
 * directory, so tests chdir() into path() before calling them.
 */
class TempGitRepo
{
    private string $root;

    private function __construct()
    {
        $this->root = sys_get_temp_dir() . '/quick-test-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/work', 0700, true);
    }

    public static function gitAvailable(): bool
    {
        exec('command -v git', $output, $resultCode);
        return $resultCode === 0;
    }

    public static function create(): self
    {
        $repo = new self();
        $repo->git(['init', '-q', '--bare', '-b', 'master', $repo->originPath()], $repo->root);
        $repo->git(['init', '-q', '-b', 'master']);
        // Local config only, so the host's global hooks, signing and identity
        // never leak into a test commit.
        $repo->git(['config', 'user.name', 'Quick Test']);
        $repo->git(['config', 'user.email', 'quick-test@example.invalid']);
        $repo->git(['config', 'commit.gpgsign', 'false']);
        $repo->git(['config', 'core.hooksPath', '/dev/null']);
        $repo->git(['remote', 'add', 'origin', $repo->originPath()]);
        $repo->commitWord('start');
        $repo->git(['push', '-q', '-u', 'origin', 'master']);
        return $repo;
    }

    public function path(): string
    {
        return $this->root . '/work';
    }

    public function originPath(): string
    {
        return $this->root . '/origin.git';
    }

    /** An empty commit whose whole message is $word, like a story-word commit. */
    public function commitWord(string $word): void
    {
        $this->git(['commit', '-q', '--allow-empty', '--allow-empty-message', '-m', $word]);
    }

    /** Commit subjects, newest first, in the work tree. */
    public function subjects(): array
    {
        return $this->lines($this->git(['log', '--pretty=format:%s']));
    }

    /** Commit subjects, newest first, as origin has them. */
    public function originSubjects(): array
    {
        return $this->lines($this->git(['--git-dir', $this->originPath(), 'log', '--pretty=format:%s', 'master']));
    }

    /** Write a fixture story file beside the repos and return its path. */
    public function writeStory(string $text): string
    {
        $file = $this->root . '/story.txt';
        file_put_contents($file, $text);
        return $file;
    }

    public function remove(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    /**
     * Run git with an argv list (no shell) and return stdout.
     *
     * @param list<string> $args
     */
    public function git(array $args, ?string $cwd = null): string
    {
        $process = proc_open(
            ['git', ...$args],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $cwd ?? $this->path()
        );
        if (!is_resource($process)) {
            throw new \RuntimeException('Could not start git');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $resultCode = proc_close($process);
        if ($resultCode !== 0) {
            throw new \RuntimeException('git ' . implode(' ', $args) . " failed ($resultCode): $stderr");
        }
        return (string) $stdout;
    }

    private function lines(string $text): array
    {
        return $text === '' ? [] : explode("\n", rtrim($text, "\n"));
    }
}
