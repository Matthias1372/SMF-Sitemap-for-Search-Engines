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

require_once(__DIR__ . '/Sources/Sitemap.subs.php');

global $smcFunc;

$settings = SitemapSettingKeys();

$smcFunc['db_query'](
	'',
	'DELETE FROM {db_prefix}settings
	WHERE variable IN ({array_string:variables})',
	array(
		'variables' => $settings,
	)
);
