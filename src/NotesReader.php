<?php

declare(strict_types=1);

namespace PPTXenigma;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use ZipArchive;

/**
 * Reads the notes of a presentation in the order of its slides, without
 * extracting anything on the disk.
 */
final class NotesReader
{
    private const string NS_OFFICE_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const string NS_PACKAGE_REL = 'http://schemas.openxmlformats.org/package/2006/relationships';

    private const string NS_PRESENTATION = 'http://schemas.openxmlformats.org/presentationml/2006/main';

    /**
     * @return array<int, Notes> the notes, by number of slide (from 1); a slide without notes is missing
     *
     * @throws InvalidArgumentException when the file is not a PowerPoint 2007+ presentation
     */
    public function read(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist or is not readable.', $path));
        }

        $zipArchive = new ZipArchive();

        if (true !== $zipArchive->open($path, ZipArchive::RDONLY)) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not a ZIP archive.', $path));
        }

        try {
            return $this->readNotes($zipArchive, $path);
        }
        finally {
            $zipArchive->close();
        }
    }

    /**
     * @return array<string, string> the URL of each external link of a notes page, by id of relationship
     */
    private function hyperlinks(ZipArchive $zipArchive, string $notesPath): array
    {
        return $this->targets($this->load($zipArchive, dirname($notesPath) . '/_rels/' . basename($notesPath) . '.rels'), '/hyperlink');
    }

    private function load(ZipArchive $zipArchive, string $name): ?DOMDocument
    {
        $content = $zipArchive->getFromName($name);

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

    /**
     * The path of the notes of a slide, found in the relationships of the slide.
     */
    private function notesPath(ZipArchive $zipArchive, string $slidePath): ?string
    {
        $relationships    = $this->load($zipArchive, dirname($slidePath) . '/_rels/' . basename($slidePath) . '.rels');
        $domxPath         = new DOMXPath($relationships ?? new DOMDocument());
        $domxPath->registerNamespace('rel', self::NS_PACKAGE_REL);

        $found = $domxPath->query('//rel:Relationship');

        foreach (false === $found ? [] : $found as $relationship) {
            if ($relationship instanceof DOMElement && str_ends_with($relationship->getAttribute('Type'), '/notesSlide')) {
                return $this->resolve(dirname($slidePath), $relationship->getAttribute('Target'));
            }
        }

        return null;
    }

    /**
     * @return array<int, Notes>
     */
    private function readNotes(ZipArchive $zipArchive, string $path): array
    {
        $presentation = $this->load($zipArchive, 'ppt/presentation.xml');

        if (! $presentation instanceof DOMDocument) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not a PowerPoint 2007+ presentation.', $path));
        }

        $slideTargets = $this->targets($this->load($zipArchive, 'ppt/_rels/presentation.xml.rels'));
        $notes        = [];
        $number       = 0;

        foreach ($presentation->getElementsByTagNameNS(self::NS_PRESENTATION, 'sldId') as $elementsByTagNameN) {
            $number++;

            $slidePath = $slideTargets[$elementsByTagNameN->getAttributeNS(self::NS_OFFICE_REL, 'id')] ?? null;

            if (null === $slidePath) {
                continue;
            }

            $notesPath = $this->notesPath($zipArchive, $this->resolve('ppt', $slidePath));
            $document  = null === $notesPath ? null : $this->load($zipArchive, $notesPath);

            if (null !== $notesPath && $document instanceof DOMDocument) {
                $notes[$number] = new Notes($document, $this->hyperlinks($zipArchive, $notesPath));
            }
        }

        return $notes;
    }

    /**
     * Resolves a target of a relationship, absolute or relative to a directory of the archive.
     */
    private function resolve(string $directory, string $target): string
    {
        $segments = str_starts_with($target, '/') ? [] : explode('/', $directory);

        foreach (explode('/', $target) as $segment) {
            if ('..' === $segment) {
                array_pop($segments);
            }
            elseif ('' !== $segment && '.' !== $segment) {
                $segments[] = $segment;
            }
        }

        return implode('/', $segments);
    }

    /**
     * @return array<string, string> the target of each relationship, by id
     */
    private function targets(?DOMDocument $domDocument, ?string $type = null): array
    {
        $targets = [];

        foreach ($domDocument?->getElementsByTagNameNS(self::NS_PACKAGE_REL, 'Relationship') ?? [] as $relationship) {
            if (null === $type || str_ends_with($relationship->getAttribute('Type'), $type)) {
                $targets[$relationship->getAttribute('Id')] = $relationship->getAttribute('Target');
            }
        }

        return $targets;
    }
}
