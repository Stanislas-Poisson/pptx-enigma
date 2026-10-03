<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * The voice-overs of a presentation, sorted by speaker, then in the order of the slides.
 *
 * @implements \IteratorAggregate<int, VoiceOver>
 */
final readonly class VoiceOvers implements \Countable, \IteratorAggregate
{
    /**
     * @param string          $sign       the sign that delimited the voice-overs
     * @param list<VoiceOver> $voiceOvers
     */
    public function __construct(
        public string $sign,
        private array $voiceOvers,
    ) {
    }

    public function count(): int
    {
        return count($this->voiceOvers);
    }

    /**
     * @return \ArrayIterator<int, VoiceOver>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->voiceOvers);
    }

    /**
     * @return array<string, array<string, string>> the content of each voice-over, by speaker then by reference
     */
    public function toArray(Format $format = Format::Html): array
    {
        $voiceOvers = [];

        foreach ($this->voiceOvers as $voiceOver) {
            $voiceOvers[$voiceOver->speaker][$voiceOver->reference] = $voiceOver->content($format);
        }

        return $voiceOvers;
    }

    public function toJson(Format $format = Format::Html): string
    {
        return json_encode($this->toArray($format), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * A fragment of HTML: a heading for each speaker and each reference, then the voice-over.
     */
    public function toHtml(): string
    {
        $html = '';
        $speaker = null;

        foreach ($this->voiceOvers as $voiceOver) {
            if ($voiceOver->speaker !== $speaker) {
                $speaker = $voiceOver->speaker;
                $html .= '<h2>' . htmlspecialchars($speaker, ENT_NOQUOTES | ENT_SUBSTITUTE) . "</h2>\n";
            }

            $html .= '<h3>' . htmlspecialchars($voiceOver->reference, ENT_NOQUOTES | ENT_SUBSTITUTE) . "</h3>\n" . $voiceOver->html . "\n";
        }

        return $html;
    }

    /**
     * A document in plain text: a title for each speaker, then each reference between brackets and its text.
     */
    public function toText(): string
    {
        $text = '';
        $speaker = null;

        foreach ($this->voiceOvers as $voiceOver) {
            if ($voiceOver->speaker !== $speaker) {
                $text .= (null === $speaker ? '' : "\n") . $voiceOver->speaker . "\n" . str_repeat('=', max(1, mb_strlen($voiceOver->speaker))) . "\n";
                $speaker = $voiceOver->speaker;
            }

            $text .= "\n[" . $voiceOver->reference . "]\n" . $voiceOver->text . "\n";
        }

        return $text;
    }
}
