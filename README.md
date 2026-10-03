# ![PPTX-Enigma](assets/logo.jpg)

PPTX-Enigma extracts the voice-over texts written in the speaker notes of a PowerPoint (`.pptx`) file. The texts are grouped by speaker and by reference, and the formatting of the notes (bold, italic, underline, strikethrough, superscript, subscript, lists) is converted to HTML.

> **Status: proof of concept.** The extractor was rewritten and tested ([#3](https://github.com/Stanislas-Poisson/pptx-enigma/issues/3)). It still has a few limits, and it is not a published package yet: no configuration, one output format. See [Known limits](#known-limits) and [Roadmap](#roadmap).

## Requirements

PHP 8.3 or higher, with the `dom` and `zip` extensions. There is no other dependency. The file is read in memory: nothing is extracted on the disk.

## The format of the notes

The extractor reads the notes of the slides, in the order of the presentation. It expects:

1. A **sign**, a short marker that delimits the voice-overs, for example `¤`. Unless you set it, the sign is the text of the notes of the first slide that has notes. These first notes are only used to find the sign: they are not searched for voice-overs.
2. A **marker line** that starts with the sign, then the speaker between parentheses, then the reference: `¤ VOICE OVER (Narrator) w01_intro`. The words before the parentheses are free text. The speaker and the reference are trimmed.
3. The **paragraphs of the text**, which can be styled and can be lists.
4. A **closing line** that starts with the sign: `¤ VOICE OVER END`. Any other line that starts with the sign also closes the voice-over, so a marker line can open the next one.

A speaker can have several voice-overs in the same notes, but a reference can be used only once per speaker.

## Usage

There is no published Composer package yet. `examples/sample.pptx` is a small fictional presentation that follows the format above:

```php
<?php

require 'vendor/autoload.php';

$extractor = new PPTXenigma\Extracts('examples/sample.pptx');

print_r($extractor->extract()->getVoiceOver());
```

Output:

```text
Array
(
    [Guide] => Array
        (
            [w02_guide] => <p>Hello, I am the guide.</p>
            [w03_goodbye] => <p>That is all, thank you.</p>
        )

    [Narrator] => Array
        (
            [w01_intro] => <p>Welcome to this </p><p><b>fictional</b> presentation, written only to test the extractor.</p><p>It has <i>two</i> voices.</p>
            [w02_list] => <p>Three things to remember:</p><ul><li>Notes are read slide by slide.</li><li>Voices are grouped by speaker.</li><li>References identify each voice-over.</li></ul>
        )

)
```

The speakers are sorted by name, and the references keep the order of the slides. To use another sign, call `setSign()` before `extract()`: the first notes are then searched too.

| Method | Description |
| :--- | :--- |
| `new Extracts(string $path)` | The presentation to read. |
| `setSign(string $sign)` / `getSign()` | Set the sign, or read the one that was found. |
| `extract()` | Read the notes. Returns the extractor. |
| `getVoiceOver()` | The HTML of each voice-over, by speaker then by reference. |

A file that is missing or is not a PowerPoint 2007+ presentation throws an `InvalidArgumentException`. A missing sign, or a reference used twice by the same speaker, throws a `PPTXenigma\ExtractionException` with the number of the slide.

### HTML

| In the notes | Output |
| :--- | :--- |
| A paragraph | `<p>…</p>` |
| Bold, italic, underline, strikethrough | `<b>`, `<i>`, `<u>`, `<s>` |
| Superscript, subscript | `<sup>`, `<sub>` |
| A bulleted or a numbered paragraph | `<ul>` or `<ol>` with `<li>`, nested by level |
| An empty paragraph | nothing, it only ends a list |

The text is escaped. A paragraph is a list item only when it has a bullet (`buChar`) or a number (`buAutoNum`): `buNone` is a paragraph.

## Known limits

- **A voice-over that is not closed** (no line with the sign after it, and no next voice-over) is ignored without an error ([#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4)).
- **Line breaks inside a paragraph, hyperlinks and the other styles** of PowerPoint are not converted ([#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4)).
- **There is only one output format** (a PHP array of HTML), and nothing can be configured except the sign ([#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4), [#5](https://github.com/Stanislas-Poisson/pptx-enigma/issues/5)).
- **The size of the archive is not limited**: do not use it on files you do not trust.

## Development

```sh
composer install
composer check   # PHPStan at the maximum level, then PHPUnit
```

## Roadmap

1. Fix the known bugs and clean the application ([#3](https://github.com/Stanislas-Poisson/pptx-enigma/issues/3)): done.
2. Add the missing features ([#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4)).
3. Turn PPTX-Enigma into a Composer package with a configuration ([#5](https://github.com/Stanislas-Poisson/pptx-enigma/issues/5)).

## License

[MIT](LICENSE). Copyright (c) 2017 Stanislas Poisson.
