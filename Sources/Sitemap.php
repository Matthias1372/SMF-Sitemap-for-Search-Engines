<?php

/**
 * Sitemap for Search Engines
 *
 * Main controller.
 *
 * @package Sitemap for Search Engines
 * @version 1.0.0
 * @author Matthias Feik
 * @copyright Matthias Feik
 * @license MIT
 */

if (!defined('SMF'))
	die('No direct access...');

require_once(__DIR__ . '/Sitemap.subs.php');

/**
 * Registers the Sitemap action.
 *
 * @param array $actions
 *
 * @return void
 */
function SitemapActions(&$actions)
{
	$actions['sitemap'] = array(
		'Sitemap.php',
		'ShowSiteMap',
	);
}

/**
 * Adds the Sitemap settings to the Administration Center.
 *
 * @param array $config_vars
 *
 * @return void
 */
function SitemapGeneralModSettings(&$config_vars)
{
	$priorities = SitemapPriorities();
}

/**
 * Displays the HTML Sitemap.
 *
 * @return void
 */
function ShowSiteMap()
{
	global $context, $scripturl, $txt, $sourcedir;

	loadLanguage('Sitemap');

	$context['page_title'] = $txt['sitemap'];

	$context['linktree'][] = array(
		'url' => SitemapCreateUrl('index'),
		'name' => $txt['sitemap'],
	);

	$subAction = isset($_REQUEST['sa']) ? ucfirst(strtolower($_REQUEST['sa'])) : 'Boards';

	$extension = SitemapGetExtension($subAction);

	if ($extension === null || empty($extension['display']))
		fatal_lang_error('invalid_sitemap_subaction', false);

	require_once($sourcedir . '/Sitemap-Ext/' . $extension['file']);

	$displayFunction = SitemapDisplayFunction($subAction);

	if (!function_exists($displayFunction))
		fatal_lang_error('invalid_sitemap_subaction', false);

	$listOptions = $displayFunction();

	require_once($sourcedir . '/Subs-List.php');
	
	$listOptions['id'] = 'sitemap';
	$listOptions['base_href'] = SitemapCreateUrl(strtolower($subAction));
	
	createList($listOptions);
	
	loadTemplate('Sitemap');
	
	$context['sub_template'] = 'sitemap_main';
	$context['default_list'] = 'sitemap';
}
	
function SitemapBoardsXml()
{
	return array(
		array(
			'loc' => 'https://example.com/index.php?board=1.0',
			'lastmod' => '2026-07-22',
			'changefreq' => 'daily',
			'priority' => '0.8',
		),
	);
}

/**
 * Displays the XML Sitemap.
 *
 * @return void
 */
function ShowSiteMapXml()
{
	global $sourcedir;

	loadLanguage('Sitemap');

	$sections = SitemapXmlSections();

	header('Content-Type: application/xml; charset=UTF-8');

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="' . SitemapXmlNamespace() . '">' . "\n";

	foreach ($sections as $name => $extension)
{
	require_once($sourcedir . '/Sitemap-Ext/' . $extension['file']);

	$xmlFunction = 'Sitemap' . $name . 'Xml';

	if (!function_exists($xmlFunction))
		continue;

	$urls = $xmlFunction();

	foreach ($urls as $url)
	{
		echo "\t<url>\n";
		echo "\t\t<loc>" . SitemapEscapeXml($url['loc']) . "</loc>\n";

		if (!empty($url['lastmod']))
			echo "\t\t<lastmod>{$url['lastmod']}</lastmod>\n";

		if (!empty($url['changefreq']))
			echo "\t\t<changefreq>{$url['changefreq']}</changefreq>\n";

		if (!empty($url['priority']))
			echo "\t\t<priority>{$url['priority']}</priority>\n";

		echo "\t</url>\n";
	}
}

	echo "</urlset>\n";

	obExit(false);
}

/**
 * Adds the Sitemap link to the page footer.
 *
 * @param string $buffer
 *
 * @return string
 */
function SitemapBuffer($buffer)
{
	$link = SitemapFooterLink();
}
