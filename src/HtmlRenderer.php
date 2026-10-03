<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * Writes paragraphs as HTML: the styles, the links, the line breaks and the lists.
 */
final readonly class HtmlRenderer
{
    public function __construct(private Options $options) {}

    /**
     * @param list<Paragraph> $paragraphs
     */
    public function render(array $paragraphs): string
    {
        $html = '';

        /** @var list<string> $open the tag of each opened list, from the outermost */
        $open = [];

        foreach ($paragraphs as $paragraph) {
            $item = $paragraph->item;

            if ($paragraph->isBlank() || null === $item) {
                $html .= $this->closeLists($open);
                $open = [];

                if (! $paragraph->isBlank()) {
                    $html .= '<p>' . $this->inlines($paragraph) . '</p>';
                }

                continue;
            }

            $type  = ListType::Bullet === $item->type ? 'ul' : 'ol';
            $level = min($item->level, count($open));

            while (count($open) > $level + 1) {
                $html .= '</li></' . array_pop($open) . '>';
            }

            if (count($open) === $level + 1) {
                $html .= '</li>';

                if ($open[$level] !== $type) {
                    $html .= '</' . array_pop($open) . '><' . $type . '>';
                    $open[] = $type;
                }
            }
            else {
                $html .= '<' . $type . '>';
                $open[] = $type;
            }

            $html .= '<li>' . $this->inlines($paragraph);
        }

        return $html . $this->closeLists($open);
    }

    /**
     * @param list<string> $open
     */
    private function closeLists(array $open): string
    {
        $html = '';

        while ([] !== $open) {
            $html .= '</li></' . array_pop($open) . '>';
        }

        return $html;
    }

    private function inline(Inline $inline): string
    {
        $begin  = '';
        $end    = '';
        $styles = [
            'bold'        => $inline->bold,
            'italic'      => $inline->italic,
            'underline'   => $inline->underline,
            'strike'      => $inline->strike,
            'superscript' => 0 < $inline->baseline,
            'subscript'   => 0 > $inline->baseline,
        ];

        foreach ($styles as $style => $applies) {
            if ($applies) {
                $begin .= '<' . $this->options->htmlTags[$style] . '>';
                $end = '</' . $this->options->htmlTags[$style] . '>' . $end;
            }
        }

        $url = Links::safeUrl($inline->url, $this->options);

        if (null !== $url) {
            $begin = '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE) . '">' . $begin;
            $end .= '</a>';
        }

        return $begin . htmlspecialchars($inline->text, ENT_NOQUOTES | ENT_SUBSTITUTE) . $end;
    }

    private function inlines(Paragraph $paragraph): string
    {
        $html = '';

        foreach ($paragraph->inlines as $inline) {
            $html .= $inline->lineBreak ? '<br>' : $this->inline($inline);
        }

        return $html;
    }
}
