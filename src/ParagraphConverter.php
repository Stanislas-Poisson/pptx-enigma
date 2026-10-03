<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * Converts the paragraphs of a note into HTML: the styles of the text and the lists.
 */
final class ParagraphConverter
{
    private const NS_DRAWING = 'http://schemas.openxmlformats.org/drawingml/2006/main';
    private const NS_RELATIONSHIPS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const LINK_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * The HTML tag of each style, written in the order in which the tags are opened.
     */
    private const STYLES = ['b' => 'b', 'i' => 'i', 'u' => 'u', 'strike' => 's'];

    /**
     * @param array<string, string> $hyperlinks the URL of each link of the notes, by id of relationship
     */
    public function __construct(private readonly array $hyperlinks = [])
    {
    }

    /**
     * The text of a paragraph, without any style.
     */
    public function text(\DOMElement $paragraph): string
    {
        $text = '';

        foreach ($this->inlines($paragraph) as $inline) {
            $text .= 'r' === $inline->localName ? $inline->textContent : '';
        }

        return $text;
    }

    /**
     * The text of a paragraph, without the spaces around it, the non-breaking ones included.
     */
    public function trimmedText(\DOMElement $paragraph): string
    {
        return preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $this->text($paragraph)) ?? '';
    }

    /**
     * @param list<\DOMElement> $paragraphs
     */
    public function convert(array $paragraphs): string
    {
        $html = '';
        /** @var list<string> $open the type of each opened list, from the outermost */
        $open = [];

        foreach ($paragraphs as $paragraph) {
            $content = $this->content($paragraph);
            $list = $this->listItem($paragraph);

            if (null === $list || '' === $content) {
                $html .= $this->closeLists($open);
                $open = [];

                if ('' !== $content) {
                    $html .= '<p>' . $content . '</p>';
                }

                continue;
            }

            [$level, $type] = $list;
            $level = min($level, count($open));

            while (count($open) > $level + 1) {
                $html .= '</li></' . array_pop($open) . '>';
            }

            if (count($open) === $level + 1) {
                $html .= '</li>';

                if ($open[$level] !== $type) {
                    $html .= '</' . array_pop($open) . '><' . $type . '>';
                    $open[] = $type;
                }
            } else {
                $html .= '<' . $type . '>';
                $open[] = $type;
            }

            $html .= '<li>' . $content;
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

    /**
     * @return array{int, string}|null the level and the type ("ul" or "ol") of the list item, or null for a paragraph
     */
    private function listItem(\DOMElement $paragraph): ?array
    {
        $properties = $paragraph->getElementsByTagNameNS(self::NS_DRAWING, 'pPr')->item(0);

        if (!$properties instanceof \DOMElement) {
            return null;
        }

        $type = null;

        foreach ($properties->childNodes as $child) {
            if ('buNone' === $child->localName) {
                return null;
            }

            if ('buChar' === $child->localName) {
                $type = 'ul';
            } elseif ('buAutoNum' === $child->localName) {
                $type = 'ol';
            }
        }

        return null === $type ? null : [max(0, (int) $properties->getAttribute('lvl')), $type];
    }

    private function content(\DOMElement $paragraph): string
    {
        if ('' === $this->trimmedText($paragraph)) {
            return '';
        }

        $html = '';

        foreach ($this->inlines($paragraph) as $inline) {
            $html .= 'br' === $inline->localName ? '<br>' : $this->run($inline);
        }

        return $html;
    }

    private function run(\DOMElement $run): string
    {
        $text = htmlspecialchars($run->textContent, ENT_NOQUOTES | ENT_SUBSTITUTE);
        $properties = $run->getElementsByTagNameNS(self::NS_DRAWING, 'rPr')->item(0);
        $begin = '';
        $end = '';

        if ($properties instanceof \DOMElement) {
            foreach (self::STYLES as $attribute => $tag) {
                if ($this->isStyled($properties->getAttribute($attribute))) {
                    $begin .= '<' . $tag . '>';
                    $end = '</' . $tag . '>' . $end;
                }
            }

            $baseline = (int) $properties->getAttribute('baseline');

            if (0 !== $baseline) {
                $tag = $baseline > 0 ? 'sup' : 'sub';
                $begin .= '<' . $tag . '>';
                $end = '</' . $tag . '>' . $end;
            }

            $url = $this->hyperlink($properties);

            if (null !== $url) {
                $begin = '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE) . '">' . $begin;
                $end .= '</a>';
            }
        }

        return $begin . $text . $end;
    }

    /**
     * The URL of the link of a run, if it has one with a safe scheme.
     */
    private function hyperlink(\DOMElement $properties): ?string
    {
        $link = $properties->getElementsByTagNameNS(self::NS_DRAWING, 'hlinkClick')->item(0);

        if (!$link instanceof \DOMElement) {
            return null;
        }

        $url = $this->hyperlinks[$link->getAttributeNS(self::NS_RELATIONSHIPS, 'id')] ?? null;

        if (null === $url) {
            return null;
        }

        $compact = preg_replace('/[\x00-\x20]+/', '', $url) ?? '';

        if (1 !== preg_match('/^([a-z][a-z0-9+.\-]*):/i', $compact, $scheme) || !in_array(strtolower($scheme[1]), self::LINK_SCHEMES, true)) {
            return null;
        }

        return $url;
    }

    private function isStyled(string $value): bool
    {
        return !in_array($value, ['', '0', 'false', 'none', 'noStrike'], true);
    }

    /**
     * The runs of text and the line breaks of a paragraph, in order.
     *
     * @return list<\DOMElement>
     */
    private function inlines(\DOMElement $paragraph): array
    {
        $inlines = [];

        foreach ($paragraph->childNodes as $child) {
            if ($child instanceof \DOMElement && self::NS_DRAWING === $child->namespaceURI && in_array($child->localName, ['r', 'br'], true)) {
                $inlines[] = $child;
            }
        }

        return $inlines;
    }
}
