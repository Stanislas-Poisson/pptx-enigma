# ![PPTX-Enigma](assets/logo.jpg)

PPTX-Enigma extracts the voice-over texts written in the speaker notes of a PowerPoint (`.pptx`) file. The texts are grouped by speaker and by reference, and the formatting of the notes (bold, italic, underline, strikethrough, superscript, subscript, line breaks, links, lists) is converted to HTML or to plain text.

> **Status: proof of concept.** The extractor was rewritten, tested and given a configuration and a command line ([#3](https://github.com/Stanislas-Poisson/pptx-enigma/issues/3), [#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4), [#5](https://github.com/Stanislas-Poisson/pptx-enigma/issues/5)). It is not published as a package yet, and it has not been checked on real PowerPoint exports. See [Known limits](#known-limits).

## Requirements

PHP 8.3 or higher, with the `dom` and `zip` extensions. There is no other dependency. The file is read in memory: nothing is extracted on the disk.

## The format of the notes

The extractor reads the notes of the slides, in the order of the presentation. It expects:

1. A **sign**, a short marker that delimits the voice-overs, for example `¤`. Unless you set it, the sign is the text of the notes of the first slide that has notes. These first notes are only used to find the sign: they are not searched for voice-overs.
2. A **marker line** that starts with the sign, then the speaker between parentheses, then the reference: `¤ VOICE OVER (Narrator) w01_intro`. The words before the parentheses are free text. The speaker and the reference are trimmed.
3. The **paragraphs of the text**, which can be styled and can be lists.
4. A **closing line** that starts with the sign: `¤ VOICE OVER END`. Any other line that starts with the sign also closes the voice-over, so a marker line can open the next one.

A speaker can have several voice-overs in the same notes. By default a reference can be used only once per speaker (see the option `duplicates`).

## Installation

PPTX-Enigma is not published on Packagist yet: there is no release. When it is, it will be installed with `composer require stanislas-poisson/pptx-enigma`. Until then, add the repository to your `composer.json`.

## Usage

`examples/sample.pptx` is a small fictional presentation that follows the format above:

```php
<?php

use PPTXenigma\Extracts;

require 'vendor/autoload.php';

$voiceOvers = (new Extracts('examples/sample.pptx'))->extract();

print_r($voiceOvers->toArray());
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

`extract()` returns a `VoiceOvers` object. The voice-overs are sorted by speaker, then they keep the order of the slides.

| Method | Description |
| :--- | :--- |
| `count()` and `foreach` | The number of voice-overs, and each `VoiceOver` (`speaker`, `reference`, `slide`, `html`, `text`). |
| `sign` | The sign that delimited the voice-overs. |
| `toArray(Format $format = Format::Html)` | The content of each voice-over, by speaker then by reference. `Format::Text` gives the plain text. |
| `toJson(Format $format = Format::Html)` | The same array, as JSON. |
| `toHtml()` | An HTML fragment: a heading for each speaker and each reference, then the voice-over. |
| `toText()` | A plain text document. |

A file that is missing or is not a PowerPoint 2007+ presentation throws an `InvalidArgumentException`. A missing sign, a reference used twice by the same speaker, or a voice-over that is not closed, throws a `PPTXenigma\ExtractionException` that names the speaker, the reference and the number of the slide.

### Options

The settings are given with an immutable `Options` object. Use the named arguments to change one:

```php
use PPTXenigma\Duplicates;
use PPTXenigma\Extracts;
use PPTXenigma\Options;

$options = new Options(
    sign: '§',
    duplicates: Duplicates::KeepLast,
    htmlTags: ['bold' => 'strong', 'italic' => 'em'],
);

$voiceOvers = (new Extracts('presentation.pptx', $options))->extract();
```

| Option | Default | Description |
| :--- | :--- | :--- |
| `sign` | `null` | The sign of the voice-overs. Without it, the sign is read in the first notes, which are then not searched for voice-overs. With it, all the notes are searched. |
| `duplicates` | `Duplicates::Error` | What to do when a speaker uses a reference twice: `Error`, `KeepFirst` or `KeepLast`. |
| `htmlTags` | `b`, `i`, `u`, `s`, `sup`, `sub` | The HTML tag of a style, by style: `bold`, `italic`, `underline`, `strike`, `superscript` and `subscript`. |
| `linkSchemes` | `http`, `https`, `mailto`, `tel` | The schemes of the links that are kept. Any other link is written as plain text. |

An invalid setting throws an `InvalidArgumentException`.

### Command line

```sh
vendor/bin/pptx-enigma examples/sample.pptx --format=text
```

| Option | Description |
| :--- | :--- |
| `--format=json\|html\|text` | How to write the voice-overs. The default is `json`. |
| `--sign=SIGN` | The sign of the voice-overs. |
| `--duplicates=error\|first\|last` | What to do when a speaker uses a reference twice. |
| `-h`, `--help` | Show the help. |

The exit code is `0` on success, `1` when the extraction fails (the message is written on the error output), and `2` when the command is not valid.

### Output

| In the notes | HTML | Plain text |
| :--- | :--- | :--- |
| A paragraph | `<p>…</p>` | one line |
| Bold, italic, underline, strikethrough | `<b>`, `<i>`, `<u>`, `<s>` | dropped |
| Superscript, subscript | `<sup>`, `<sub>` | dropped |
| A line break inside a paragraph | `<br>` | a line break |
| A hyperlink | `<a href="…">`, for the allowed schemes | `text (URL)` |
| A bulleted or a numbered paragraph | `<ul>` or `<ol>` with `<li>`, nested by level | `- item` or `1. item`, indented by level |
| An empty paragraph | nothing, it only ends a list | nothing, it only ends a list |

The text is escaped in the HTML. A paragraph is a list item only when it has a bullet (`buChar`) or a number (`buAutoNum`): `buNone` is a paragraph.

## Known limits

- **It has not been checked on real exports** of PowerPoint or Google Slides. The tests and the example were built with PHP and python-pptx.
- **The markers are the ones above**: a sign followed by the speaker in parentheses. Only the sign can be changed.
- **The size of the archive is not limited**: do not use it on files you do not trust.
- The other formatting of PowerPoint (colours, sizes, fonts) is dropped.

## Development

```sh
composer install
composer check   # PHPStan at the maximum level, then PHPUnit
```

## Roadmap

1. Fix the known bugs and clean the application ([#3](https://github.com/Stanislas-Poisson/pptx-enigma/issues/3)): done.
2. Add the missing features ([#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4)): done.
3. Turn PPTX-Enigma into a Composer package with a configuration ([#5](https://github.com/Stanislas-Poisson/pptx-enigma/issues/5)): the configuration is done, the release and the publication on Packagist are not.

## License

[MIT](LICENSE). Copyright (c) 2017 Stanislas Poisson.
