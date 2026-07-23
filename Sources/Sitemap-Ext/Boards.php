<?php

/**
 * Sitemap for Search Engines - Boards extension.
 *
 * @package Sitemap for Search Engines
 * @version 1.1.0
 * @author Matthias Feik
 * @license MIT
 */

if (!defined('SMF'))
	die('No direct access...');

/**
 * Returns list options for the public board Sitemap.
 *
 * @return array
 */
function SitemapBoardsDisplay()
{
	global $txt;

	return array(
		'title' => $txt['sitemap_section_boards'],
		'no_items_label' => $txt['sitemap_no_boards'],
		'items_per_page' => 1000,
		'base_href' => SitemapCreateUrl('boards'),
		'default_sort_col' => 'board',
		'get_items' => array(
			'function' => 'SitemapBoardsGetItems',
		),
		'get_count' => array(
			'function' => 'SitemapBoardsGetCount',
		),
		'columns' => array(
			'board' => array(
				'header' => array('value' => $txt['sitemap_board']),
				'data' => array('db' => 'board_link'),
				'sort' => array('default' => 'c.cat_order, b.board_order', 'reverse' => 'c.cat_order DESC, b.board_order DESC'),
			),
			'topics' => array(
				'header' => array('value' => $txt['sitemap_topics']),
				'data' => array('db' => 'num_topics'),
				'sort' => array('default' => 'b.num_topics', 'reverse' => 'b.num_topics DESC'),
			),
			'posts' => array(
				'header' => array('value' => $txt['sitemap_posts']),
				'data' => array('db' => 'num_posts'),
				'sort' => array('default' => 'b.num_posts', 'reverse' => 'b.num_posts DESC'),
			),
		),
	);
}

/**
 * Counts boards visible to guests.
 *
 * @return int
 */
function SitemapBoardsGetCount()
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}boards AS b
		WHERE FIND_IN_SET({int:guest_group}, b.member_groups) != 0
			AND FIND_IN_SET({int:guest_group}, b.deny_member_groups) = 0
			AND b.redirect = {string:empty}',
		array('guest_group' => -1, 'empty' => '')
	);
	list ($count) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return (int) $count;
}

/**
 * Returns boards visible to guests.
 *
 * @param int $start Start offset.
 * @param int $items_per_page Number of items.
 * @param string $sort SQL sort expression.
 *
 * @return array
 */
function SitemapBoardsGetItems($start, $items_per_page, $sort)
{
	global $scripturl, $smcFunc;

	$allowedSorts = array(
		'c.cat_order, b.board_order',
		'c.cat_order DESC, b.board_order DESC',
		'b.num_topics',
		'b.num_topics DESC',
		'b.num_posts',
		'b.num_posts DESC',
	);

	if (!in_array($sort, $allowedSorts, true))
		$sort = 'c.cat_order, b.board_order';

	$request = $smcFunc['db_query']('', '
		SELECT b.id_board, b.name, b.num_topics, b.num_posts
		FROM {db_prefix}boards AS b
			INNER JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
		WHERE FIND_IN_SET({int:guest_group}, b.member_groups) != 0
			AND FIND_IN_SET({int:guest_group}, b.deny_member_groups) = 0
			AND b.redirect = {string:empty}
		ORDER BY ' . $sort . '
		LIMIT {int:start}, {int:limit}',
		array('guest_group' => -1, 'empty' => '', 'start' => (int) $start, 'limit' => (int) $items_per_page)
	);

	$items = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$items[] = array(
			'board_link' => '<a href="' . $scripturl . '?board=' . (int) $row['id_board'] . '.0">' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</a>',
			'num_topics' => comma_format($row['num_topics']),
			'num_posts' => comma_format($row['num_posts']),
		);
	}

	$smcFunc['db_free_result']($request);

	return $items;
}

/**
 * Returns public board URLs for the XML Sitemap.
 *
 * @return array
 */
function SitemapBoardsXml()
{
	global $scripturl, $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT b.id_board, MAX(m.poster_time) AS last_update
		FROM {db_prefix}boards AS b
			LEFT JOIN {db_prefix}messages AS m ON (m.id_msg = b.id_last_msg)
		WHERE FIND_IN_SET({int:guest_group}, b.member_groups) != 0
			AND FIND_IN_SET({int:guest_group}, b.deny_member_groups) = 0
			AND b.redirect = {string:empty}
		GROUP BY b.id_board
		ORDER BY b.id_board',
		array('guest_group' => -1, 'empty' => '')
	);

	$urls = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$urls[] = array(
			'loc' => $scripturl . '?board=' . (int) $row['id_board'] . '.0',
			'lastmod' => !empty($row['last_update']) ? gmdate('Y-m-d\TH:i:s\Z', (int) $row['last_update']) : '',
			'changefreq' => 'daily',
			'priority' => '0.7',
		);
	}

	$smcFunc['db_free_result']($request);

	return $urls;
}


/**
 * Returns the newest modification timestamp in the board Sitemap.
 *
 * @return int
 */
function SitemapBoardsLastmod()
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT MAX(m.poster_time)
		FROM {db_prefix}boards AS b
			LEFT JOIN {db_prefix}messages AS m ON (m.id_msg = b.id_last_msg)
		WHERE FIND_IN_SET({int:guest_group}, b.member_groups) != 0
			AND FIND_IN_SET({int:guest_group}, b.deny_member_groups) = 0
			AND b.redirect = {string:empty}',
		array('guest_group' => -1, 'empty' => '')
	);

	list ($lastmod) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return (int) $lastmod;
}
