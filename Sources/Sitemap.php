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
}

/**
 * Displays the HTML Sitemap.
 *
 * @return void
 */
function ShowSiteMap()
{
}

/**
 * Displays the XML Sitemap.
 *
 * @return void
 */
function ShowSiteMapXml()
{
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
	return $buffer;
}
