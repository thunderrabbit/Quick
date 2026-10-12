<?php

declare(strict_types=1);

/**
 * YAML double-quoted scalars, as the journal's frontmatter uses them.
 *
 * Inside "..." YAML treats backslash as an escape, so a title like Say "hi"
 * must be written "Say \"hi\"" or Hugo cannot parse the entry. Only the two
 * characters that would end or corrupt the string are escaped; line breaks
 * become spaces because every frontmatter value here is one line.
 */
class YamlString
{
    public static function quote(string $value): string
    {
        $one_line = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $one_line) . '"';
    }

    /**
     * The inside of a double-quoted scalar (without its quotes) back to text.
     * Undoes quote(); other escapes are left as written.
     */
    public static function unquote(string $inner): string
    {
        return preg_replace_callback(
            '/\\\\(["\\\\])/',
            fn(array $m): string => $m[1],
            $inner
        ) ?? $inner;
    }
}
