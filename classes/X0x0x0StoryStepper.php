<?php

// X0x0x0StoryStepper — decides the SINGLE next action, statelessly, from git state.
//
// It mirrors NextStoryWord's "match recent commit subjects against the story,
// take the word before" logic, but returns a richer step that also says whether
// crossing a marker OPENs a nested story or CLOSEs one.
//
// Committer walks the story BACKWARD, so it hits markers in reverse of reading:
// the reader's <<<SURFACE (nested's END) is hit FIRST → OPEN; the reader's
// DIVE>>> (nested's START / the dive word) is hit LAST → CLOSE.
//
// Returned step (assoc array):
//   ['done' => true]
//   ['done'=>false, 'action'=>'COMMIT', 'word'=>w]
//   ['done'=>false, 'action'=>'OPEN',  'word'=>w, 'child'=>b, 'parent'=>p]  (<<<SURFACE)
//   ['done'=>false, 'action'=>'CLOSE', 'word'=>w, 'child'=>b, 'parent'=>p]  (DIVE>>>; w rides on the merge)
//
// Pure read of git + story; the caller performs the side effects.
class X0x0x0StoryStepper
{
    public function __construct(
        private string $repoPath,
        private X0x0x0StoryParser $story,
        private int $logCount = 15,
    ) {
    }

    public function nextStep(): array
    {
        $log = $this->gitLogSubjects();

        if (empty($log)) {
            // bootstrap: nothing committed yet → commit the story's LAST token first
            return $this->stepForNext(count($this->story->tokens) - 1, null);
        }

        $i = $this->findMatchIndex($log);
        if ($i === null) {
            throw new Exception("story desync: recent commit subjects not found in story");
        }
        if ($i <= 0) {
            return ['done' => true]; // newest commit is the first word → story complete
        }
        // committing token i-1; the marker crossed sits before token i
        return $this->stepForNext($i - 1, $i);
    }

    private function stepForNext(int $nextIndex, ?int $matchIndex): array
    {
        if ($nextIndex < 0) {
            return ['done' => true];
        }
        $word   = $this->story->tokens[$nextIndex];
        $marker = ($matchIndex !== null && isset($this->story->markerBefore[$matchIndex]))
            ? $this->story->markerBefore[$matchIndex]
            : null;

        if ($marker === null) {
            return ['done' => false, 'action' => 'COMMIT', 'word' => $word];
        }
        $action = $marker['action'] === 'SURFACE' ? 'OPEN' : 'CLOSE';
        return [
            'done'   => false,
            'action' => $action,
            'word'   => $word,
            'child'  => $marker['child'],
            'parent' => $marker['parent'],
        ];
    }

    // First occurrence of the recent-commit run within the story tokens.
    // (Same collision caveat as NextStoryWord; the long window makes it safe.)
    private function findMatchIndex(array $log): ?int
    {
        $tokens = $this->story->tokens;
        $n = count($tokens);
        $m = count($log);
        for ($i = 0; $i <= $n - $m; $i++) {
            if ($tokens[$i] !== $log[0]) {
                continue;
            }
            $ok = true;
            for ($j = 1; $j < $m; $j++) {
                if (trim($tokens[$i + $j]) !== trim((string)$log[$j])) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return $i;
            }
        }
        return null;
    }

    private function gitLogSubjects(): array
    {
        $out = [];
        exec(
            "git -C " . escapeshellarg($this->repoPath) .
            " log -" . (int)$this->logCount . " --pretty=format:%s 2>/dev/null",
            $out
        );
        return $out;
    }
}
