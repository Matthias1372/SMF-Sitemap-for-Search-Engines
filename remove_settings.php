<?php

/**
 * Sitemap for Search Engines
 *
 * Uninstallation script.
 *
 * This file is included inside SMF's PackageInstall() function. All work is
 * isolated in an anonymous function to avoid overwriting package-manager
 * variables. Integration hooks are removed declaratively in package-info.xml.
 *
 * @package Sitemap for Search Engines
 * @version 1.2.0
 * @author Matthias Feik
 * @copyright Matthias Feik
 * @license MIT
 */

if (!defined('SMF'))
	die('No direct access...');

(function () {
	global $smcFunc;

	$sitemap_setting_keys = array(
		'sitemap_topic_count',
		'sitemap_cache_ttl',
		'sitemap_30day_priority',
		'sitemap_60day_priority',
		'sitemap_90day_priority',
		'sitemap_91day_priority',
	);

	$smcFunc['db_query'](
		'',
		'DELETE FROM {db_prefix}settings
		WHERE variable IN ({array_string:variables})',
		array(
			'variables' => $sitemap_setting_keys,
		)
	);

	cache_put_data('sitemap_extensions', null, 0);
})();
