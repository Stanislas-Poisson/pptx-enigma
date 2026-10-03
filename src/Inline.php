<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * A run of text with its style, or a line break.
 */
final readonly class Inline
{
    /**
     * @param int $baseline above 0 for a superscript, below 0 for a subscript
     */
    public function __construct(
        public string $text = '',
        public bool $lineBreak = false,
        public bool $bold = false,
        public bool $italic = false,
        public bool $underline = false,
        public bool $strike = false,
        public int $baseline = 0,
        public ?string $url = null,
    ) {
    }
}
