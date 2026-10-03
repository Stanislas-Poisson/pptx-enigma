<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use DOMDocument;
use LogicException;
use PHPUnit\Framework\TestCase;
use PPTXenigma\ListType;
use PPTXenigma\Paragraph;
use PPTXenigma\ParagraphReader;

final class ParagraphReaderTest extends TestCase
{
    public function test_a_paragraph_of_spaces_is_blank(): void
    {
        self::assertTrue($this->read('<a:r><a:t> ' . "\u{00A0}" . '</a:t></a:r>')->isBlank());
        self::assertTrue($this->read('')->isBlank());
    }

    public function test_reads_a_run_without_properties_and_skips_what_is_not_text(): void
    {
        $paragraph = $this->read(' <x:other/><a:r><a:t>plain</a:t></a:r> <a:endParaRPr/><!-- c -->');

        self::assertCount(1, $paragraph->inlines);
        self::assertSame('plain', $paragraph->inlines[0]->text);
        self::assertFalse($paragraph->inlines[0]->bold);
        self::assertNull($paragraph->item);
        self::assertSame('plain', $paragraph->text());
        self::assertFalse($paragraph->isBlank());
    }

    public function test_reads_the_list_item(): void
    {
        $paragraph  = $this->read('<a:pPr lvl="2"><a:buFont typeface="Arial"/><a:buChar char="x"/></a:pPr><a:r><a:t>x</a:t></a:r>');
        $number     = $this->read('<a:pPr><a:buAutoNum type="arabicPeriod"/></a:pPr><a:r><a:t>x</a:t></a:r>');
        $none       = $this->read('<a:pPr><a:buFont typeface="Arial"/><a:buNone/></a:pPr><a:r><a:t>x</a:t></a:r>');
        $without    = $this->read('<a:pPr algn="l"/><a:r><a:t>x</a:t></a:r>');

        self::assertSame([2, ListType::Bullet], [$paragraph->item?->level, $paragraph->item?->type]);
        self::assertSame([0, ListType::Number], [$number->item?->level, $number->item?->type]);
        self::assertNull($none->item);
        self::assertNull($without->item);
    }

    public function test_reads_the_styles_the_breaks_and_the_links(): void
    {
        $paragraph = $this->read(
            '<a:r><a:rPr b="1" i="true" u="sng" strike="sngStrike" baseline="-25000"><a:hlinkClick r:id="rId3"/></a:rPr><a:t>x</a:t></a:r><a:br/>',
            new ParagraphReader(['rId3' => 'https://example.com']),
        );

        [$run, $break] = $paragraph->inlines;

        self::assertTrue($run->bold && $run->italic && $run->underline && $run->strike);
        self::assertSame(-25000, $run->baseline);
        self::assertSame('https://example.com', $run->url);
        self::assertTrue($break->lineBreak);
    }

    private function read(string $xml, ParagraphReader $paragraphReader = new ParagraphReader()): Paragraph
    {
        $domDocument = new DOMDocument();
        $domDocument->loadXML('<a:p xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:x="urn:other" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . $xml . '</a:p>');

        return $paragraphReader->read($domDocument->documentElement ?? throw new LogicException('No element.'));
    }
}
