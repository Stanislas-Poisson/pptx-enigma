<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use PHPUnit\Framework\TestCase;
use PPTXenigma\Emphasis;
use PPTXenigma\Extracts;
use PPTXenigma\Layout;
use PPTXenigma\MissingDependencyException;
use PPTXenigma\ScriptOptions;
use PPTXenigma\VoiceOver;
use PPTXenigma\VoiceOvers;

final class ScriptTest extends TestCase
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

    public function test_counts_the_voice_overs_and_the_words_without_the_marks_of_the_styles(): void
    {
        $script = $this->styled()->script('Narrator');

        self::assertCount(2, $script);
        self::assertSame(11, $script->wordCount());
    }

    public function test_escapes_the_speaker_in_the_html(): void
    {
        $voiceOvers = new VoiceOvers('¤', [new VoiceOver('A <b> & "c"', 'r', 1, '<p>x</p>', 'x')]);

        self::assertStringContainsString('<h1>A &lt;b&gt; &amp; &quot;c&quot;</h1>', $voiceOvers->script('A <b> & "c"')->toHtml());
    }

    public function test_has_a_standalone_html_document_that_keeps_the_formatting(): void
    {
        $html = $this->styled()->script('Narrator')->toHtml();

        self::assertStringStartsWith("<!DOCTYPE html>\n<html>", $html);
        self::assertStringContainsString('<title>Narrator</title>', $html);
        self::assertStringContainsString('<h1>Narrator</h1>', $html);
        self::assertStringContainsString('<p class="counts">2 voice-overs, 11 words</p>', $html);
        self::assertStringContainsString('<b>strong</b>', $html);
        self::assertStringContainsString('<i>soft</i>', $html);
        self::assertStringContainsString('<p class="reference">[w01] (slide 2)</p>', $html);
    }

    public function test_has_one_voice_over_and_one_word_in_the_singular(): void
    {
        $voiceOvers = new VoiceOvers('¤', [new VoiceOver('A', 'r', 1, '<p>Hi</p>', 'Hi')]);

        self::assertStringContainsString('1 voice-over, 1 word', $voiceOvers->script('A')->toText());
        self::assertStringContainsString('<p class="counts">1 voice-over, 1 word</p>', $voiceOvers->script('A')->toHtml());
    }

    public function test_has_the_layouts_in_the_html(): void
    {
        $voiceOvers = $this->plain();
        $above      = $voiceOvers->script('Guide', new ScriptOptions(Layout::ReferenceAbove, showSlides: false))->toHtml();
        $below      = $voiceOvers->script('Guide', new ScriptOptions(Layout::ReferenceBelow, showSlides: false))->toHtml();
        $inline     = $voiceOvers->script('Guide', new ScriptOptions(Layout::ReferenceInline, showSlides: false))->toHtml();
        $none       = $voiceOvers->script('Guide', new ScriptOptions(Layout::TextOnly, showCounts: false))->toHtml();

        self::assertStringContainsString("<p class=\"reference\">[w01]</p>\n<p>Hello, I am the guide.</p>", $above);
        self::assertStringContainsString("<p>Hello, I am the guide.</p>\n<p class=\"reference\">[w01]</p>", $below);
        self::assertStringContainsString('<p><span class="reference">[w01]</span> Hello, I am the guide.</p>', $inline);
        self::assertStringNotContainsString('[w01]', $none);
        self::assertStringNotContainsString('class="counts"', $none);
    }

    public function test_has_the_reference_above_the_text_by_default(): void
    {
        $text = $this->plain()->script('Guide')->toText();

        self::assertSame(
            "Guide\n=====\n2 voice-overs, 7 words\n\n[w01] (slide 2)\nHello, I am the guide.\n\n[w02] (slide 2)\nThank you.\n",
            $text,
        );
    }

    public function test_has_the_reference_below_inline_or_left_out(): void
    {
        self::assertSame(
            "Guide\n=====\n\nHello, I am the guide.\n[w01]\n\nThank you.\n[w02]\n",
            $this->plain()->script('Guide', new ScriptOptions(Layout::ReferenceBelow, showSlides: false, showCounts: false))->toText(),
        );
        self::assertSame(
            "Guide\n=====\n\n[w01] Hello, I am the guide.\n\n[w02] Thank you.\n",
            $this->plain()->script('Guide', new ScriptOptions(Layout::ReferenceInline, showSlides: false, showCounts: false))->toText(),
        );
        self::assertSame(
            "Guide\n=====\n\nHello, I am the guide.\n\nThank you.\n",
            $this->plain()->script('Guide', new ScriptOptions(Layout::TextOnly, showSlides: false, showCounts: false))->toText(),
        );
    }

    public function test_names_the_speaker(): void
    {
        self::assertSame('Guide', $this->plain()->script('Guide')->speaker);
    }

    public function test_puts_an_inline_reference_on_its_own_line_before_a_list(): void
    {
        $voiceOvers = new VoiceOvers('¤', [new VoiceOver('A', 'r', 1, '<ul><li>x</li></ul>', '- x')]);

        self::assertStringContainsString(
            "<p><span class=\"reference\">[r]</span></p>\n<ul><li>x</li></ul>",
            $voiceOvers->script('A', new ScriptOptions(Layout::ReferenceInline, showSlides: false))->toHtml(),
        );
    }

    public function test_the_missing_dependency_says_what_to_install(): void
    {
        self::assertStringContainsString('composer require dompdf/dompdf', MissingDependencyException::pdf()->getMessage());
    }

    public function test_writes_a_pdf(): void
    {
        $pdf = $this->plain()->script('Guide')->toPdf();

        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertGreaterThan(1000, strlen($pdf));
    }

    public function test_writes_the_scripts_of_the_bakery_example(): void
    {
        $voiceOvers = (new Extracts(__DIR__ . '/../examples/bakery.pptx'))->extract();

        self::assertSame(['Baker', 'Customer', 'Narrator'], $voiceOvers->speakers());

        $baker = $voiceOvers->script('Baker')->toText();

        self::assertStringContainsString("1. 500 g of flour\n2. 350 ml of water", $baker);
        self::assertStringContainsString('Mix them, then *__wait__*.', $baker);
        self::assertStringContainsString('  - never open the door', $baker);
        self::assertStringContainsString(
            "Waiting is the hardest part.\nTwo hours, without touching it.",
            $voiceOvers->script('Narrator')->toText(),
        );
        self::assertStringContainsString('à l\'été', $voiceOvers->script('Customer')->toText());
        self::assertStringContainsString(
            'our website (https://example.com/menu)',
            $voiceOvers->script('Customer')->toText(),
        );
    }

    public function test_writes_the_styles_as_marks_in_uppercase_or_not_at_all(): void
    {
        $voiceOvers = $this->styled();
        $options    = static fn (Emphasis $emphasis): ScriptOptions => new ScriptOptions(Layout::TextOnly, $emphasis, false, false);

        self::assertStringContainsString("Say *strong* and _soft_ and __under__ and *_both_* now\n", $voiceOvers->script('Narrator', $options(Emphasis::Marks))->toText());
        self::assertStringContainsString("Say STRONG and soft and under and BOTH now\n", $voiceOvers->script('Narrator', $options(Emphasis::Upper))->toText());
        self::assertStringContainsString("Say strong and soft and under and both now\n", $voiceOvers->script('Narrator', $options(Emphasis::None))->toText());
    }

    /**
     * @param list<array{file: int, notes: list<mixed>|null}> $slides
     */
    private function extract(array $slides): VoiceOvers
    {
        $path          = PptxBuilder::build($slides);
        $this->files[] = $path;

        return (new Extracts($path))->extract();
    }

    private function plain(): VoiceOvers
    {
        return $this->extract([
            ['file' => 1, 'notes' => ['¤']],
            ['file' => 2, 'notes' => ['¤ V (Guide) w01', 'Hello, I am the guide.', '¤ V (Guide) w02', 'Thank you.', '¤']],
        ]);
    }

    private function styled(): VoiceOvers
    {
        return $this->extract([
            ['file' => 1, 'notes' => ['¤']],
            ['file' => 2, 'notes' => [
                '¤ V (Narrator) w01',
                ['runs' => [
                    'Say ',
                    ['t' => 'strong', 'b' => '1'],
                    ' and ',
                    ['t' => 'soft', 'i' => '1'],
                    ' and ',
                    ['t' => 'under', 'u' => '1'],
                    ' and ',
                    ['t' => 'both', 'b' => '1', 'i' => '1'],
                    ' now',
                ]],
                '¤ V (Narrator) w02',
                'The end.',
                '¤',
            ]],
        ]);
    }
}
