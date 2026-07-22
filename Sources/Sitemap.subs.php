<?php

/**
 * Sitemap for Search Engines
 *
 * Common helper functions.
 *
 * @package Sitemap for Search Engines
 * @version 1.0.0
 * @author Matthias Feik
 * @copyright Matthias Feik
 * @license MIT
 */

if (!defined('SMF'))
	die('No direct access...');

/**
 * Returns the default settings for the Sitemap modification.
 *
 * These values are used during installation and as fallbacks if
 * a setting has not yet been stored in the database.
 *
 * @return array
 */
function SitemapDefaultSettings()
{
	return array(
		'sitemap_topic_count' => '1000',
		'sitemap_cache_ttl' => '3600',
		'sitemap_30day_priority' => '0.8',
		'sitemap_60day_priority' => '0.6',
		'sitemap_90day_priority' => '0.4',
		'sitemap_91day_priority' => '0.2',
	);
}

/**
 * Returns all Sitemap setting keys.
 *
 * @return array
 */
function SitemapSettingKeys()
{
	return array_keys(SitemapDefaultSettings());
}

/**
 * Returns the current Sitemap settings.
 *
 * Missing settings are automatically filled with the default values.
 *
 * @return array
 */
function SitemapSettings()
{
	global $modSettings;

	$settings = SitemapDefaultSettings();

	foreach ($settings as $key => $default)
	{
		if (isset($modSettings[$key]) && $modSettings[$key] !== '')
			$settings[$key] = $modSettings[$key];
	}

	return $settings;
}

/**
 * Returns cached data.
 *
 * Wrapper around SMF's cache_get_data().
 *
 * @param string $key Cache key.
 * @param int $ttl Cache lifetime in seconds. If 0, the configured
 *                 Sitemap cache lifetime is used.
 *
 * @return mixed
 */
function SitemapCacheGet($key, $ttl = 0)
{
	if ($ttl <= 0)
	{
		$settings = SitemapSettings();
		$ttl = (int) $settings['sitemap_cache_ttl'];
	}

	return cache_get_data($key, $ttl);
}

/**
 * Stores data in the cache.
 *
 * Wrapper around SMF's cache_put_data().
 *
 * @param string $key Cache key.
 * @param mixed $value Data to cache.
 * @param int $ttl Cache lifetime in seconds. If 0, the configured
 *                 Sitemap cache lifetime is used.
 *
 * @return void
 */
function SitemapCachePut($key, $value, $ttl = 0)
{
	if ($ttl <= 0)
	{
		$settings = SitemapSettings();
		$ttl = (int) $settings['sitemap_cache_ttl'];
	}

	cache_put_data($key, $value, $ttl);
}

/**
 * Returns a cache key for the Sitemap modification.
 *
 * Prepends the standard Sitemap prefix to the specified cache name.
 *
 * @param string $name Cache name.
 *
 * @return string
 */
function SitemapCacheKey($name)
{
	return 'sitemap_' . $name;
}

/**
 * Creates a Sitemap URL.
 *
 * @param string $type URL type.
 * @param array $parameters Optional URL parameters.
 *
 * @return string
 */
function SitemapCreateUrl($type, $parameters = array())
{
	global $scripturl;

	switch ($type)
	{
		case 'index':
			$url = $scripturl . '?action=sitemap';
			break;

		case 'xml':
			$url = $scripturl . '?action=sitemap;xml';
			break;

		case 'boards':
			$url = $scripturl . '?action=sitemap;sa=boards';
			break;

		case 'topics':
			$url = $scripturl . '?action=sitemap;sa=topics';
			break;

		default:
			$url = $scripturl . '?action=sitemap;sa=' . urlencode($type);
			break;
	}

	if (!empty($parameters))
	{
		foreach ($parameters as $key => $value)
			$url .= ';' . urlencode($key) . '=' . urlencode($value);
	}

	return $url;
}

/**
 * Calculates the XML priority for a topic.
 *
 * @param int $timestamp Unix timestamp of the topic's last modification.
 *
 * @return string
 */
function SitemapGetPriority($timestamp)
{
	$settings = SitemapSettings();

	$age = floor((time() - (int) $timestamp) / 86400);

	if ($age <= 30)
		return $settings['sitemap_30day_priority'];

	if ($age <= 60)
		return $settings['sitemap_60day_priority'];

	if ($age <= 90)
		return $settings['sitemap_90day_priority'];

	return $settings['sitemap_91day_priority'];
}

/**
 * Loads all available Sitemap extensions.
 *
 * Searches the Sources/Sitemap-Ext directory for extension files and
 * returns information about the available display and XML handlers.
 *
 * @return array
 */
function SitemapLoadExtensions()
{
	global $sourcedir;

	$extensions = SitemapCacheGet('extensions');

	if ($extensions !== null)
		return $extensions;

	$extensions = array();

	$directory = $sourcedir . '/Sitemap-Ext';

	if (!is_dir($directory))
		return $extensions;

	$dir = opendir($directory);

	if ($dir === false)
		return $extensions;

	while (($file = readdir($dir)) !== false)
	{
		if ($file === '.' || $file === '..')
			continue;

		if (substr($file, -4) !== '.php')
			continue;

		$name = basename($file, '.php');

		require_once($directory . '/' . $file);

		$extensions[$name] = array(
			'file' => $file,
			'display' => function_exists('Sitemap' . $name . 'Display'),
			'xml' => function_exists('Sitemap' . $name . 'Xml'),
		);
	}

	closedir($dir);

	ksort($extensions);

	SitemapCachePut('extensions', $extensions);

	return $extensions;
}

/**
 * Returns a single Sitemap extension.
 *
 * @param string $name Extension name.
 *
 * @return array|null
 */
function SitemapGetExtension($name)
{
	$extensions = SitemapLoadExtensions();

	if (isset($extensions[$name]))
		return $extensions[$name];

	return null;
}

/**
 * Returns all available HTML Sitemap sections.
 *
 * @return array
 */
function SitemapDisplaySections()
{
	$extensions = SitemapLoadExtensions();

	$sections = array();

	foreach ($extensions as $name => $extension)
	{
		if (empty($extension['display']))
			continue;

		$sections[$name] = array(
			'name' => $name,
			'url' => SitemapCreateUrl(strtolower($name)),
			'file' => $extension['file'],
		);
	}

	return $sections;
}

/**
 * Returns all available XML Sitemap sections.
 *
 * @return array
 */
function SitemapXmlSections()
{
	$extensions = SitemapLoadExtensions();

	$sections = array();

	foreach ($extensions as $name => $extension)
	{
		if (empty($extension['xml']))
			continue;

		$sections[$name] = $extension;
	}

	return $sections;
}

/**
 * Escapes a string for XML output.
 *
 * @param string $string String to escape.
 *
 * @return string
 */
function SitemapEscapeXml($string)
{
	return htmlspecialchars($string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

/**
 * Returns the XML namespace.
 *
 * @return string
 */
function SitemapXmlNamespace()
{
	return 'http://www.sitemaps.org/schemas/sitemap/0.9';
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
	global $txt, $scripturl;

	loadLanguage('Sitemap');

	$settings = SitemapDefaultSettings();

	$priorities = array(
		'1.0' => '1.0',
		'0.9' => '0.9',
		'0.8' => '0.8',
		'0.7' => '0.7',
		'0.6' => '0.6',
		'0.5' => '0.5',
		'0.4' => '0.4',
		'0.3' => '0.3',
		'0.2' => '0.2',
		'0.1' => '0.1',
		'0.0' => '0.0',
	);

	$config_vars[] = '';

	$config_vars[] = array(
		'title',
		'sitemap',
	);

	$config_vars[] = array(
		'message',
		sprintf(
			$txt['sitemap_xml_info'],
			SitemapCreateUrl('xml')
		)
	);

	$config_vars[] = array(
		'int',
		'sitemap_topic_count',
		0,
		$settings['sitemap_topic_count'],
	);

	$config_vars[] = array(
		'int',
		'sitemap_cache_ttl',
		0,
		$settings['sitemap_cache_ttl'],
	);

	$config_vars[] = array(
		'select',
		'sitemap_30day_priority',
		$priorities,
		$settings['sitemap_30day_priority'],
	);

	$config_vars[] = array(
		'select',
		'sitemap_60day_priority',
		$priorities,
		$settings['sitemap_60day_priority'],
	);

	$config_vars[] = array(
		'select',
		'sitemap_90day_priority',
		$priorities,
		$settings['sitemap_90day_priority'],
	);

	$config_vars[] = array(
		'select',
		'sitemap_91day_priority',
		$priorities,
		$settings['sitemap_91day_priority'],
	);
}
