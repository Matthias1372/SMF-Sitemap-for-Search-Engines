# SMF integration workflow

The `SMF 2.1.7 integration` workflow uses PHP 8.4 and installs SMF 2.1.7 through its web installer. It then builds this repository as an SMF package, installs it through SMF's package manager, and checks the package log, registered hooks, stored defaults, HTML sitemap, XML sitemap index, board sitemap, topic sitemap, XML parsing, and response headers.

PostgreSQL 16 is the required matrix entry. MySQL 8.4 is a non-blocking comparison entry (`continue-on-error`) so a MySQL-only problem remains visible without hiding a PostgreSQL failure.

## Limits

- This is an integration smoke test, not browser automation. It checks generated markup and XML but does not execute JavaScript or inspect visual layout.
- SMF is prepared from the official `v2.1.7` source tag. The tag keeps installer files in `other/`, so the workflow copies those files to the forum root to reproduce the layout of an SMF release archive.
- The test uses SMF's installer-created public board and welcome topic. It does not exercise a large forum, the 50,000-URL pagination boundary, custom themes, non-default permissions, cache backends, or package uninstall/upgrade paths.
- The MySQL comparison is deliberately non-blocking. Remove `experimental: true` (or the job's `continue-on-error`) if MySQL compatibility should become a release requirement.
- A successful local lint only proves that the PHP files parse. The integration result is authoritative only after the workflow has actually run on GitHub Actions.
