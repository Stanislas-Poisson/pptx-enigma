<?php

declare(strict_types=1);

namespace PPTXenigma;

use DOMElement;

/**
 * Reads a paragraph of a note: the style of each run, the line breaks, the links and the list item.
 */
final readonly class ParagraphReader
{
    private const string NS_DRAWING = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    private const string NS_RELATIONSHIPS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * @param array<string, string> $hyperlinks the URL of each link of the notes, by id of relationship
     */
    public function __construct(private array $hyperlinks = []) {}

    public function read(DOMElement $domElement): Paragraph
    {
        $inlines = [];

        foreach ($domElement->childNodes as $child) {
            if (! $child instanceof DOMElement || self::NS_DRAWING !== $child->namespaceURI) {
                continue;
            }

            if ('br' === $child->localName) {
                $inlines[] = new Inline(lineBreak: true);
            }
            elseif ('r' === $child->localName) {
                $inlines[] = $this->run($child);
            }
        }

        return new Paragraph($inlines, $this->listItem($domElement));
    }

    private function isStyled(string $value): bool
    {
        return ! in_array($value, ['', '0', 'false', 'none', 'noStrike'], true);
    }

    /**
     * A paragraph is a list item only when it has a bullet or a number.
     */
    private function listItem(DOMElement $domElement): ?ListItem
    {
        $properties = $domElement->getElementsByTagNameNS(self::NS_DRAWING, 'pPr')->item(0);

        if (! $properties instanceof DOMElement) {
            return null;
        }

        $type = null;

        foreach ($properties->childNodes as $child) {
            if ('buNone' === $child->localName) {
                return null;
            }

            if ('buChar' === $child->localName) {
                $type = ListType::Bullet;
            }
            elseif ('buAutoNum' === $child->localName) {
                $type = ListType::Number;
            }
        }

        return null === $type ? null : new ListItem(max(0, (int) $properties->getAttribute('lvl')), $type);
    }

    private function run(DOMElement $domElement): Inline
    {
        $properties = $domElement->getElementsByTagNameNS(self::NS_DRAWING, 'rPr')->item(0);

        if (! $properties instanceof DOMElement) {
            return new Inline($domElement->textContent);
        }

        return new Inline(
            text: $domElement->textContent,
            bold: $this->isStyled($properties->getAttribute('b')),
            italic: $this->isStyled($properties->getAttribute('i')),
            underline: $this->isStyled($properties->getAttribute('u')),
            strike: $this->isStyled($properties->getAttribute('strike')),
            baseline: (int) $properties->getAttribute('baseline'),
            url: $this->url($properties),
        );
    }

    private function url(DOMElement $domElement): ?string
    {
        $link = $domElement->getElementsByTagNameNS(self::NS_DRAWING, 'hlinkClick')->item(0);

        return $link instanceof DOMElement ? ($this->hyperlinks[$link->getAttributeNS(self::NS_RELATIONSHIPS, 'id')] ?? null) : null;
    }
}
