<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PPTXenigma\Duplicates;
use PPTXenigma\Options;

final class OptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new Options();

        self::assertNull($options->sign);
        self::assertSame(Duplicates::Error, $options->duplicates);
        self::assertSame(Options::DEFAULT_HTML_TAGS, $options->htmlTags);
        self::assertSame(['http', 'https', 'mailto', 'tel'], $options->linkSchemes);
    }

    public function testTagsThatAreLeftOutKeepTheirDefault(): void
    {
        $tags = (new Options(htmlTags: ['bold' => 'strong']))->htmlTags;

        self::assertSame('strong', $tags['bold']);
        self::assertSame('i', $tags['italic']);
    }

    /**
     * @return iterable<string, array{\Closure(): Options, string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'empty sign' => [static fn (): Options => new Options(sign: ' '), 'The sign cannot be empty'];
        yield 'unknown style' => [static fn (): Options => new Options(htmlTags: ['color' => 'b']), '"color" is not a style'];
        yield 'tag with a capital' => [static fn (): Options => new Options(htmlTags: ['bold' => 'B']), '"B" is not a valid HTML tag'];
        yield 'tag with an attribute' => [static fn (): Options => new Options(htmlTags: ['bold' => 'b onclick']), '"b onclick" is not a valid HTML tag'];
        yield 'scheme in capitals' => [static fn (): Options => new Options(linkSchemes: ['HTTP']), '"HTTP" is not a valid scheme'];
    }

    /**
     * @param \Closure(): Options $create
     */
    #[DataProvider('invalidProvider')]
    public function testRejectsAnInvalidSetting(\Closure $create, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $create();
    }
}
