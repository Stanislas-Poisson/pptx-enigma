# Security Policy

## Reporting vulnerabilities

| Channel | Description | Contact / Link |
| :--- | :--- | :--- |
| **Private report** | Preferred channel for sensitive reports. | [Report a vulnerability][advisories] |
| **Issues** | Non-sensitive problems. | [Open an issue][issues] |
| **Email** | Alternative contact. | `security@the-white-rabbits.fr` |

Please **do not disclose a vulnerability publicly** until it has been reviewed and fixed.

---

## Supported versions

PPTX-Enigma is a proof of concept and has no release yet. Fixes are made on `main`.

---

## Scope

- PPTX-Enigma is a PHP class that reads a `.pptx` file. It has no authentication and no user account.
- It is a proof of concept: it unzips the file in a temporary directory, reads the XML with regular expressions, and does not escape the extracted text. Do not use it on files you do not trust until this is fixed ([#3](https://github.com/Stanislas-Poisson/pptx-enigma/issues/3)).
- A wrong extraction on a valid file is not a vulnerability: open an issue.

[advisories]: https://github.com/Stanislas-Poisson/pptx-enigma/security/advisories/new
[issues]: https://github.com/Stanislas-Poisson/pptx-enigma/issues
