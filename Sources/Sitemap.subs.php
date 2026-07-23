<?php

/**
 * Sitemap for Search Engines
 *
 * Common helper functions.
 *
 * @package Sitemap for Search Engines
 * @version 1.1.0
 * @author Matthias Feik
 * @copyright Matthias Feik
 * @license MIT
 */

if (!defined('SMF'))
	die('No direct access...');


/**
 * Returns the default settings for the Sitemap modification.
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
 * Returns the selectable XML priority values.
 *
 * @return array
 */
function SitemapPriorities()
{
	$priorities = array();

	for ($priority = 10; $priority >= 0; $priority--)
	{
		$value = number_format($priority / 10, 1, '.', '');
		$priorities[$value] = $value;
	}

	return $priorities;
}

/**
 * Returns the current Sitemap settings.
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
 * Returns a cache key for the Sitemap modification.
 *
 * @param string $name Cache name.
 *
 * @return string
 */
function SitemapCacheKey($name)
{
	return 'sitemap_' . preg_replace('~[^a-z0-9_\-]~i', '', (string) $name);
}

/**
 * Returns cached data.
 *
 * @param string $name Logical cache name.
 * @param int|null $ttl Cache lifetime or null for the configured lifetime.
 *
 * @return mixed
 */
function SitemapCacheGet($name, $ttl = null)
{
	if ($ttl === null)
	{
		$settings = SitemapSettings();
		$ttl = (int) $settings['sitemap_cache_ttl'];
	}

	if ($ttl <= 0)
		return null;

	return cache_get_data(SitemapCacheKey($name), $ttl);
}

/**
 * Stores data in the cache.
 *
 * @param string $name Logical cache name.
 * @param mixed $value Data to cache.
 * @param int|null $ttl Cache lifetime or null for the configured lifetime.
 *
 * @return void
 */
function SitemapCachePut($name, $value, $ttl = null)
{
	if ($ttl === null)
	{
		$settings = SitemapSettings();
		$ttl = (int) $settings['sitemap_cache_ttl'];
	}

	if ($ttl <= 0)
		return;

	cache_put_data(SitemapCacheKey($name), $value, $ttl);
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

	$type = strtolower(preg_replace('~[^a-z0-9_\-]~i', '', (string) $type));

	if ($type === 'index')
		$url = $scripturl . '?action=sitemap';
	else
		$url = $scripturl . '?action=sitemap;sa=' . $type;

	foreach ($parameters as $key => $value)
		$url .= ';' . rawurlencode((string) $key) . '=' . rawurlencode((string) $value);

	return $url;
}

/**
 * Calculates the XML priority for a topic.
 *
 * @param int $timestamp Unix timestamp of the last modification.
 *
 * @return string
 */
function SitemapGetPriority($timestamp)
{
	$settings = SitemapSettings();
	$age = max(0, time() - (int) $timestamp);
	$days = (int) floor($age / 86400);

	if ($days <= 30)
		return $settings['sitemap_30day_priority'];

	if ($days <= 60)
		return $settings['sitemap_60day_priority'];

	if ($days <= 90)
		return $settings['sitemap_90day_priority'];

	return $settings['sitemap_91day_priority'];
}

/**
 * Loads all available Sitemap extensions.
 *
 * @return array
 */
function SitemapLoadExtensions()
{
	global $sourcedir;

	$extensions = SitemapCacheGet('extensions');

	if (is_array($extensions))
		return $extensions;

	$extensions = array();
	$directory = $sourcedir . '/Sitemap-Ext';
	$files = is_dir($directory) ? glob($directory . '/*.php') : array();

	if ($files === false)
		$files = array();

	foreach ($files as $path)
	{
		$file = basename($path);
		$name = basename($file, '.php');
		$key = strtolower($name);

		require_once($path);

		$extensions[$key] = array(
			'name' => $name,
			'file' => $file,
			'url' => SitemapCreateUrl($key),
			'display' => function_exists(SitemapDisplayFunction($name)),
			'xml' => function_exists(SitemapXmlFunction($name)),
		);
	}

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
	$key = strtolower((string) $name);

	return isset($extensions[$key]) ? $extensions[$key] : null;
}

/**
 * Returns extensions that provide an HTML list.
 *
 * @return array
 */
function SitemapDisplaySections()
{
	return array_filter(SitemapLoadExtensions(), function ($extension) {
		return !empty($extension['display']);
	});
}

/**
 * Returns extensions that provide XML URLs.
 *
 * @return array
 */
function SitemapXmlSections()
{
	return array_filter(SitemapLoadExtensions(), function ($extension) {
		return !empty($extension['xml']);
	});
}

/**
 * Returns the display handler name for an extension.
 *
 * @param string $extension Extension name.
 *
 * @return string
 */
function SitemapDisplayFunction($extension)
{
	return 'Sitemap' . ucfirst(strtolower((string) $extension)) . 'Display';
}

/**
 * Returns the XML handler name for an extension.
 *
 * @param string $extension Extension name.
 *
 * @return string
 */
function SitemapXmlFunction($extension)
{
	return 'Sitemap' . ucfirst(strtolower((string) $extension)) . 'Xml';
}

/**
 * Escapes a value for XML output.
 *
 * @param mixed $string Value to escape.
 *
 * @return string
 */
function SitemapEscapeXml($string)
{
	return htmlspecialchars((string) $string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

/**
 * Returns the Sitemap XML namespace.
 *
 * @return string
 */
function SitemapXmlNamespace()
{
	return 'http://www.sitemaps.org/schemas/sitemap/0.9';
}


/**
 * Returns the maximum number of URLs in a generated child Sitemap.
 *
 * @return int
 */
function SitemapXmlPageSize()
{
	return 50000;
}

/**
 * Outputs a Sitemap URL set.
 *
 * @param array $urls Sitemap URL records.
 *
 * @return void
 */
function SitemapOutputUrlSet($urls)
{
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="' . SitemapXmlNamespace() . '">' . "\n";

	if (is_array($urls))
	{
		foreach ($urls as $url)
		{
			if (!is_array($url) || empty($url['loc']))
				continue;

			echo "\t<url>\n";
			echo "\t\t<loc>" . SitemapEscapeXml($url['loc']) . "</loc>\n";

			foreach (array('lastmod', 'changefreq', 'priority') as $element)
			{
				if (isset($url[$element]) && $url[$element] !== '')
					echo "\t\t<" . $element . '>' . SitemapEscapeXml($url[$element]) . '</' . $element . ">\n";
			}

			echo "\t</url>\n";
		}
	}

	echo "</urlset>\n";
}

/**
 * Outputs a Sitemap index.
 *
 * @param array $sitemaps Child Sitemap records.
 *
 * @return void
 */
function SitemapOutputIndex($sitemaps)
{
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<sitemapindex xmlns="' . SitemapXmlNamespace() . '">' . "\n";

	foreach ($sitemaps as $sitemap)
	{
		if (!is_array($sitemap) || empty($sitemap['loc']))
			continue;

		echo "\t<sitemap>\n";
		echo "\t\t<loc>" . SitemapEscapeXml($sitemap['loc']) . "</loc>\n";

		if (!empty($sitemap['lastmod']))
			echo "\t\t<lastmod>" . SitemapEscapeXml($sitemap['lastmod']) . "</lastmod>\n";

		echo "\t</sitemap>\n";
	}

	echo "</sitemapindex>\n";
}

/**
 * Creates the footer link HTML.
 *
 * @return string
 */
function SitemapFooterLink()
{
	global $txt;

	loadLanguage('Sitemap');

	return '\n\t<div class="smalltext centertext sitemap_link"><a href="' . SitemapCreateUrl('xml') . '">' . $txt['sitemap_xml'] . '</a></div>';
}
