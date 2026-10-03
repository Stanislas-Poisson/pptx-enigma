<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PPTXenigma\NotesReader;

final class NotesReaderTest extends TestCase
{
    private const NOTES_TYPE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/notesSlide';

    private const PRESENTATION = 'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';

    private const RELATIONSHIPS = 'xmlns="http://schemas.openxmlformats.org/package/2006/relationships"';

    /**
     * @var list<string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    public function test_a_presentation_without_relationships_has_no_notes(): void
    {
        $notes = (new NotesReader())->read($this->archive([
            'ppt/presentation.xml' => '<p:presentation ' . self::PRESENTATION . '><p:sldIdLst><p:sldId id="1" r:id="rId1"/></p:sldIdLst></p:presentation>',
        ]));

        self::assertSame([], $notes);
    }

    public function test_does_not_load_external_entities(): void
    {
        $secret = $this->files[] = (string) tempnam(sys_get_temp_dir(), 'secret');
        file_put_contents($secret, 'TOP-SECRET');

        $notes = (new NotesReader())->read($this->archive([
            'ppt/presentation.xml'             => '<p:presentation ' . self::PRESENTATION . '><p:sldIdLst><p:sldId id="1" r:id="rId1"/></p:sldIdLst></p:presentation>',
            'ppt/_rels/presentation.xml.rels'  => '<Relationships ' . self::RELATIONSHIPS . '><Relationship Id="rId1" Type="x/slide" Target="slides/slide1.xml"/></Relationships>',
            'ppt/slides/_rels/slide1.xml.rels' => '<Relationships ' . self::RELATIONSHIPS . '><Relationship Id="a" Type="' . self::NOTES_TYPE . '" Target="../notesSlides/notesSlide1.xml"/></Relationships>',
            'ppt/notesSlides/notesSlide1.xml'  => '<!DOCTYPE n [<!ENTITY x SYSTEM "file://' . $secret . '">]><notes>&x;</notes>',
        ]));

        self::assertStringNotContainsString('TOP-SECRET', $notes[1]->document->saveXML() ?: '');
    }

    public function test_rejects_a_file_that_is_not_an_archive(): void
    {
        $path = $this->files[] = (string) tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($path, 'not a zip');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a ZIP archive');

        (new NotesReader())->read($path);
    }

    public function test_rejects_a_missing_file(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist or is not readable');

        (new NotesReader())->read(__DIR__ . '/missing.pptx');
    }

    public function test_rejects_an_archive_that_is_not_a_presentation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a PowerPoint 2007+ presentation');

        (new NotesReader())->read($this->archive(['readme.txt' => 'hello']));
    }

    public function test_resolves_absolute_targets_and_skips_what_is_missing(): void
    {
        $slide = static fn (int $number): string => '<Relationship Id="rId' . $number . '" Type="x/slide" Target="/ppt/slides/slide' . $number . '.xml"/>';

        $notes = (new NotesReader())->read($this->archive([
            'ppt/presentation.xml' => '<p:presentation ' . self::PRESENTATION . '><p:sldIdLst>'
                . '<p:sldId id="1" r:id="rId1"/>'  // its notes are valid
                . '<p:sldId id="2" r:id="rId9"/>'  // unknown relationship
                . '<p:sldId id="3" r:id="rId3"/>'  // no relationships file
                . '<p:sldId id="4" r:id="rId4"/>'  // no notes relationship
                . '<p:sldId id="5" r:id="rId5"/>'  // the notes file is missing
                . '<p:sldId id="6" r:id="rId6"/>'  // the notes file is not XML
                . '</p:sldIdLst></p:presentation>',
            'ppt/_rels/presentation.xml.rels'  => '<Relationships ' . self::RELATIONSHIPS . '>' . $slide(1) . $slide(3) . $slide(4) . $slide(5) . $slide(6) . '</Relationships>',
            'ppt/slides/_rels/slide1.xml.rels' => '<Relationships ' . self::RELATIONSHIPS . '><Relationship Id="a" Type="' . self::NOTES_TYPE . '" Target="./../notesSlides/./notesSlide1.xml"/></Relationships>',
            'ppt/notesSlides/notesSlide1.xml'  => '<notes/>',
            'ppt/slides/_rels/slide4.xml.rels' => '<Relationships ' . self::RELATIONSHIPS . '><Relationship Id="a" Type="x/slideLayout" Target="../slideLayouts/a.xml"/></Relationships>',
            'ppt/slides/_rels/slide5.xml.rels' => '<Relationships ' . self::RELATIONSHIPS . '><Relationship Id="a" Type="' . self::NOTES_TYPE . '" Target="../notesSlides/missing.xml"/></Relationships>',
            'ppt/slides/_rels/slide6.xml.rels' => '<Relationships ' . self::RELATIONSHIPS . '><Relationship Id="a" Type="' . self::NOTES_TYPE . '" Target="../notesSlides/notesSlide6.xml"/></Relationships>',
            'ppt/notesSlides/notesSlide6.xml'  => '<notes>',
        ]));

        self::assertSame([1], array_keys($notes));
    }

    /**
     * @param array<string, string> $files
     */
    private function archive(array $files): string
    {
        return $this->files[] = PptxBuilder::zip($files);
    }
}
