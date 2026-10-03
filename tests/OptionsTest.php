<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use Closure;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PPTXenigma\Duplicates;
use PPTXenigma\Options;

final class OptionsTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(): Options, string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'empty sign' => [static fn (): Options => new Options(sign: ' '), 'The sign cannot be empty'];

        yield 'unknown style' => [static fn (): Options => new Options(htmlTags: ['color' => 'b']), '"color" is not a style'];

        yield 'tag with a capital' => [static fn (): Options => new Options(htmlTags: ['bold' => 'B']), '"B" is not a valid HTML tag'];

        yield 'tag with an attribute' => [static fn (): Options => new Options(htmlTags: ['bold' => 'b onclick']), '"b onclick" is not a valid HTML tag'];

        yield 'scheme in capitals' => [static fn (): Options => new Options(linkSchemes: ['HTTP']), '"HTTP" is not a valid scheme'];
    }

    public function test_defaults(): void
    {
        $options = new Options();

        self::assertNull($options->sign);
        self::assertSame(Duplicates::Error, $options->duplicates);

        foreach (Options::DEFAULT_HTML_TAGS as $style => $tag) {
            self::assertSame($tag, $options->htmlTag($style));
        }

        self::assertSame(['http', 'https', 'mailto', 'tel'], $options->linkSchemes);
    }

    /**
     * @param Closure(): Options $create
     */
    #[DataProvider('invalidProvider')]
    public function test_rejects_an_invalid_setting(Closure $create, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $create();
    }

    public function test_tags_that_are_left_out_keep_their_default(): void
    {
        $options = new Options(htmlTags: ['bold' => 'strong']);

        self::assertSame('strong', $options->htmlTag('bold'));
        self::assertSame('i', $options->htmlTag('italic'));
        self::assertSame('', $options->htmlTag('unknown'));
    }
}
