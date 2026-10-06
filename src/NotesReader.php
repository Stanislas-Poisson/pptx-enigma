<?php

declare(strict_types=1);

namespace PPTXenigma;

use DOMDocument;
use InvalidArgumentException;

/**
 * Reads the notes of a presentation in the order of its slides, without
 * extracting anything on the disk.
 */
final class NotesReader
{
    private const string NS_OFFICE_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const string NS_PRESENTATION = 'http://schemas.openxmlformats.org/presentationml/2006/main';

    /**
     * @return array<int, Notes> the notes, by number of slide (from 1); a slide without notes is missing
     *
     * @throws InvalidArgumentException when the file is not a PowerPoint 2007+ presentation
     */
    public function read(string $path): array
    {
        $archive = Archive::open($path);

        try {
            return $this->readNotes($archive, $path);
        }
        finally {
            $archive->close();
        }
    }

    /**
     * The notes of a slide, found in the relationships of the slide.
     */
    private function notesOf(Archive $archive, string $slidePath): ?Notes
    {
        $target = Relationships::of($archive, $slidePath)->firstTarget('/notesSlide');

        if (null === $target) {
            return null;
        }

        $notesPath = PackagePath::resolve(dirname($slidePath), $target);
        $document  = $archive->load($notesPath);

        return $document instanceof DOMDocument ? new Notes(
            $document,
            Relationships::of($archive, $notesPath)->targets('/hyperlink'),
        ) : null;
    }

    /**
     * @return array<int, Notes>
     */
    private function readNotes(Archive $archive, string $path): array
    {
        $presentation = $archive->load('ppt/presentation.xml');

        if (! $presentation instanceof DOMDocument) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not a PowerPoint 2007+ presentation.', $path));
        }

        $slides = Relationships::of($archive, 'ppt/presentation.xml')->targets();
        $notes  = [];
        $number = 0;

        foreach ($presentation->getElementsByTagNameNS(self::NS_PRESENTATION, 'sldId') as $elementsByTagNameN) {
            $number++;
            $slidePath = $slides[$elementsByTagNameN->getAttributeNS(self::NS_OFFICE_REL, 'id')] ?? null;
            $page      = null === $slidePath ? null : $this->notesOf($archive, PackagePath::resolve('ppt', $slidePath));

            if ($page instanceof Notes) {
                $notes[$number] = $page;
            }
        }

        return $notes;
    }
}
