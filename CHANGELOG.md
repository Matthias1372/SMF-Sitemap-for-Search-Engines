# Changelog

## 1.1.0 - 2026-07-23

- Added an automatic XML Sitemap index.
- Added a separate board Sitemap.
- Split topic URLs automatically into child Sitemaps containing at most 50,000 URLs.
- Added accurate per-child-Sitemap `lastmod` values.
- Kept the existing HTML Sitemap unchanged.

## 1.1.0

- Fixed the installer overwriting SMF's global `$settings` theme configuration array.
- Prevented the misleading “default theme directory is wrong” error after package installation.

## 1.1.0

- Restored language files to `Themes/default/languages`.
- Corrected the SMF package destination variable to `$themes_dir/default/languages`.
- Replaced the custom language loader with SMF's `loadLanguage('Sitemap')`.

## 1.1.0 - 2026-07-23

- Earlier language-path experiments were superseded by the correct `$themes_dir/default/languages` destination in 1.1.0.
- Added the standard SMF package-info DOCTYPE and namespace alias.
- Normalized the supported version range to `2.1.0 - 2.1.99`.

# Changelog

## 1.1.0

- Removed the custom theme template dependency.
- HTML sitemap now uses SMF's built-in GenericList template.
- Eliminates package installation failures caused by default-theme path validation.


## 0.1.5 - 2026-07-23

- Fixed integration hooks not being stored permanently.
- Corrected the integrate_buffer callback to modify the output by reference.
- Added reinstall-safe hook registration.

## 0.1.3 - 2026-07-23

- Corrected the default theme template destination.
