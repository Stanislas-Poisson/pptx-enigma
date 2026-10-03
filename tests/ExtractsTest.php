<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use PHPUnit\Framework\TestCase;
use PPTXenigma\ExtractionException;
use PPTXenigma\Extracts;

final class ExtractsTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    /**
     * @param list<array{file: int, notes: list<mixed>|null}> $slides
     */
    private function extract(array $slides, ?string $sign = null): Extracts
    {
        $path = PptxBuilder::build($slides);
        $this->files[] = $path;
        $extractor = new Extracts($path);

        if (null !== $sign) {
            $extractor->setSign($sign);
        }

        return $extractor->extract();
    }

    /**
     * @return array{file: int, notes: list<mixed>}
     */
    private static function slide(int $file, string ...$lines): array
    {
        return ['file' => $file, 'notes' => array_values($lines)];
    }

    public function testExtractsTheSample(): void
    {
        $extractor = (new Extracts(__DIR__ . '/../examples/sample.pptx'))->extract();

        self::assertSame('¤', $extractor->getSign());
        self::assertSame([
            'Guide' => [
                'w02_guide' => '<p>Hello, I am the guide.</p>',
                'w03_goodbye' => '<p>That is all, thank you.</p>',
            ],
            'Narrator' => [
                'w01_intro' => '<p>Welcome to this </p><p><b>fictional</b> presentation, written only to test the extractor.</p><p>It has <i>two</i> voices.</p>',
                'w02_list' => '<p>Three things to remember:</p><ul><li>Notes are read slide by slide.</li><li>Voices are grouped by speaker.</li><li>References identify each voice-over.</li></ul>',
            ],
        ], $extractor->getVoiceOver());
    }

    public function testReadsTheSlidesInTheOrderOfThePresentation(): void
    {
        // The files are numbered 1, 2, 10 and 11, but the presentation shows them as 11, 2, 10, 1.
        $extractor = $this->extract([
            self::slide(11, '¤'),
            self::slide(2, '¤ VOICE (Speaker) first', 'A', '¤ END'),
            self::slide(10, '¤ VOICE (Speaker) second', 'B', '¤ END'),
            self::slide(1, '¤ VOICE (Speaker) third', 'C', '¤ END'),
        ]);

        self::assertSame(['first', 'second', 'third'], array_keys($extractor->getVoiceOver()['Speaker']));
    }

    public function testKeepsTheFirstParagraphOfAVoiceOver(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            self::slide(2, '¤ VOICE (Speaker) ref', 'First', 'Second', '¤ END'),
        ]);

        self::assertSame(['Speaker' => ['ref' => '<p>First</p><p>Second</p>']], $extractor->getVoiceOver());
    }

    public function testReadsSeveralVoiceOversInTheSameNotesWithoutAClosingLine(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            self::slide(2, '¤ VOICE (B) one', 'x', '¤ VOICE (A) two', 'y', '¤ END', 'ignored', '¤ VOICE (B) three', 'z'),
        ]);

        // The last voice-over is not closed: it is ignored.
        self::assertSame(['A' => ['two' => '<p>y</p>'], 'B' => ['one' => '<p>x</p>']], $extractor->getVoiceOver());
    }

    public function testTrimsTheSpeakerAndTheReference(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            self::slide(2, '¤  VOICE OVER  ( The Guide )   ref 1  ', 'x', '¤'),
        ]);

        self::assertSame(['The Guide' => ['ref 1' => '<p>x</p>']], $extractor->getVoiceOver());
    }

    public function testIgnoresTheNumberOfTheSlideAndTheOtherPlaceholders(): void
    {
        $extractor = $this->extract([self::slide(1, '¤'), self::slide(2, '¤ V (S) r', 'x', '¤')]);

        self::assertSame('¤', $extractor->getSign());
        self::assertStringNotContainsString('‹', json_encode($extractor->getVoiceOver(), JSON_THROW_ON_ERROR));
    }

    public function testConvertsTheLists(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            ['file' => 2, 'notes' => [
                '¤ V (S) lists',
                ['runs' => ['Intro']],
                ['runs' => ['A'], 'bullet' => 'ul'],
                ['runs' => ['B'], 'bullet' => 'ul', 'lvl' => 1],
                ['runs' => ['C'], 'bullet' => 'ol', 'lvl' => 1],
                ['runs' => ['D'], 'bullet' => 'ul'],
                ['runs' => ['not a list'], 'bullet' => 'none'],
                ['runs' => ['1'], 'bullet' => 'ol'],
                ['runs' => ['2'], 'bullet' => 'ol', 'lvl' => 1],
                ['runs' => ['3'], 'bullet' => 'ol', 'lvl' => 2],
                ['runs' => ['4'], 'bullet' => 'ol'],
                ['runs' => [' '], 'bullet' => 'ol'],
                ['runs' => ['5'], 'bullet' => 'ol', 'lvl' => 5],
                '¤',
            ]],
        ]);

        self::assertSame(
            '<p>Intro</p>'
            . '<ul><li>A<ul><li>B</li></ul><ol><li>C</li></ol></li><li>D</li></ul>'
            . '<p>not a list</p>'
            . '<ol><li>1<ol><li>2<ol><li>3</li></ol></li></ol></li><li>4</li></ol>'
            . '<ol><li>5</li></ol>',
            $extractor->getVoiceOver()['S']['lists'],
        );
    }

    public function testConvertsTheStylesAndEscapesTheText(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            ['file' => 2, 'notes' => [
                '¤ V (S) styles',
                ['runs' => [
                    ['t' => 'bold', 'b' => '1'],
                    ' ',
                    ['t' => 'all', 'b' => 'true', 'i' => '1', 'u' => 'sng', 'strike' => 'sngStrike'],
                    ' ',
                    ['t' => 'off', 'b' => '0', 'i' => 'false', 'u' => 'none', 'strike' => 'noStrike'],
                    ' ',
                    ['t' => 'sup', 'baseline' => 30000],
                    ['t' => 'sub', 'baseline' => -25000],
                    ['t' => 'flat', 'baseline' => 0],
                ]],
                ['runs' => ['a < b & c > d']],
                ['runs' => ["\u{00A0}"]],
                '¤',
            ]],
        ]);

        self::assertSame(
            '<p><b>bold</b> <b><i><u><s>all</s></u></i></b> off <sup>sup</sup><sub>sub</sub>flat</p><p>a &lt; b &amp; c &gt; d</p>',
            $extractor->getVoiceOver()['S']['styles'],
        );
    }

    public function testSortsTheSpeakers(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            self::slide(2, '¤ V (b) r', 'x', '¤ V (B) r', 'x', '¤ V (a) r', 'x', '¤'),
        ]);

        self::assertSame(['B', 'a', 'b'], array_keys($extractor->getVoiceOver()));
    }

    public function testRejectsAReferenceUsedTwiceForTheSameSpeaker(): void
    {
        $this->expectException(ExtractionException::class);
        $this->expectExceptionMessage('The reference "ref" of the voice-over "Speaker" on slide 4 is already used.');

        // The second slide has no notes, but it counts in the number of the slide.
        $this->extract([
            self::slide(1, '¤'),
            ['file' => 2, 'notes' => null],
            self::slide(3, '¤ V (Speaker) ref', 'x', '¤'),
            self::slide(4, '¤ V (Speaker) ref', 'y', '¤'),
        ]);
    }

    public function testAcceptsTheSameReferenceForTwoSpeakers(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            self::slide(2, '¤ V (A) ref', 'x', '¤ V (B) ref', 'y', '¤'),
        ]);

        self::assertSame(['A' => ['ref' => '<p>x</p>'], 'B' => ['ref' => '<p>y</p>']], $extractor->getVoiceOver());
    }

    public function testUsesTheSignThatIsSetAndSearchesTheFirstNotes(): void
    {
        $extractor = $this->extract([
            self::slide(1, '§ V (S) one', 'x', '§', '¤ V (S) other', 'y', '¤'),
        ], '§');

        self::assertSame('§', $extractor->getSign());
        self::assertSame(['S' => ['one' => '<p>x</p>']], $extractor->getVoiceOver());
    }

    public function testEmptyNotesHaveNoSign(): void
    {
        $this->expectException(ExtractionException::class);
        $this->expectExceptionMessage('No sign found');

        $this->extract([self::slide(1, ' ', "\u{00A0}"), self::slide(2, '¤ V (S) r', 'x', '¤')]);
    }

    public function testAPresentationWithoutNotesHasNoSign(): void
    {
        $this->expectException(ExtractionException::class);

        $this->extract([['file' => 1, 'notes' => null]]);
    }

    public function testAnEmptySignResetsTheDetection(): void
    {
        $path = PptxBuilder::build([self::slide(1, '¤'), self::slide(2, '¤ V (S) r', 'x', '¤')]);
        $this->files[] = $path;

        $extractor = (new Extracts($path))->setSign('')->extract();

        self::assertSame('¤', $extractor->getSign());
    }

    public function testExtractsAgain(): void
    {
        $path = PptxBuilder::build([self::slide(1, '¤'), self::slide(2, '¤ V (S) r', 'x', '¤')]);
        $this->files[] = $path;
        $extractor = new Extracts($path);

        self::assertSame($extractor->extract()->getVoiceOver(), $extractor->extract()->getVoiceOver());
    }

    public function testLeavesNoFileBehind(): void
    {
        $before = glob(sys_get_temp_dir() . '/*') ?: [];
        $path = PptxBuilder::build([self::slide(1, '¤')]);
        $this->files[] = $path;
        $during = glob(sys_get_temp_dir() . '/*') ?: [];

        (new Extracts($path))->setSign('¤')->extract();

        self::assertSame($during, glob(sys_get_temp_dir() . '/*') ?: []);
        self::assertCount(count($before) + 1, $during);
    }
}
