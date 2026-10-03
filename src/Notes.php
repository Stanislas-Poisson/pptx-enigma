<?php

declare(strict_types=1);

namespace PPTXenigma;

use DOMDocument;

/**
 * The notes of a slide: the page, and the external links that it refers to.
 */
final readonly class Notes
{
    /**
     * @param array<string, string> $hyperlinks the URL of each link of the page, by id of relationship
     */
    public function __construct(
        public DOMDocument $document,
        public array $hyperlinks = [],
    ) {}
}
