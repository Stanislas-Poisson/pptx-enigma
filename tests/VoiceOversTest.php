<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use PHPUnit\Framework\TestCase;
use PPTXenigma\Extracts;
use PPTXenigma\Format;
use PPTXenigma\VoiceOver;
use PPTXenigma\VoiceOvers;

final class VoiceOversTest extends TestCase
{
    public function test_an_empty_result(): void
    {
        $voiceOvers = new VoiceOvers('¤', []);

        self::assertCount(0, $voiceOvers);
        self::assertSame([], $voiceOvers->toArray());
        self::assertSame('', $voiceOvers->toHtml());
        self::assertSame('', $voiceOvers->toText());
    }

    public function test_counts_and_iterates_the_voice_overs(): void
    {
        $voiceOvers = $this->sample();

        self::assertCount(4, $voiceOvers);
        self::assertSame('¤', $voiceOvers->sign);
        self::assertSame(
            ['Guide/w02_guide/3', 'Guide/w03_goodbye/4', 'Narrator/w01_intro/2', 'Narrator/w02_list/3'],
            array_map(static fn (VoiceOver $voiceOver): string => $voiceOver->speaker . '/' . $voiceOver->reference . '/' . $voiceOver->slide, iterator_to_array($voiceOvers)),
        );
    }

    public function test_escapes_the_names_in_the_html_fragment(): void
    {
        $html = (new VoiceOvers('¤', [new VoiceOver('A <b>', 'r & s', 1, '<p>x</p>', 'x')]))->toHtml();

        self::assertSame("<h2>A &lt;b&gt;</h2>\n<h3>r &amp; s</h3>\n<p>x</p>\n", $html);
    }

    public function test_writes_an_array_and_json(): void
    {
        $voiceOvers = $this->sample();

        self::assertSame(['Guide', 'Narrator'], array_keys($voiceOvers->toArray()));
        self::assertSame('<p>Hello, I am the guide.</p>', $voiceOvers->toArray()['Guide']['w02_guide']);
        self::assertSame('Hello, I am the guide.', $voiceOvers->toArray(Format::Text)['Guide']['w02_guide']);
        self::assertSame($voiceOvers->toArray(), json_decode($voiceOvers->toJson(), true, 512, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('"w02_guide": "<p>Hello, I am the guide.</p>"', $voiceOvers->toJson());
    }

    public function test_writes_an_html_fragment(): void
    {
        $html = $this->sample()->toHtml();

        self::assertStringStartsWith("<h2>Guide</h2>\n<h3>w02_guide</h3>\n<p>Hello, I am the guide.</p>\n<h3>w03_goodbye</h3>", $html);
        self::assertSame(2, substr_count($html, '<h2>'));
        self::assertSame(4, substr_count($html, '<h3>'));
    }

    public function test_writes_a_text_document(): void
    {
        $text = $this->sample()->toText();

        self::assertStringStartsWith("Guide\n=====\n\n[w02_guide]\nHello, I am the guide.\n\n[w03_goodbye]\nThat is all, thank you.\n\nNarrator\n========\n\n[w01_intro]\n", $text);
        self::assertStringEndsWith("- References identify each voice-over.\n", $text);
    }

    public function test_writes_the_content_in_the_format_that_is_asked(): void
    {
        $voiceOver = iterator_to_array($this->sample())[3];

        self::assertSame('<p>Three things to remember:</p><ul><li>Notes are read slide by slide.</li><li>Voices are grouped by speaker.</li><li>References identify each voice-over.</li></ul>', $voiceOver->content(Format::Html));
        self::assertSame("Three things to remember:\n- Notes are read slide by slide.\n- Voices are grouped by speaker.\n- References identify each voice-over.", $voiceOver->content(Format::Text));
    }

    private function sample(): VoiceOvers
    {
        return (new Extracts(__DIR__ . '/../examples/sample.pptx'))->extract();
    }
}
