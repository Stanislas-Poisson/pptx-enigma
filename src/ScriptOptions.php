<?php

declare(strict_types=1);

namespace PPTXenigma;

/**
 * The settings of a script. The object is immutable: use the named arguments to change a setting.
 *
 *     new ScriptOptions(layout: Layout::ReferenceInline, showSlides: false);
 */
final readonly class ScriptOptions
{
    /**
     * @param Layout   $layout     where the reference of a voice-over is written
     * @param Emphasis $emphasis   how the styles are written in the text (the HTML and the PDF keep them)
     * @param bool     $showSlides write the slide that each voice-over comes from
     * @param bool     $showCounts write the number of voice-overs and of words under the title
     */
    public function __construct(
        public Layout $layout = Layout::ReferenceAbove,
        public Emphasis $emphasis = Emphasis::Marks,
        public bool $showSlides = true,
        public bool $showCounts = true,
    ) {}
}
