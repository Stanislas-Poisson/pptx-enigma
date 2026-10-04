<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * Writes the text of a run with its styles, for a plain text: the spaces around the text stay outside the marks.
 */
final readonly class EmphasisWriter
{
    public function __construct(private Emphasis $emphasis) {}

    public function write(Inline $inline): string
    {
        if (Emphasis::None === $this->emphasis || '' === trim($inline->text)) {
            return $inline->text;
        }

        return preg_replace_callback(
            '/^(\s*)(.*?)(\s*)$/su',
            fn (array $match): string => $match[1] . $this->style($inline, $match[2]) . $match[3],
            $inline->text,
        ) ?? $inline->text;
    }

    private function marks(Inline $inline, string $words): string
    {
        $words = $inline->underline ? '__' . $words . '__' : $words;
        $words = $inline->italic ? '_' . $words . '_' : $words;

        return $inline->bold ? '*' . $words . '*' : $words;
    }

    private function style(Inline $inline, string $words): string
    {
        if (Emphasis::Upper === $this->emphasis) {
            return $inline->bold ? mb_strtoupper($words) : $words;
        }

        return $this->marks($inline, $words);
    }
}
