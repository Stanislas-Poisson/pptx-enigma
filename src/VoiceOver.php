<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * A voice-over: who says it, its reference, the slide it comes from and its content.
 */
final readonly class VoiceOver
{
    /**
     * @param array<string, string> $emphasized the text with its styles written, by emphasis ("marks" and "upper")
     */
    public function __construct(
        public string $speaker,
        public string $reference,
        public int $slide,
        public string $html,
        public string $text,
        private array $emphasized = [],
    ) {}

    public function content(Format $format): string
    {
        return Format::Html === $format ? $this->html : $this->text;
    }

    /**
     * The plain text, with its styles written as asked. Without styles in the notes, it is the same text.
     */
    public function text(Emphasis $emphasis = Emphasis::None): string
    {
        return $this->emphasized[$emphasis->value] ?? $this->text;
    }
}
