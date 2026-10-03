<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * Reads a paragraph of a note: the style of each run, the line breaks, the links and the list item.
 */
final readonly class ParagraphReader
{
    private const NS_DRAWING = 'http://schemas.openxmlformats.org/drawingml/2006/main';
    private const NS_RELATIONSHIPS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * @param array<string, string> $hyperlinks the URL of each link of the notes, by id of relationship
     */
    public function __construct(private array $hyperlinks = [])
    {
    }

    public function read(\DOMElement $paragraph): Paragraph
    {
        $inlines = [];

        foreach ($paragraph->childNodes as $child) {
            if (!$child instanceof \DOMElement || self::NS_DRAWING !== $child->namespaceURI) {
                continue;
            }

            if ('br' === $child->localName) {
                $inlines[] = new Inline(lineBreak: true);
            } elseif ('r' === $child->localName) {
                $inlines[] = $this->run($child);
            }
        }

        return new Paragraph($inlines, $this->listItem($paragraph));
    }

    private function run(\DOMElement $run): Inline
    {
        $properties = $run->getElementsByTagNameNS(self::NS_DRAWING, 'rPr')->item(0);

        if (!$properties instanceof \DOMElement) {
            return new Inline($run->textContent);
        }

        return new Inline(
            text: $run->textContent,
            bold: $this->isStyled($properties->getAttribute('b')),
            italic: $this->isStyled($properties->getAttribute('i')),
            underline: $this->isStyled($properties->getAttribute('u')),
            strike: $this->isStyled($properties->getAttribute('strike')),
            baseline: (int) $properties->getAttribute('baseline'),
            url: $this->url($properties),
        );
    }

    private function isStyled(string $value): bool
    {
        return !in_array($value, ['', '0', 'false', 'none', 'noStrike'], true);
    }

    private function url(\DOMElement $properties): ?string
    {
        $link = $properties->getElementsByTagNameNS(self::NS_DRAWING, 'hlinkClick')->item(0);

        return $link instanceof \DOMElement ? ($this->hyperlinks[$link->getAttributeNS(self::NS_RELATIONSHIPS, 'id')] ?? null) : null;
    }

    /**
     * A paragraph is a list item only when it has a bullet or a number.
     */
    private function listItem(\DOMElement $paragraph): ?ListItem
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
                $type = ListType::Bullet;
            } elseif ('buAutoNum' === $child->localName) {
                $type = ListType::Number;
            }
        }

        return null === $type ? null : new ListItem(max(0, (int) $properties->getAttribute('lvl')), $type);
    }
}
