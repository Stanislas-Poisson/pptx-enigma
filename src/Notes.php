<?php

declare(strict_types=1);

namespace PPTXenigma;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * The notes of a slide: the page, and the external links that it refers to.
 */
final readonly class Notes
{
    private const string NS_DRAWING = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    private const string NS_PRESENTATION = 'http://schemas.openxmlformats.org/presentationml/2006/main';

    /**
     * @param array<string, string> $hyperlinks the URL of each link of the page, by id of relationship
     */
    public function __construct(
        public DOMDocument $document,
        public array $hyperlinks = [],
    ) {}

    /**
     * The paragraphs of the text of the notes, which is the "body" placeholder of the page.
     *
     * @return list<Paragraph>
     */
    public function paragraphs(): array
    {
        $domxPath = new DOMXPath($this->document);
        $domxPath->registerNamespace('p', self::NS_PRESENTATION);
        $domxPath->registerNamespace('a', self::NS_DRAWING);

        $paragraphReader     = new ParagraphReader($this->hyperlinks);
        $paragraphs          = [];
        $found               = $domxPath->query('//p:sp[p:nvSpPr/p:nvPr/p:ph[@type="body"]]/p:txBody/a:p');

        foreach (false === $found ? [] : $found as $paragraph) {
            if ($paragraph instanceof DOMElement) {
                $paragraphs[] = $paragraphReader->read($paragraph);
            }
        }

        return $paragraphs;
    }
}
