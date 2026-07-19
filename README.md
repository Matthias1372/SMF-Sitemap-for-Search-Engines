# Sitemap for Search Engines

A modern XML and HTML Sitemap modification for **Simple Machines Forum 2.1.x**.

> **Status:** Development (v0.1.0)

---

## Features

- HTML sitemap
- XML sitemap for search engines
- Board overview
- Topic overview
- Automatic priorities
- XML cache
- Search engine friendly
- PHP 8.1 / 8.2 compatible
- Native SMF 2.1 integration
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
index.php?action=sitemap;xml
```

---

## Planned Features

- [x] HTML Sitemap
- [x] XML Sitemap
- [x] Boards
- [x] Topics
- [ ] Sitemap Index
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
| 0.1.0 | Project setup |
| 0.2.0 | HTML Sitemap |
| 0.3.0 | Topics |
| 0.4.0 | XML Sitemap |
| 0.5.0 | Cache |
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
