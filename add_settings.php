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
	'sitemap_topic_count' => '1000',
	'sitemap_cache_ttl' => '3600',
	'sitemap_30day_priority' => '0.8',
	'sitemap_60day_priority' => '0.6',
	'sitemap_90day_priority' => '0.4',
	'sitemap_91day_priority' => '0.2',
);

$request = $smcFunc['db_query'](
	'',
	'SELECT variable, value
	FROM {db_prefix}settings
	WHERE variable IN ({array_string:variables})',
	array(
		'variables' => array_keys($settings),
	)
);

$existing = array();

while ($row = $smcFunc['db_fetch_assoc']($request))
	$existing[$row['variable']] = $row['value'];

$smcFunc['db_free_result']($request);

$insert = array();

foreach ($settings as $variable => $value)
{
	if (!isset($existing[$variable]))
		$insert[] = array($variable, $value);
}

if (!empty($insert))
{
	$smcFunc['db_insert'](
		'insert',
		'{db_prefix}settings',
		array(
			'variable' => 'string-255',
			'value' => 'string-65534',
		),
		$insert,
		array('variable')
	);
}
