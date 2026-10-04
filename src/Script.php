<?php

declare(strict_types=1);

namespace PPTXenigma;

use Countable;
use Dompdf\Dompdf;

/**
 * The voice-overs of one speaker, written as a document that can be sent to a voice actor: as text, as HTML or
 * as a PDF. The HTML and the PDF keep the formatting of the notes.
 */
final readonly class Script implements Countable
{
    private const string STYLE = <<<'CSS'
        @page { margin: 2cm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11pt; line-height: 1.5; color: #111; }
        h1 { font-size: 18pt; margin: 0 0 4px; }
        .counts { color: #555; margin: 0 0 24px; }
        .voice-over { margin: 0 0 18px; page-break-inside: avoid; }
        .reference { color: #555; font-size: 9pt; font-weight: bold; margin: 0 0 3px; }
        .voice-over p, .voice-over ul, .voice-over ol { margin: 0 0 6px; }
        CSS;

    /**
     * @param list<VoiceOver> $voiceOvers the voice-overs of the speaker, in the order of the slides
     */
    public function __construct(
        public string $speaker,
        private array $voiceOvers,
        private ScriptOptions $scriptOptions = new ScriptOptions(),
    ) {}

    public function count(): int
    {
        return count($this->voiceOvers);
    }

    public function toHtml(): string
    {
        $body = '';

        foreach ($this->voiceOvers as $voiceOver) {
            $body .= '<div class="voice-over">' . $this->htmlOf($voiceOver) . "</div>\n";
        }

        $title = htmlspecialchars($this->speaker, ENT_QUOTES | ENT_SUBSTITUTE);
        $head  = '<h1>' . $title . "</h1>\n"
            . ($this->scriptOptions->showCounts ? '<p class="counts">' . $this->counts() . "</p>\n" : '');

        return "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"utf-8\">\n<title>" . $title . "</title>\n<style>\n"
            . self::STYLE . "\n</style>\n</head>\n<body>\n" . $head . $body . "</body>\n</html>\n";
    }

    /**
     * @throws MissingDependencyException when the package dompdf is not installed
     */
    public function toPdf(): string
    {
        // @codeCoverageIgnoreStart
        // dompdf is a development dependency: it is always installed in the tests, so the case where it is
        // missing cannot be reached there. The message itself is tested.
        if (! class_exists(Dompdf::class)) {
            throw MissingDependencyException::pdf();
        }

        // @codeCoverageIgnoreEnd

        $dompdf = new Dompdf();
        $dompdf->loadHtml($this->toHtml(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }

    public function toText(): string
    {
        $text = $this->speaker . "\n" . str_repeat('=', max(1, mb_strlen($this->speaker))) . "\n";

        if ($this->scriptOptions->showCounts) {
            $text .= $this->counts() . "\n";
        }

        foreach ($this->voiceOvers as $voiceOver) {
            $text .= "\n" . $this->textOf($voiceOver) . "\n";
        }

        return $text;
    }

    /**
     * The number of words of the voice-overs, without the marks of the styles.
     */
    public function wordCount(): int
    {
        $words = 0;

        foreach ($this->voiceOvers as $voiceOver) {
            $words += (int) preg_match_all('/[\p{L}\p{N}]+(?:[\'’-][\p{L}\p{N}]+)*/u', $voiceOver->text);
        }

        return $words;
    }

    private function counts(): string
    {
        $voiceOvers = count($this->voiceOvers);
        $words      = $this->wordCount();

        $voiceLabel = 1 === $voiceOvers ? 'voice-over' : 'voice-overs';
        $wordLabel  = 1 === $words ? 'word' : 'words';

        return $voiceOvers . ' ' . $voiceLabel . ', ' . $words . ' ' . $wordLabel;
    }

    private function escapedLabel(VoiceOver $voiceOver): string
    {
        return htmlspecialchars($this->label($voiceOver), ENT_QUOTES | ENT_SUBSTITUTE);
    }

    private function htmlOf(VoiceOver $voiceOver): string
    {
        $label     = $this->escapedLabel($voiceOver);
        $paragraph = '<p class="reference">' . $label . '</p>';

        return match ($this->scriptOptions->layout) {
            Layout::ReferenceAbove  => $paragraph . "\n" . $voiceOver->html,
            Layout::ReferenceBelow  => $voiceOver->html . "\n" . $paragraph,
            Layout::ReferenceInline => $this->inline('<span class="reference">' . $label . '</span>', $voiceOver->html),
            Layout::TextOnly        => $voiceOver->html,
        };
    }

    /**
     * The reference goes at the start of the first paragraph, or on its own line when the text starts with a list.
     */
    private function inline(string $reference, string $html): string
    {
        if (1 === preg_match('/^<p(?:\s[^>]*)?>/', $html, $tag)) {
            return $tag[0] . $reference . ' ' . substr($html, strlen($tag[0]));
        }

        return '<p>' . $reference . "</p>\n" . $html;
    }

    private function label(VoiceOver $voiceOver): string
    {
        $label = '[' . $voiceOver->reference . ']';

        return $this->scriptOptions->showSlides ? $label . ' (slide ' . $voiceOver->slide . ')' : $label;
    }

    private function textOf(VoiceOver $voiceOver): string
    {
        $text  = $voiceOver->text($this->scriptOptions->emphasis);
        $label = $this->label($voiceOver);

        return match ($this->scriptOptions->layout) {
            Layout::ReferenceAbove  => $label . "\n" . $text,
            Layout::ReferenceBelow  => $text . "\n" . $label,
            Layout::ReferenceInline => rtrim($label . ' ' . $text),
            Layout::TextOnly        => $text,
        };
    }
}
