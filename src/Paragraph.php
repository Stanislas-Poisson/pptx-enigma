<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * A paragraph of a note: its runs of text, and its place in a list if it has one.
 */
final readonly class Paragraph
{
    /**
     * @param list<Inline> $inlines
     */
    public function __construct(
        public array $inlines,
        public ?ListItem $item = null,
    ) {
    }

    /**
     * The text of the paragraph, without any style.
     */
    public function text(): string
    {
        $text = '';

        foreach ($this->inlines as $inline) {
            $text .= $inline->text;
        }

        return $text;
    }

    /**
     * The text without the spaces around it, the non-breaking ones included.
     */
    public function trimmedText(): string
    {
        return preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $this->text()) ?? '';
    }

    public function isBlank(): bool
    {
        return '' === $this->trimmedText();
    }
}
