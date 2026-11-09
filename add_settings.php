<?php

/**
 * Sitemap for Search Engines
 *
 * Installation script.
 *
 * This file is included inside SMF's PackageInstall() function. All work is
 * therefore isolated in an anonymous function to avoid overwriting variables
 * used internally by the package manager.
 *
 * @package Sitemap for Search Engines
 * @version 1.3.0
 * @author Matthias Feik
 * @copyright Matthias Feik
 * @license MIT
 */

if (!defined('SMF'))
	die('No direct access...');

(function () {
	global $modSettings;

	$sitemap_defaults = array(
		'sitemap_topic_count' => '1000',
		'sitemap_cache_ttl' => '3600',
		'sitemap_30day_priority' => '0.8',
		'sitemap_60day_priority' => '0.6',
		'sitemap_90day_priority' => '0.4',
		'sitemap_91day_priority' => '0.2',
	);
	$sitemap_updates = array();

	foreach ($sitemap_defaults as $sitemap_key => $sitemap_value)
	{
		if (!isset($modSettings[$sitemap_key]))
			$sitemap_updates[$sitemap_key] = $sitemap_value;
	}

	if (!empty($sitemap_updates))
		updateSettings($sitemap_updates);
})();
