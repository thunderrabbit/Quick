<?php
// X0x0x0StoryParser — reads a story file and produces:
//   ->tokens[]        committable tokens (words + 　), markers EXCLUDED
//   ->markerBefore[i] for some token index i: the branch/merge marker sitting
//                     immediately before that token, pre-resolved to child/parent
//                     branch names via a LIFO stack scan (deterministic, so the
//                     committer can stay stateless across invocations).
//
// Marker syntax (validated, tag-style, LIFO):
//   DIVE>>>(Label)        opens a nested story
//   <<<SURFACE(/Label)    closes it (must match the open Label on top)
//
// This class has no Config/DB/web dependency, so it runs anywhere PHP + git do.
class X0x0x0StoryParser
{
    /** @var string[] committable tokens, markers removed */
    public array $tokens = [];
    /** @var array<int,array{action:string,label:string,child:string,parent:string}> */
    public array $markerBefore = [];

    public function __construct(string $storyFile)
    {
        if (!is_file($storyFile)) {
            throw new Exception("story file not found: $storyFile");
        }
        $lines = file($storyFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new Exception("could not read story file: $storyFile");
        }
        // drop trailing blank lines (file terminator, not an intended gap)
        while (!empty($lines) && trim((string)end($lines)) === '') {
            array_pop($lines);
        }

        $stack     = [];   // open stories: ['label'=>,'child'=>,'parent'=>]
        $diveCount = 0;    // deterministic branch numbering
        $pending   = null; // marker waiting to attach to the next token

        foreach ($lines as $raw) {
            $line = rtrim((string)$raw, "\r");
            $trim = trim($line);

            if ($trim === '') {
                $this->pushToken("　", $pending);
                $pending = null;
            } elseif (preg_match('/^DIVE>>>\((.+)\)$/', $trim, $m)) {
                $this->guardPending($pending);
                $label  = $m[1];
                $parent = empty($stack) ? 'master' : end($stack)['child'];
                $child  = sprintf('lvl%02d_%s', $diveCount, $this->safe($label));
                $diveCount++;
                $entry   = ['label' => $label, 'child' => $child, 'parent' => $parent];
                $stack[] = $entry;
                $pending = ['action' => 'DIVE'] + $entry;
            } elseif (preg_match('#^<<<SURFACE\(/(.+)\)$#', $trim, $m)) {
                $this->guardPending($pending);
                $label = $m[1];
                if (empty($stack)) {
                    throw new Exception("SURFACE(/$label) with no open DIVE");
                }
                $top = array_pop($stack);
                if ($top['label'] !== $label) {
                    throw new Exception("marker mismatch: SURFACE(/$label) tried to close DIVE({$top['label']})");
                }
                $pending = ['action' => 'SURFACE'] + $top;
            } else {
                foreach (preg_split('/\s+/', $trim) as $w) {
                    if ($w === '') {
                        continue;
                    }
                    $this->pushToken($w, $pending);
                    $pending = null;
                }
            }
        }

        if (!empty($stack)) {
            throw new Exception("unclosed DIVE(s): " . implode(', ', array_column($stack, 'label')));
        }
    }

    private function pushToken(string $tok, ?array $marker): void
    {
        $idx = count($this->tokens);
        $this->tokens[] = $tok;
        if ($marker !== null) {
            $this->markerBefore[$idx] = $marker;
        }
    }

    private function guardPending(?array $pending): void
    {
        if ($pending !== null) {
            throw new Exception(
                "two markers with no word between is not supported yet: " .
                "{$pending['action']}({$pending['label']})"
            );
        }
    }

    private function safe(string $s): string
    {
        return trim((string)preg_replace('/[^A-Za-z0-9]+/', '_', $s), '_');
    }
}
