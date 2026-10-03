<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * Reads the notes of a presentation in the order of its slides, without
 * extracting anything on the disk.
 */
final class NotesReader
{
    private const NS_PACKAGE_REL = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const NS_OFFICE_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const NS_PRESENTATION = 'http://schemas.openxmlformats.org/presentationml/2006/main';

    /**
     * @return array<int, \DOMDocument> the notes, by number of slide (from 1); a slide without notes is missing
     *
     * @throws \InvalidArgumentException when the file is not a PowerPoint 2007+ presentation
     */
    public function read(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not exist or is not readable.', $path));
        }

        $zip = new \ZipArchive();

        if (true !== $zip->open($path, \ZipArchive::RDONLY)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" is not a ZIP archive.', $path));
        }

        try {
            return $this->readNotes($zip, $path);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, \DOMDocument>
     */
    private function readNotes(\ZipArchive $zip, string $path): array
    {
        $presentation = $this->load($zip, 'ppt/presentation.xml');

        if (null === $presentation) {
            throw new \InvalidArgumentException(sprintf('The file "%s" is not a PowerPoint 2007+ presentation.', $path));
        }

        $slideTargets = $this->targets($this->load($zip, 'ppt/_rels/presentation.xml.rels'));
        $notes = [];
        $number = 0;

        foreach ($presentation->getElementsByTagNameNS(self::NS_PRESENTATION, 'sldId') as $slide) {
            ++$number;

            $slidePath = $slideTargets[$slide->getAttributeNS(self::NS_OFFICE_REL, 'id')] ?? null;

            if (null === $slidePath) {
                continue;
            }

            $notesPath = $this->notesPath($zip, $this->resolve('ppt', $slidePath));
            $document = null === $notesPath ? null : $this->load($zip, $notesPath);

            if (null !== $document) {
                $notes[$number] = $document;
            }
        }

        return $notes;
    }

    /**
     * The path of the notes of a slide, found in the relationships of the slide.
     */
    private function notesPath(\ZipArchive $zip, string $slidePath): ?string
    {
        $relationships = $this->load($zip, dirname($slidePath) . '/_rels/' . basename($slidePath) . '.rels');
        $xpath = new \DOMXPath($relationships ?? new \DOMDocument());
        $xpath->registerNamespace('rel', self::NS_PACKAGE_REL);

        foreach ($xpath->query('//rel:Relationship') ?: [] as $relationship) {
            if ($relationship instanceof \DOMElement && str_ends_with($relationship->getAttribute('Type'), '/notesSlide')) {
                return $this->resolve(dirname($slidePath), $relationship->getAttribute('Target'));
            }
        }

        return null;
    }

    /**
     * @return array<string, string> the target of each relationship, by id
     */
    private function targets(?\DOMDocument $relationships): array
    {
        $targets = [];
        $xpath = new \DOMXPath($relationships ?? new \DOMDocument());
        $xpath->registerNamespace('rel', self::NS_PACKAGE_REL);

        foreach ($xpath->query('//rel:Relationship') ?: [] as $relationship) {
            if ($relationship instanceof \DOMElement) {
                $targets[$relationship->getAttribute('Id')] = $relationship->getAttribute('Target');
            }
        }

        return $targets;
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
            } elseif ('' !== $segment && '.' !== $segment) {
                $segments[] = $segment;
            }
        }

        return implode('/', $segments);
    }

    private function load(\ZipArchive $zip, string $name): ?\DOMDocument
    {
        $content = $zip->getFromName($name);

        if (false === $content) {
            return null;
        }

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadXML($content, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $loaded ? $document : null;
    }
}
