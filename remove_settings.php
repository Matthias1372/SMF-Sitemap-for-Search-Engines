<?php

/**
 * Sitemap for Search Engines
 *
 * @package Sitemap for Search Engines
 * @version 1.0.0
 * @author Matthias Feik
 * @copyright Matthias Feik
 * @license MIT
 */

if (!defined('SMF'))
	die('No direct access...');

global $smcFunc;

$settings = array(
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
		'variables' => $settings,
	)
);
