# ![PPTX-Enigma](assets/logo.jpg)

[![CI][ci-badge]][ci]
[![Release][release-badge]][releases]
[![Packagist][packagist-badge]][packagist]
[![PHP][php-badge]][packagist]
[![License][license-badge]][license]
[![Docs][docs-badge]][docs]

PPTX-Enigma extracts the voice-over texts written in the speaker notes of a PowerPoint (`.pptx`) file. The texts are grouped by speaker and by reference. The voice-overs of a speaker can be written as a script to send to a voice actor, as text, HTML or PDF. The formatting of the notes (bold, italic, underline, strikethrough, superscript, subscript, line breaks, links, lists) is converted to HTML or to plain text.

> **Status: stable, `1.0.0`.** The extractor was rewritten, tested and given a configuration and a command line ([#3](https://github.com/Stanislas-Poisson/pptx-enigma/issues/3), [#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4), [#5](https://github.com/Stanislas-Poisson/pptx-enigma/issues/5)). It is published as a Composer package. It has been tested on generated presentations only, not on real PowerPoint exports. See [Known limits](#known-limits).

## Documentation

The [documentation site][docs] has this guide and the reference of every class, read from the source, for each released version (selector at the top right, `next` is `main`). Build it with `cd docs && npm ci && npm run dev`.

## Requirements

PHP 8.3 or higher, with the `dom` and `zip` extensions. There is no other dependency: the PDF of a script needs the optional package dompdf. The file is read in memory: nothing is extracted on the disk.

## The format of the notes

The extractor reads the notes of the slides, in the order of the presentation. It expects:

1. A **sign**, a short marker that delimits the voice-overs: `¤` by default, and it can be changed. It opens a voice-over and, unless you set a closing sign, it closes it too. With `sign: null` (`--sign-in-notes`), the sign is the text of the notes of the first slide that has notes, which are then only used to find the sign: they are not searched for voice-overs.
2. A **marker line** that starts with the sign, then the speaker between parentheses, then the reference: `¤ VOICE OVER (Narrator) w01_intro`. The words before the parentheses are free text. The speaker and the reference are trimmed.
3. The **paragraphs of the text**, which can be styled and can be lists.
4. A **closing line** that starts with the sign: `¤ VOICE OVER END`. Any other line that starts with the sign also closes the voice-over, so a marker line can open the next one.

The closing sign can be different from the opening one, with the option `endSign`: with `sign: '<<'` and `endSign: '>>'`, `<< VOICE OVER (Narrator) w01_intro` opens a voice-over and a line that starts with `>>` closes it. A line that starts with the opening sign still closes the voice-over that is open, and opens the next one.

A speaker can have several voice-overs in the same notes. By default a reference can be used only once per speaker (see the option `duplicates`).

## Installation

Install it with Composer:

```bash
composer require stanislas-poisson/pptx-enigma
```

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
| `sign` | `'¤'` | The sign that opens a voice-over. With `null`, the sign is read in the first notes, which are then not searched for voice-overs. Otherwise, all the notes are searched. |
| `endSign` | `null` | The sign that closes a voice-over. Without it, the sign that opens a voice-over closes it too. |
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
| `--format=json\|html\|text\|pdf` | How to write the voice-overs. The default is `json`, or `text` for a script. `pdf` needs `--split`. |
| `--sign=SIGN` | The sign that opens a voice-over. The default is `¤`. |
| `--sign-in-notes` | Read the sign in the first notes instead. |
| `--end-sign=SIGN` | The sign that closes a voice-over. |
| `--duplicates=error\|first\|last` | What to do when a speaker uses a reference twice. |
| `--speaker=NAME` | Write the script of one speaker, as `text` or `html`, on the standard output. |
| `--split=DIR` | Write the script of each speaker in a file of the directory, as `text` (`.txt`), `html` or `pdf`. |
| `--layout=above\|below\|inline\|none` | Where the reference is in a script. The default is `above`. |
| `--emphasis=marks\|upper\|none` | How the styles are written in a text script. The default is `marks`. |
| `--no-slides`, `--no-counts` | Leave out the slide of each voice-over, or the number of voice-overs and words. |
| `-h`, `--help` | Show the help. |

The exit code is `0` on success, `1` when the extraction fails or a file cannot be written (the message is written on the error output), and `2` when the command is not valid.

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

## Scripts for the voice actors

A script holds the voice-overs of **one speaker**, with the reference and the slide of each one, so that it can be sent to a voice actor as it is. `examples/bakery.pptx` is a fictional presentation with three speakers, styles, lists, a line break, a link and accents:

```php
$voiceOvers = (new Extracts('examples/bakery.pptx'))->extract();

$voiceOvers->speakers();                 // ['Baker', 'Customer', 'Narrator']
echo $voiceOvers->script('Baker')->toText();
```

```text
Baker
=====
2 voice-overs, 66 words

[w02_dough] (slide 3)
First, the dough. You need four things:
1. 500 g of flour
2. 350 ml of water
3. 10 g of salt
4. a pinch of yeast
Mix them, then *__wait__*. The dough rises on its own.

[w04_oven] (slide 4)
The oven must be very hot. Remember:
- 240 degrees
- a tray of water at the bottom
  - never open the door in the first ten minutes
Bake for *thirty* minutes.
```

| Method | Description |
| :--- | :--- |
| `speakers()` | The speakers, in the order of the voice-overs. |
| `forSpeaker($speaker)` | A `VoiceOvers` with this speaker only. |
| `script($speaker, $options)` | The `Script` of a speaker. |
| `scripts($options)` | The `Script` of each speaker, by speaker. |
| `Script::toText()` | The script as a plain text. |
| `Script::toHtml()` | The script as a standalone HTML document: the bold, italic, underline, lists and links are kept. |
| `Script::toPdf()` | The same document as a PDF (A4), which needs [dompdf](https://github.com/dompdf/dompdf): `composer require dompdf/dompdf`. Without it, a `MissingDependencyException` says what to install. |
| `Script::count()` and `wordCount()` | The number of voice-overs and of words. |

An unknown speaker throws an `InvalidArgumentException` that lists the speakers.

### Layout and styles

The settings are given with an immutable `ScriptOptions` object:

```php
use PPTXenigma\Emphasis;
use PPTXenigma\Layout;
use PPTXenigma\ScriptOptions;

$options = new ScriptOptions(layout: Layout::ReferenceInline, emphasis: Emphasis::Upper, showSlides: false);

echo $voiceOvers->script('Customer', $options)->toText();
```

| Option | Default | Description |
| :--- | :--- | :--- |
| `layout` | `Layout::ReferenceAbove` | Where the reference is: `ReferenceAbove` (a line above the text), `ReferenceBelow` (a line below it), `ReferenceInline` (`[w05_order] Bonjour…`, at the start of the text) or `TextOnly` (no reference). |
| `emphasis` | `Emphasis::Marks` | How the styles are written in the **text**: `Marks` (`*bold*`, `_italic_`, `__underline__`), `Upper` (the bold text in uppercase) or `None`. The HTML and the PDF always keep the real styles. |
| `showSlides` | `true` | Write the slide that each voice-over comes from. |
| `showCounts` | `true` | Write the number of voice-overs and of words under the title. |

### Files

With the command line, `--split` writes one file per speaker, named after the speaker (`baker.txt`, `customer.pdf`, and `speaker` for a name without a letter or a digit):

```sh
vendor/bin/pptx-enigma examples/bakery.pptx --split=scripts --format=pdf
vendor/bin/pptx-enigma examples/bakery.pptx --speaker=Baker --layout=below --no-slides
```

## Known limits

- **It has not been checked on real exports** of PowerPoint or Google Slides. The tests and the examples were built with PHP and python-pptx (`examples/build_bakery.py` builds the bakery one), and the bakery example, opened and saved again by LibreOffice Impress, is read the same way.
- **The markers are the ones above**: a sign followed by the speaker in parentheses. Only the opening sign and the closing sign can be changed.
- **The size of the archive is not limited**: do not use it on files you do not trust.
- The other formatting of PowerPoint (colours, sizes, fonts) is dropped.

## Development

```sh
make install
make hooks     # the Git hooks
make quality   # Pint, PHPStan, Rector, PHP Insights and PHPUnit
```

`make help` lists every command. See [CONTRIBUTING.md](CONTRIBUTING.md) for the details.

## Roadmap

1. Fix the known bugs and clean the application ([#3](https://github.com/Stanislas-Poisson/pptx-enigma/issues/3)): done.
2. Add the missing features ([#4](https://github.com/Stanislas-Poisson/pptx-enigma/issues/4)): done.
3. Turn PPTX-Enigma into a Composer package with a configuration ([#5](https://github.com/Stanislas-Poisson/pptx-enigma/issues/5)): done, released as `1.0.0`.

## License

[MIT](LICENSE). Copyright (c) 2017 Stanislas Poisson.

[ci-badge]: https://img.shields.io/github/actions/workflow/status/Stanislas-Poisson/pptx-enigma/ci.yml?branch=main&label=CI
[ci]: https://github.com/Stanislas-Poisson/pptx-enigma/actions/workflows/ci.yml
[release-badge]: https://img.shields.io/github/v/release/Stanislas-Poisson/pptx-enigma
[releases]: https://github.com/Stanislas-Poisson/pptx-enigma/releases
[packagist-badge]: https://img.shields.io/packagist/v/stanislas-poisson/pptx-enigma
[packagist]: https://packagist.org/packages/stanislas-poisson/pptx-enigma
[php-badge]: https://img.shields.io/packagist/dependency-v/stanislas-poisson/pptx-enigma/php
[license-badge]: https://img.shields.io/github/license/Stanislas-Poisson/pptx-enigma
[license]: LICENSE
[docs-badge]: https://img.shields.io/badge/docs-online-blue
[docs]: https://stanislas-poisson.github.io/pptx-enigma/
