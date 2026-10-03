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
        $html      = '';
        $openLists = new OpenLists();

        foreach ($paragraphs as $paragraph) {
            $item = $paragraph->item;

            if ($paragraph->isBlank() || null === $item) {
                $html .= $openLists->closeAll() . $this->paragraph($paragraph);

                continue;
            }

            $type = ListType::Bullet === $item->type ? 'ul' : 'ol';
            $html .= $openLists->enter($item->level, $type) . '<li>' . $this->inlines($paragraph);
        }

        return $html . $openLists->closeAll();
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

        foreach (array_keys(array_filter($styles)) as $style) {
            $tag = $this->options->htmlTag($style);
            $begin .= '<' . $tag . '>';
            $end = '</' . $tag . '>' . $end;
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

    private function paragraph(Paragraph $paragraph): string
    {
        return $paragraph->isBlank() ? '' : '<p>' . $this->inlines($paragraph) . '</p>';
    }
}
