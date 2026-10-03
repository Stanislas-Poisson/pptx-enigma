<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * Writes paragraphs as plain text: one line per paragraph, "-" and "1." for the lists, and the URL
 * of a link after its text.
 */
final readonly class TextRenderer
{
    public function __construct(private Options $options)
    {
    }

    /**
     * @param list<Paragraph> $paragraphs
     */
    public function render(array $paragraphs): string
    {
        $lines = [];
        /** @var array<int, int> $counters the number of the last numbered item, by level */
        $counters = [];

        foreach ($paragraphs as $paragraph) {
            $item = $paragraph->item;

            if ($paragraph->isBlank()) {
                $counters = [];

                continue;
            }

            if (null === $item) {
                $counters = [];
                $lines[] = $this->inlines($paragraph, '');

                continue;
            }

            $counters = array_filter($counters, static fn (int $level): bool => $level <= $item->level, ARRAY_FILTER_USE_KEY);

            if (ListType::Number === $item->type) {
                $counters[$item->level] = ($counters[$item->level] ?? 0) + 1;
                $marker = $counters[$item->level] . '.';
            } else {
                unset($counters[$item->level]);
                $marker = '-';
            }

            $indent = str_repeat('  ', $item->level);
            $lines[] = $indent . $marker . ' ' . $this->inlines($paragraph, $indent . '  ');
        }

        return implode("\n", $lines);
    }

    private function inlines(Paragraph $paragraph, string $indent): string
    {
        $text = '';

        foreach ($paragraph->inlines as $inline) {
            if ($inline->lineBreak) {
                $text .= "\n" . $indent;

                continue;
            }

            $url = Links::safeUrl($inline->url, $this->options);
            $text .= null === $url || $url === $inline->text ? $inline->text : $inline->text . ' (' . $url . ')';
        }

        return $text;
    }
}
