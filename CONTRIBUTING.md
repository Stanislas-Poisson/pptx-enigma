# Contributing

Thank you for helping. PPTX-Enigma is a PHP extractor of the voice-over texts of a PowerPoint file. It is a proof of concept.

---

## Development workflow

| Step | Command / Action | Description |
| :--- | :--- | :--- |
| **1. Issue** | Open or pick an issue | One branch and one pull request per issue. |
| **2. Branch** | `git checkout -b feature/#TICKET-name develop` | Create a branch from `develop`. |
| **3. Code** | *(your IDE)* | Keep the change small, and write its test. |
| **4. Check** | `composer check` | Run PHPStan at the maximum level, then PHPUnit. |
| **5. Commit** | `git commit -m "type(scope): #TICKET subject"` | Use the [Conventional Commits][conventional-commits] format, in English, 72 characters at most. |
| **6. Push** | `git push origin feature/#TICKET-name` | Push and open a pull request to `develop`. |

A pull request needs a review and is merged with a merge commit.

---

## Checks

| Command | Tool | Description |
| :--- | :--- | :--- |
| `composer install` | Composer | Install the development tools. |
| `composer stan` | PHPStan | Static analysis at the maximum level, without a baseline. |
| `composer test` | PHPUnit | Run the tests. The coverage of `src/` must stay at 100 %. |
| `composer check` | Both | The two commands above. |

The tests build their presentations on the fly (`tests/PptxBuilder.php`) and use `examples/sample.pptx`, which is fictional. Never add a file with a private or confidential content.

The `ci` check runs the same commands on PHP 8.3 and 8.4, and must pass before a change reaches `develop` or `main`.

---

## Language

Code, comments, commits, issues and pull requests are in English.

[conventional-commits]: https://www.conventionalcommits.org/
