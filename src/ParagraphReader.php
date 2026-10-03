<?php

declare(strict_types=1);

namespace PPTXenigma;

use DOMElement;
use DOMNode;

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
            $inline = $this->inline($child);

            if ($inline instanceof Inline) {
                $inlines[] = $inline;
            }
        }

        return new Paragraph($inlines, $this->listItem($domElement));
    }

    private function inline(DOMNode $domNode): ?Inline
    {
        if (! $domNode instanceof DOMElement || self::NS_DRAWING !== $domNode->namespaceURI) {
            return null;
        }

        if ('br' === $domNode->localName) {
            return new Inline(lineBreak: true);
        }

        return 'r' === $domNode->localName ? $this->run($domNode) : null;
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

        $type = $this->listType($properties);

        return $type instanceof ListType ? new ListItem(max(0, (int) $properties->getAttribute('lvl')), $type) : null;
    }

    private function listType(DOMElement $domElement): ?ListType
    {
        if (0 !== $domElement->getElementsByTagNameNS(self::NS_DRAWING, 'buNone')->length) {
            return null;
        }

        if (0 !== $domElement->getElementsByTagNameNS(self::NS_DRAWING, 'buAutoNum')->length) {
            return ListType::Number;
        }

        return 0 !== $domElement->getElementsByTagNameNS(self::NS_DRAWING, 'buChar')->length ? ListType::Bullet : null;
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

        if (! $link instanceof DOMElement) {
            return null;
        }

        return $this->hyperlinks[$link->getAttributeNS(self::NS_RELATIONSHIPS, 'id')] ?? null;
    }
}
