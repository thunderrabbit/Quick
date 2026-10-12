<?php

class JournalPath
{
    /**
     * Resolve a journal-relative file to its real path, or null if it does
     * not exist or resolves outside the journal (realpath prefix check).
     * Both sides go through realpath() so a symlinked journal still works.
     */
    public static function resolve(string $journalPath, string $file): ?string
    {
        $journalRoot = realpath($journalPath);
        $fullPath = realpath($journalPath . '/' . $file);

        if (!$fullPath || !$journalRoot || !str_starts_with($fullPath, $journalRoot)) {
            return null;
        }
        return $fullPath;
    }
}
