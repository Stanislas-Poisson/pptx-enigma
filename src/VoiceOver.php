<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * A voice-over: who says it, its reference, the slide it comes from and its content.
 */
final readonly class VoiceOver
{
    public function __construct(
        public string $speaker,
        public string $reference,
        public int $slide,
        public string $html,
        public string $text,
    ) {}

    public function content(Format $format): string
    {
        return Format::Html === $format ? $this->html : $this->text;
    }
}
