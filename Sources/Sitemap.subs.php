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
}

/**
 * Returns cached data.
 *
 * Wrapper around SMF's cache_get_data().
 *
 * @param string $key Cache key.
 * @param int $ttl Cache lifetime in seconds.
 *
 * @return mixed
 */
function SitemapCacheGet($key, $ttl = 0)
{
}

/**
 * Stores data in the cache.
 *
 * Wrapper around SMF's cache_put_data().
 *
 * @param string $key Cache key.
 * @param mixed $value Data to cache.
 * @param int $ttl Cache lifetime in seconds.
 *
 * @return void
 */
function SitemapCachePut($key, $value, $ttl = 0)
{
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
}

/**
 * Calculates the XML priority for a topic.
 *
 * @param int $timestamp Topic modification timestamp.
 *
 * @return string
 */
function SitemapGetPriority($timestamp)
{
}

/**
 * Loads all available Sitemap extensions.
 *
 * @return array
 */
function SitemapLoadExtensions()
{
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
}

/**
 * Returns all available HTML Sitemap sections.
 *
 * @return array
 */
function SitemapDisplaySections()
{
}

/**
 * Returns all XML Sitemap sections.
 *
 * @return array
 */
function SitemapXmlSections()
{
}

/**
 * Escapes a string for XML output.
 *
 * @param string $string
 *
 * @return string
 */
function SitemapEscapeXml($string)
{
}

/**
 * Returns the current XML namespace.
 *
 * @return string
 */
function SitemapXmlNamespace()
{
}

/**
 * Returns the XML schema location.
 *
 * @return string
 */
function SitemapXmlSchema()
{
}
