<?php

declare(strict_types=1);

namespace PPTXenigma;

use DOMDocument;
use InvalidArgumentException;
use ZipArchive;

/**
 * A presentation, which is a ZIP archive that is read in memory.
 */
final readonly class Archive
{
    private function __construct(private ZipArchive $zipArchive) {}

    /**
     * @throws InvalidArgumentException when the file cannot be read or is not a ZIP archive
     */
    public static function open(string $path): self
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist or is not readable.', $path));
        }

        $zipArchive = new ZipArchive();

        if (true !== $zipArchive->open($path, ZipArchive::RDONLY)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not a ZIP archive.', $path));
        }

        return new self($zipArchive);
    }

    public function close(): void
    {
        $this->zipArchive->close();
    }

    /**
     * An XML file of the archive, or null if it is missing or is not valid XML. The external entities are not loaded.
     */
    public function load(string $name): ?DOMDocument
    {
        $content = $this->zipArchive->getFromName($name);

        if (false === $content) {
            return null;
        }

        $domDocument = new DOMDocument();
        $previous    = libxml_use_internal_errors(true);

        try {
            $loaded = $domDocument->loadXML($content, LIBXML_NONET);
        }
        finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $loaded ? $domDocument : null;
    }
}
