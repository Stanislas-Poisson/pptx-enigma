<?php

declare(strict_types=1);

namespace PPTXenigma\Tests;

use ZipArchive;

/**
 * Builds small presentations for the tests: only the parts that the extractor reads.
 *
 * A paragraph is a text, or an array with the keys "runs" (a list of texts, of
 * arrays with the keys "t", "b", "i", "u", "strike", "baseline" and "link" (an id of
 * relationship), or of ["br" => true] for a line break), "bullet" ("ul", "ol" or
 * "none") and "lvl".
 *
 * A slide can also have the key "links", the URL of each link of its notes by id of relationship.
 */
final class PptxBuilder
{
    private const string NS = 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"';

    private const string REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * @param list<array{file: int, notes: list<mixed>|null, links?: array<string, string>}> $slides the slides in the order of the presentation
     */
    public static function build(array $slides): string
    {
        $files = [
            '[Content_Types].xml'             => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            'ppt/presentation.xml'            => self::presentation($slides),
            'ppt/_rels/presentation.xml.rels' => self::presentationRelationships($slides),
        ];

        foreach ($slides as $slide) {
            $file                                       = $slide['file'];
            $files['ppt/slides/slide' . $file . '.xml'] = '<p:sld ' . self::NS . '/>';

            if (null !== $slide['notes']) {
                $files['ppt/slides/_rels/slide' . $file . '.xml.rels'] = self::slideRelationships($file);
                $files['ppt/notesSlides/notesSlide' . $file . '.xml']  = self::notes($slide['notes']);

                if (isset($slide['links'])) {
                    $files['ppt/notesSlides/_rels/notesSlide' . $file . '.xml.rels'] = self::notesRelationships($slide['links']);
                }
            }
        }

        return self::zip($files);
    }

    /**
     * @param list<mixed> $paragraphs
     */
    public static function notes(array $paragraphs): string
    {
        $body = '';

        foreach ($paragraphs as $paragraph) {
            $body .= self::paragraph($paragraph);
        }

        return '<p:notes ' . self::NS . '><p:cSld><p:spTree>'
            . '<p:sp><p:nvSpPr><p:cNvPr id="2" name="Slide image"/><p:cNvSpPr/><p:nvPr><p:ph type="sldImg"/></p:nvPr></p:nvSpPr></p:sp>'
            . '<p:sp><p:nvSpPr><p:cNvPr id="3" name="Notes"/><p:cNvSpPr/><p:nvPr><p:ph type="body" idx="1"/></p:nvPr></p:nvSpPr><p:txBody><a:bodyPr/>' . $body . '</p:txBody></p:sp>'
            . '<p:sp><p:nvSpPr><p:cNvPr id="4" name="Number"/><p:cNvSpPr/><p:nvPr><p:ph type="sldNum" idx="12"/></p:nvPr></p:nvSpPr><p:txBody><a:bodyPr/><a:p><a:r><a:t>‹#›</a:t></a:r></a:p></p:txBody></p:sp>'
            . '</p:spTree></p:cSld></p:notes>';
    }

    /**
     * @param array<string, string> $files the content of each file of the archive, by path
     */
    public static function zip(array $files): string
    {
        $path        = (string) tempnam(sys_get_temp_dir(), 'pptx');
        $zipArchive  = new ZipArchive();
        $zipArchive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $content) {
            $zipArchive->addFromString($name, $content);
        }

        $zipArchive->close();

        return $path;
    }

    /**
     * @param array<string, string> $links
     */
    private static function notesRelationships(array $links): string
    {
        $relationships = '';

        foreach ($links as $id => $url) {
            $relationships .= '<Relationship Id="' . $id . '" Type="' . self::REL . '/hyperlink" Target="' . htmlspecialchars($url, ENT_QUOTES) . '" TargetMode="External"/>';
        }

        return '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . self::REL . '/notesMaster" Target="../notesMasters/notesMaster1.xml"/>'
            . $relationships . '</Relationships>';
    }

    private static function paragraph(mixed $paragraph): string
    {
        if (is_string($paragraph)) {
            $paragraph = ['runs' => [$paragraph]];
        }

        /** @var array{runs?: list<mixed>, bullet?: string, lvl?: int} $paragraph */
        $properties = '';

        if (isset($paragraph['bullet'])) {
            $bullet = match ($paragraph['bullet']) {
                'ul'    => '<a:buFont typeface="Arial"/><a:buChar char="•"/>',
                'ol'    => '<a:buFont typeface="Arial"/><a:buAutoNum type="arabicPeriod"/>',
                default => '<a:buFont typeface="Arial"/><a:buNone/>',
            };
            $properties = '<a:pPr lvl="' . ($paragraph['lvl'] ?? 0) . '">' . $bullet . '</a:pPr>';
        }

        $runs = '';

        foreach ($paragraph['runs'] ?? [] as $run) {
            $runs .= self::run($run);
        }

        return '<a:p>' . $properties . $runs . '</a:p>';
    }

    /**
     * @param list<array{file: int, notes: list<mixed>|null, links?: array<string, string>}> $slides
     */
    private static function presentation(array $slides): string
    {
        $ids = '';

        foreach ($slides as $index => $slide) {
            $ids .= '<p:sldId id="' . (256 + $index) . '" r:id="rId' . $slide['file'] . '"/>';
        }

        return '<p:presentation ' . self::NS . '><p:sldIdLst>' . $ids . '</p:sldIdLst></p:presentation>';
    }

    /**
     * @param list<array{file: int, notes: list<mixed>|null, links?: array<string, string>}> $slides
     */
    private static function presentationRelationships(array $slides): string
    {
        $relationships = '';

        foreach ($slides as $slide) {
            $relationships .= '<Relationship Id="rId' . $slide['file'] . '" Type="' . self::REL . '/slide" Target="slides/slide' . $slide['file'] . '.xml"/>';
        }

        return '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $relationships . '</Relationships>';
    }

    private static function run(mixed $run): string
    {
        if (is_string($run)) {
            $run = ['t' => $run];
        }

        if (is_array($run) && array_key_exists('br', $run)) {
            return '<a:br/>';
        }

        /** @var array{t: string, b?: string, i?: string, u?: string, strike?: string, baseline?: int, link?: string} $run */
        $attributes = '';
        $link       = isset($run['link']) ? '<a:hlinkClick r:id="' . $run['link'] . '"/>' : '';

        foreach (['b', 'i', 'u', 'strike'] as $style) {
            if (isset($run[$style])) {
                $attributes .= ' ' . $style . '="' . $run[$style] . '"';
            }
        }

        if (isset($run['baseline'])) {
            $attributes .= ' baseline="' . $run['baseline'] . '"';
        }

        return '<a:r><a:rPr' . $attributes . '>' . $link . '</a:rPr><a:t>' . htmlspecialchars($run['t'], ENT_NOQUOTES) . '</a:t></a:r>';
    }

    private static function slideRelationships(int $file): string
    {
        return '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="' . self::REL . '/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>'
            . '<Relationship Id="rId2" Type="' . self::REL . '/notesSlide" Target="../notesSlides/notesSlide' . $file . '.xml"/>'
            . '</Relationships>';
    }
}
