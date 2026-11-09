[b]SMF Sitemap for Search Engines[/b]
Version 1.3.0

[hr]

[b]Introduction[/b]

SMF Sitemap for Search Engines automatically generates XML sitemaps for Simple Machines Forum 2.1.x.

The modification creates search-engine-friendly XML sitemaps for all publicly accessible boards and approved topics. It also provides an HTML sitemap for visitors.

For larger forums, the XML output is automatically organized as a sitemap index. Topic URLs are split into separate sitemap pages containing no more than 50,000 URLs each, in accordance with the Sitemap protocol.

[b]Features[/b]

[list]
[li]Automatic XML sitemap index[/li]
[li]Separate board sitemap[/li]
[li]Automatic topic sitemap splitting[/li]
[li]Maximum of 50,000 topic URLs per sitemap[/li]
[li]Accurate [tt]lastmod[/tt] timestamps[/li]
[li]Only guest-visible boards and approved topics are included[/li]
[li]HTML sitemap for forum visitors[/li]
[li]Compatible with Google Search Console[/li]
[li]Compatible with Bing Webmaster Tools[/li]
[li]Built-in caching[/li]
[li]No output-buffer manipulation or automatic footer-link injection[/li]
[/list]

[hr]

[b]Installation[/b]

[list]
[li]Open the SMF Administration Center.[/li]
[li]Go to [b]Main → Package Manager → Download Packages[/b].[/li]
[li]Upload the modification package.[/li]
[li]Click [b]Install Mod[/b] and complete the installation.[/li]
[li]Open [b]Configuration → Modification Settings → Sitemap[/b].[/li]
[li]Review and save the desired settings.[/li]
[/list]

The default XML sitemap URL is:

[code]
https://example.com/index.php?action=sitemap;sa=xml
[/code]

Replace [tt]https://example.com[/tt] with the URL of your forum. Submit this URL to Google Search Console or Bing Webmaster Tools. Search engines will discover the board and topic sitemaps through the generated sitemap index automatically.

The HTML sitemap is available at:

[code]
https://example.com/index.php?action=sitemap
[/code]

[hr]

[b]Configuration[/b]

The available options can be found in:

[code]
Administration Center → Configuration → Modification Settings → Sitemap
[/code]

Depending on the installed version, the settings include:

[list]
[li]Number of topics included per sitemap page[/li]
[li]Cache lifetime[/li]
[li]Priority values for recently updated topics[/li]
[li]Priority values for older topics[/li]
[/list]

The XML sitemap index is generated automatically and contains:

[list]
[li]One board sitemap[/li]
[li]One or more topic sitemaps[/li]
[/list]

Each topic sitemap contains no more than 50,000 URLs. Additional sitemap pages are created automatically when the forum exceeds this limit.

The [tt]lastmod[/tt] value of every sitemap entry is calculated from the actual modification time of the content contained in that sitemap.

Only content visible to guests is included. Boards that guests cannot access and topics that have not been approved are excluded automatically.

[hr]

[b]Changelog[/b]

[b]Version 1.3.0[/b]

[list]
[li]Added a Russian translation. Thanks to @Bugo[/li]
[li]Added PostgreSQL-compatible sitemap pagination while retaining MySQL compatibility. Thanks to @Bugo[/li]
[li]Added automated integration tests for SMF 2.1.7 with PostgreSQL 16 and MySQL 8.4 on PHP 8.4[/li]
[li]Added an in-place upgrade path from version 1.2.0[/li]
[/list]

[b]Version 1.2.0[/b]

[list]
[li]Added redirection to mod settings after installation[/li]
[li]Removed the automatic Sitemap link from the page footer[/li]
[li]Removed the [tt]integrate_buffer[/tt] hook[/li]
[li]Removed output-buffer manipulation from normal forum pages[/li]
[/list]

[b]Version 1.1.0[/b]

[list]
[li]Added an automatic XML sitemap index[/li]
[li]Added a separate board sitemap[/li]
[li]Added automatic topic sitemap splitting[/li]
[li]Limited each topic sitemap to a maximum of 50,000 URLs[/li]
[li]Added individual [tt]lastmod[/tt] timestamps for sitemap index entries[/li]
[li]Updated the documentation[/li]
[/list]

[b]Version 1.0.0[/b]

[list]
[li]Initial public release[/li]
[/list]

[hr]

[b]Support[/b]

Bug reports, feature requests, and contributions are welcome through the project repository:

[code]
https://github.com/Matthias1372/SMF-Sitemap-for-Search-Engines
[/code]

When reporting a problem, please include:

[list]
[li]Your SMF version[/li]
[li]Your PHP version[/li]
[li]The modification version[/li]
[li]Relevant entries from the SMF error log[/li]
[li]Clear steps to reproduce the problem[/li]
[/list]

This modification is released under the MIT License.

Thank you for using SMF Sitemap for Search Engines.
