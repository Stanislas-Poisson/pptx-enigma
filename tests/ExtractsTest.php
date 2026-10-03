<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use PHPUnit\Framework\TestCase;
use PPTXenigma\Duplicates;
use PPTXenigma\ExtractionException;
use PPTXenigma\Extracts;
use PPTXenigma\Format;
use PPTXenigma\Options;
use PPTXenigma\VoiceOvers;

final class ExtractsTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    public function test_accepts_the_same_reference_for_two_speakers(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            self::slide(2, '¤ V (A) ref', 'x', '¤ V (B) ref', 'y', '¤'),
        ]);

        self::assertSame(['A' => ['ref' => '<p>x</p>'], 'B' => ['ref' => '<p>y</p>']], $extractor->toArray());
    }

    public function test_a_presentation_without_notes_has_no_sign(): void
    {
        $this->expectException(ExtractionException::class);

        $this->extract([['file' => 1, 'notes' => null]]);
    }

    public function test_converts_the_hyperlinks(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            [
                'file'  => 2,
                'notes' => [
                    '¤ V (S) links',
                    ['runs' => [
                        'See ',
                        ['t' => 'the site', 'link' => 'rId10', 'b' => '1'],
                        ' or ',
                        ['t' => 'mail', 'link' => 'rId11'],
                        ' or ',
                        ['t' => 'script', 'link' => 'rId12'],
                        ' or ',
                        ['t' => 'unknown', 'link' => 'rId99'],
                    ]],
                    '¤',
                ],
                'links' => [
                    'rId10' => 'https://example.com/?a=1&b="2"',
                    'rId11' => 'mailto:me@example.com',
                    'rId12' => "java\tscript:alert(1)",
                ],
            ],
        ]);

        self::assertSame(
            '<p>See <a href="https://example.com/?a=1&amp;b=&quot;2&quot;"><b>the site</b></a> or <a href="mailto:me@example.com">mail</a> or script or unknown</p>',
            $extractor->toArray()['S']['links'],
        );
    }

    public function test_converts_the_line_breaks(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            ['file' => 2, 'notes' => [
                '¤ V (S) breaks',
                ['runs' => ['one', ['br' => true], 'two']],
                ['runs' => [['br' => true]]],
                '¤',
            ]],
        ]);

        self::assertSame('<p>one<br>two</p>', $extractor->toArray()['S']['breaks']);
    }

    public function test_converts_the_lists(): void
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
            $extractor->toArray()['S']['lists'],
        );
    }

    public function test_converts_the_styles_and_escapes_the_text(): void
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
            $extractor->toArray()['S']['styles'],
        );
    }

    public function test_empty_notes_have_no_sign(): void
    {
        $this->expectException(ExtractionException::class);
        $this->expectExceptionMessage('No sign found');

        $this->extract([self::slide(1, ' ', "\u{00A0}"), self::slide(2, '¤ V (S) r', 'x', '¤')]);
    }

    public function test_extracts_again(): void
    {
        $path          = PptxBuilder::build([self::slide(1, '¤'), self::slide(2, '¤ V (S) r', 'x', '¤')]);
        $this->files[] = $path;
        $extractor     = new Extracts($path);

        self::assertSame($extractor->extract()->toArray(), $extractor->extract()->toArray());
    }

    public function test_extracts_the_sample(): void
    {
        $extractor = (new Extracts(__DIR__ . '/../examples/sample.pptx'))->extract();

        self::assertCount(4, $extractor);

        self::assertSame('¤', $extractor->sign);
        self::assertSame([
            'Guide' => [
                'w02_guide'   => '<p>Hello, I am the guide.</p>',
                'w03_goodbye' => '<p>That is all, thank you.</p>',
            ],
            'Narrator' => [
                'w01_intro' => '<p>Welcome to this </p><p><b>fictional</b> presentation, written only to test the extractor.</p><p>It has <i>two</i> voices.</p>',
                'w02_list'  => '<p>Three things to remember:</p><ul><li>Notes are read slide by slide.</li><li>Voices are grouped by speaker.</li><li>References identify each voice-over.</li></ul>',
            ],
        ], $extractor->toArray());
    }

    public function test_html_tags_can_be_changed(): void
    {
        $voiceOvers = $this->extract([
            self::slide(1, '¤'),
            ['file' => 2, 'notes' => [
                '¤ V (S) ref',
                ['runs' => [['t' => 'a', 'b' => '1'], ['t' => 'b', 'i' => '1', 'baseline' => 10], ['t' => 'c', 'u' => 'sng']]],
                '¤',
            ]],
        ], new Options(htmlTags: ['bold' => 'strong', 'italic' => 'em', 'superscript' => 'span']));

        self::assertSame('<p><strong>a</strong><em><span>b</span></em><u>c</u></p>', $voiceOvers->toArray()['S']['ref']);
    }

    public function test_ignores_the_number_of_the_slide_and_the_other_placeholders(): void
    {
        $extractor = $this->extract([self::slide(1, '¤'), self::slide(2, '¤ V (S) r', 'x', '¤')]);

        self::assertSame('¤', $extractor->sign);
        self::assertStringNotContainsString('‹', json_encode($extractor->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_keeps_the_first_or_the_last_of_two_voice_overs_with_the_same_reference(): void
    {
        $slides = [
            self::slide(1, '¤'),
            self::slide(2, '¤ V (S) ref', 'first', '¤', '¤ V (S) other', 'x', '¤'),
            self::slide(3, '¤ V (S) ref', 'last', '¤'),
        ];

        self::assertSame(
            ['ref' => '<p>first</p>', 'other' => '<p>x</p>'],
            $this->extract($slides, new Options(duplicates: Duplicates::KeepFirst))->toArray()['S'],
        );
        self::assertSame(
            ['ref' => '<p>last</p>', 'other' => '<p>x</p>'],
            $this->extract($slides, new Options(duplicates: Duplicates::KeepLast))->toArray()['S'],
        );
    }

    public function test_keeps_the_first_paragraph_of_a_voice_over(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            self::slide(2, '¤ VOICE (Speaker) ref', 'First', 'Second', '¤ END'),
        ]);

        self::assertSame(['Speaker' => ['ref' => '<p>First</p><p>Second</p>']], $extractor->toArray());
    }

    public function test_leaves_no_file_behind(): void
    {
        $before        = glob(sys_get_temp_dir() . '/*') ?: [];
        $path          = PptxBuilder::build([self::slide(1, '¤')]);
        $this->files[] = $path;
        $during        = glob(sys_get_temp_dir() . '/*') ?: [];

        (new Extracts($path, new Options(sign: '¤')))->extract();

        self::assertSame($during, glob(sys_get_temp_dir() . '/*') ?: []);
        self::assertCount(count($before) + 1, $during);
    }

    public function test_link_schemes_can_be_changed(): void
    {
        $voiceOvers = $this->extract([
            self::slide(1, '¤'),
            [
                'file'  => 2,
                'notes' => ['¤ V (S) ref', ['runs' => [['t' => 'web', 'link' => 'rId1'], ' ', ['t' => 'mail', 'link' => 'rId2']]], '¤'],
                'links' => ['rId1' => 'https://example.com', 'rId2' => 'mailto:me@example.com'],
            ],
        ], new Options(linkSchemes: ['https']));

        self::assertSame('<p><a href="https://example.com">web</a> mail</p>', $voiceOvers->toArray()['S']['ref']);
    }

    public function test_reads_several_voice_overs_in_the_same_notes_without_a_closing_line(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            self::slide(2, '¤ VOICE (B) one', 'x', '¤ VOICE (A) two', 'y', '¤ END', 'ignored', '¤ VOICE (B) three', 'z', '¤'),
        ]);

        self::assertSame(['A' => ['two' => '<p>y</p>'], 'B' => ['one' => '<p>x</p>', 'three' => '<p>z</p>']], $extractor->toArray());
    }

    public function test_reads_the_slides_in_the_order_of_the_presentation(): void
    {
        // The files are numbered 1, 2, 10 and 11, but the presentation shows them as 11, 2, 10, 1.
        $extractor = $this->extract([
            self::slide(11, '¤'),
            self::slide(2, '¤ VOICE (Speaker) first', 'A', '¤ END'),
            self::slide(10, '¤ VOICE (Speaker) second', 'B', '¤ END'),
            self::slide(1, '¤ VOICE (Speaker) third', 'C', '¤ END'),
        ]);

        self::assertSame(['first', 'second', 'third'], array_keys($extractor->toArray()['Speaker']));
    }

    public function test_rejects_a_reference_used_twice_for_the_same_speaker(): void
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

    public function test_rejects_a_voice_over_that_is_not_closed(): void
    {
        $this->expectException(ExtractionException::class);
        $this->expectExceptionMessage('The voice-over "Speaker" of the reference "ref" on slide 3 is not closed.');

        $this->extract([
            self::slide(1, '¤'),
            ['file' => 2, 'notes' => null],
            self::slide(3, '¤ VOICE (Speaker) ref', 'x'),
        ]);
    }

    public function test_sorts_the_speakers(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            self::slide(2, '¤ V (b) r', 'x', '¤ V (B) r', 'x', '¤ V (a) r', 'x', '¤'),
        ]);

        self::assertSame(['B', 'a', 'b'], array_keys($extractor->toArray()));
    }

    public function test_trims_the_speaker_and_the_reference(): void
    {
        $extractor = $this->extract([
            self::slide(1, '¤'),
            self::slide(2, '¤  VOICE OVER  ( The Guide )   ref 1  ', 'x', '¤'),
        ]);

        self::assertSame(['The Guide' => ['ref 1' => '<p>x</p>']], $extractor->toArray());
    }

    public function test_uses_the_sign_that_is_set_and_searches_the_first_notes(): void
    {
        $extractor = $this->extract([
            self::slide(1, '§ V (S) one', 'x', '§', '¤ V (S) other', 'y', '¤'),
        ], new Options(sign: '§'));

        self::assertSame('§', $extractor->sign);
        self::assertSame(['S' => ['one' => '<p>x</p>']], $extractor->toArray());
    }

    public function test_writes_plain_text(): void
    {
        $voiceOvers = $this->extract([
            self::slide(1, '¤'),
            [
                'file'  => 2,
                'notes' => [
                    '¤ V (S) text',
                    'Intro line',
                    ['runs' => [['t' => 'one', 'b' => '1'], ['br' => true], 'two']],
                    ['runs' => ['A'], 'bullet' => 'ol'],
                    ['runs' => ['B'], 'bullet' => 'ol'],
                    ['runs' => ['x', ['br' => true], 'y'], 'bullet' => 'ul', 'lvl' => 1],
                    ['runs' => ['C'], 'bullet' => 'ol'],
                    ['runs' => [' ']],
                    ['runs' => ['D'], 'bullet' => 'ol'],
                    ['runs' => ['See ', ['t' => 'the site', 'link' => 'rId1'], ' and ', ['t' => 'https://example.com', 'link' => 'rId1'], ' and ', ['t' => 'bad', 'link' => 'rId2']]],
                    '¤',
                ],
                'links' => ['rId1' => 'https://example.com', 'rId2' => 'javascript:alert'],
            ],
        ]);

        self::assertSame(
            "Intro line\none\ntwo\n1. A\n2. B\n  - x\n    y\n3. C\n1. D\nSee the site (https://example.com) and https://example.com and bad",
            $voiceOvers->toArray(Format::Text)['S']['text'],
        );
    }

    /**
     * @return array{file: int, notes: list<mixed>}
     */
    private static function slide(int $file, string ...$lines): array
    {
        return ['file' => $file, 'notes' => array_values($lines)];
    }

    /**
     * @param list<array{file: int, notes: list<mixed>|null, links?: array<string, string>}> $slides
     */
    private function extract(array $slides, ?Options $options = null): VoiceOvers
    {
        $path          = PptxBuilder::build($slides);
        $this->files[] = $path;

        return (new Extracts($path, $options ?? new Options()))->extract();
    }
}
