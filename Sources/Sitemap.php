<?php

/**
 * Sitemap for Search Engines
 *
 * Main controller.
 *
 * @package Sitemap for Search Engines
 * @version 1.1.0
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
 * @param array $actions Registered actions.
 *
 * @return void
 */
function SitemapActions(&$actions)
{
	$actions['sitemap'] = array('Sitemap.php', 'ShowSiteMap');
}

/**
 * Adds the Sitemap settings to the Administration Center.
 *
 * @param array $config_vars Modification settings.
 *
 * @return void
 */
function SitemapGeneralModSettings(&$config_vars)
{
	global $txt;

	loadLanguage('Sitemap');

	$txt['sitemap_xml_info'] = sprintf($txt['sitemap_xml_info'], SitemapCreateUrl('xml'));

	$config_vars[] = array('title', 'sitemap');
	$config_vars[] = array('desc', 'sitemap_xml_info');
	$config_vars[] = array('int', 'sitemap_topic_count', 'min' => 1);
	$config_vars[] = array('int', 'sitemap_cache_ttl', 'min' => 0);
	$config_vars[] = array('select', 'sitemap_30day_priority', SitemapPriorities());
	$config_vars[] = array('select', 'sitemap_60day_priority', SitemapPriorities());
	$config_vars[] = array('select', 'sitemap_90day_priority', SitemapPriorities());
	$config_vars[] = array('select', 'sitemap_91day_priority', SitemapPriorities());
}

/**
 * Routes and displays the Sitemap.
 *
 * @return void
 */
function ShowSiteMap()
{
	$subAction = isset($_REQUEST['sa']) ? strtolower((string) $_REQUEST['sa']) : 'boards';

	if ($subAction === 'xml')
	{
		ShowSiteMapXml();
		return;
	}

	ShowSiteMapHtml($subAction);
}

/**
 * Displays the HTML Sitemap.
 *
 * @param string $subAction Requested extension.
 *
 * @return void
 */
function ShowSiteMapHtml($subAction)
{
	global $context, $txt, $sourcedir;

	loadLanguage('Sitemap');

	$sections = SitemapDisplaySections();

	if (empty($sections))
		fatal_lang_error('sitemap_no_sections', false);

	if (!isset($sections[$subAction]))
		$subAction = isset($sections['boards']) ? 'boards' : key($sections);

	$extension = $sections[$subAction];
	require_once($sourcedir . '/Sitemap-Ext/' . $extension['file']);

	$displayFunction = SitemapDisplayFunction($extension['name']);

	if (!function_exists($displayFunction))
		fatal_lang_error('invalid_sitemap_subaction', false);

	$listOptions = $displayFunction();

	if (!is_array($listOptions))
		fatal_lang_error('invalid_sitemap_subaction', false);

	$context['page_title'] = $txt['sitemap'];
	$context['linktree'][] = array(
		'url' => SitemapCreateUrl('index'),
		'name' => $txt['sitemap'],
	);
	$context['sitemap_sections'] = array();

	foreach ($sections as $key => $section)
	{
		$languageKey = 'sitemap_section_' . $key;
		$context['sitemap_sections'][$key] = array(
			'title' => isset($txt[$languageKey]) ? $txt[$languageKey] : $section['name'],
			'url' => $section['url'],
			'active' => $key === $subAction,
		);
	}

	require_once($sourcedir . '/Subs-List.php');

	$listOptions['id'] = 'sitemap';
	$listOptions['base_href'] = SitemapCreateUrl($subAction);

	createList($listOptions);
	loadTemplate('GenericList');

	$context['sub_template'] = 'show_list';
	$context['default_list'] = 'sitemap';
}

/**
 * Displays the XML Sitemap index or one of its child Sitemaps.
 *
 * @return void
 */
function ShowSiteMapXml()
{
	global $context, $sourcedir;

	$context['template_layers'] = array();
	$context['sitemap_xml'] = true;

	header('Content-Type: application/xml; charset=UTF-8');
	header('X-Robots-Tag: noindex, follow');

	$type = isset($_REQUEST['type']) ? strtolower((string) $_REQUEST['type']) : '';

	if ($type === 'boards')
	{
		require_once($sourcedir . '/Sitemap-Ext/Boards.php');
		SitemapOutputUrlSet(SitemapBoardsXml());
		obExit(false);
	}

	if ($type === 'topics')
	{
		require_once($sourcedir . '/Sitemap-Ext/Topics.php');

		$page = isset($_REQUEST['page']) ? max(1, (int) $_REQUEST['page']) : 1;
		$limit = SitemapXmlPageSize();
		$start = ($page - 1) * $limit;

		SitemapOutputUrlSet(SitemapTopicsXml($start, $limit));
		obExit(false);
	}

	require_once($sourcedir . '/Sitemap-Ext/Boards.php');
	require_once($sourcedir . '/Sitemap-Ext/Topics.php');

	$sitemaps = array();
	$boardsLastmod = SitemapBoardsLastmod();
	$sitemaps[] = array(
		'loc' => SitemapCreateUrl('xml', array('type' => 'boards')),
		'lastmod' => $boardsLastmod > 0 ? gmdate('Y-m-d\\TH:i:s\\Z', $boardsLastmod) : '',
	);

	$limit = SitemapXmlPageSize();
	$topicCount = SitemapTopicsGetCount();
	$pageCount = (int) ceil($topicCount / $limit);

	for ($page = 1; $page <= $pageCount; $page++)
	{
		$start = ($page - 1) * $limit;
		$lastmod = SitemapTopicsPageLastmod($start, $limit);

		$sitemaps[] = array(
			'loc' => SitemapCreateUrl('xml', array('type' => 'topics', 'page' => $page)),
			'lastmod' => $lastmod > 0 ? gmdate('Y-m-d\\TH:i:s\\Z', $lastmod) : '',
		);
	}

	SitemapOutputIndex($sitemaps);
	obExit(false);
}

/**
 * Adds the Sitemap link to HTML pages.
 *
 * @param string $buffer Page output.
 *
 * @return string
 */
function SitemapBuffer($buffer)
{
	global $context;

	if (!empty($context['sitemap_xml']) || stripos($buffer, '</body>') === false)
		return $buffer;

	return str_ireplace('</body>', SitemapFooterLink() . "\n</body>", $buffer);
}
