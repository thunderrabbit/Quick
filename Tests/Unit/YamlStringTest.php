<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

class YamlStringTest extends Unit
{
    public function testPlainTextIsJustWrappedInQuotes(): void
    {
        $this->assertSame('"Hello"', \YamlString::quote('Hello'));
    }

    public function testDoubleQuoteAndBackslashAreEscaped(): void
    {
        $this->assertSame('"Say \\"hi\\""', \YamlString::quote('Say "hi"'));
        $this->assertSame('"C:\\\\path"', \YamlString::quote('C:\\path'));
    }

    public function testLineBreaksBecomeSpaces(): void
    {
        $this->assertSame('"one two three"', \YamlString::quote("one\r\ntwo\nthree"));
    }

    public function testUnquoteUndoesQuote(): void
    {
        foreach (['Hello', 'Say "hi"', 'C:\\path', 'back\\"slash', "it's", '日本の朝'] as $text) {
            $quoted = \YamlString::quote($text);
            $this->assertSame($text, \YamlString::unquote(substr($quoted, 1, -1)), $text);
        }
    }

    public function testUnquoteLeavesOtherEscapesAlone(): void
    {
        $this->assertSame('tab\\there', \YamlString::unquote('tab\\there'));
    }
}
