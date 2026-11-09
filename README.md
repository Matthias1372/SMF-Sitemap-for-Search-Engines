# Sitemap for Search Engines

A modern XML and HTML Sitemap modification for **Simple Machines Forum 2.1.x**.

> **Status:** Stable (v1.3.0)

---

## Features

- HTML sitemap
- XML Sitemap index for search engines
- Separate board Sitemap
- Automatic topic Sitemap pagination at 50,000 URLs per file
- Board overview
- Topic overview
- Automatic priorities
- XML cache
- Search engine friendly
- PHP 8.1 / 8.2 compatible
- Tested with PHP 8.4, PostgreSQL 16, and MySQL 8.4
- English, German, German informal, and Russian translations
- Native SMF 2.1 integration
- No output-buffer manipulation or automatic footer injection
- Modular extension system

---

## Requirements

- Simple Machines Forum **2.1.7** or newer
- PHP **8.1** or newer

---

## Installation

1. Download the latest release.
2. Upload the package using the SMF Package Manager.
3. Install the package.
4. Open:

```
index.php?action=sitemap
```

or

```
index.php?action=sitemap;sa=xml
```

This URL now returns a Sitemap index. It links automatically to the board Sitemap and to as many topic Sitemaps as required. Each topic Sitemap contains at most 50,000 URLs.

```text
index.php?action=sitemap;sa=xml;type=boards
index.php?action=sitemap;sa=xml;type=topics;page=1
```

---

## Planned Features

- [x] HTML Sitemap
- [x] XML Sitemap
- [x] Boards
- [x] Topics
- [x] Sitemap Index
- [ ] XML Compression (gzip)
- [ ] Members (optional)
- [ ] Calendar (optional)
- [ ] Extension API

---

## Project Structure

```
Sources/
    Sitemap.php
    Sitemap.subs.php

    Sitemap-Ext/
        Boards.php
        Topics.php

Themes/
    default/
        Sitemap.template.php

Languages/
```

---

## Development Status

| Version | Status |
|----------|--------|
| 1.3.0 | Added Russian translation and PostgreSQL-compatible pagination |
| 1.2.0 | Removed automatic footer link and output-buffer hook |
| 1.1.0 | Added automatic Sitemap index and topic pagination |
| 1.0.0 | First stable release |

---

## License

MIT License

---

## Author

**Matthias Feik**

---

## Contributing

Contributions, bug reports and feature requests are welcome.
