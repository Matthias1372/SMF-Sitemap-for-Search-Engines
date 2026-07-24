<?php

/**
 * Sitemap for Search Engines - Topics extension.
 *
 * @package Sitemap for Search Engines
 * @version 1.2.0
 * @author Matthias Feik
 * @license MIT
 */

if (!defined('SMF'))
	die('No direct access...');

/**
 * Returns list options for the public topic Sitemap.
 *
 * @return array
 */
function SitemapTopicsDisplay()
{
	global $txt;

	$settings = SitemapSettings();

	return array(
		'title' => $txt['sitemap_section_topics'],
		'no_items_label' => $txt['sitemap_no_topics'],
		'items_per_page' => max(1, (int) $settings['sitemap_topic_count']),
		'base_href' => SitemapCreateUrl('topics'),
		'default_sort_col' => 'updated',
		'get_items' => array('function' => 'SitemapTopicsGetItems'),
		'get_count' => array('function' => 'SitemapTopicsGetCount'),
		'columns' => array(
			'topic' => array(
				'header' => array('value' => $txt['sitemap_topic']),
				'data' => array('db' => 'topic_link'),
				'sort' => array('default' => 'mf.subject', 'reverse' => 'mf.subject DESC'),
			),
			'board' => array(
				'header' => array('value' => $txt['sitemap_board']),
				'data' => array('db' => 'board_link'),
				'sort' => array('default' => 'b.name', 'reverse' => 'b.name DESC'),
			),
			'replies' => array(
				'header' => array('value' => $txt['sitemap_replies']),
				'data' => array('db' => 'num_replies'),
				'sort' => array('default' => 't.num_replies', 'reverse' => 't.num_replies DESC'),
			),
			'updated' => array(
				'header' => array('value' => $txt['sitemap_last_updated']),
				'data' => array('db' => 'last_updated'),
				'sort' => array('default' => 'ml.poster_time DESC', 'reverse' => 'ml.poster_time'),
			),
		),
	);
}

/**
 * Counts approved topics in boards visible to guests.
 *
 * @return int
 */
function SitemapTopicsGetCount()
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}topics AS t
			INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
		WHERE t.approved = {int:approved}
			AND FIND_IN_SET({int:guest_group}, b.member_groups) != 0
			AND FIND_IN_SET({int:guest_group}, b.deny_member_groups) = 0
			AND b.redirect = {string:empty}',
		array('approved' => 1, 'guest_group' => -1, 'empty' => '')
	);
	list ($count) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return (int) $count;
}

/**
 * Returns approved topics in boards visible to guests.
 *
 * @param int $start Start offset.
 * @param int $items_per_page Number of items.
 * @param string $sort SQL sort expression.
 *
 * @return array
 */
function SitemapTopicsGetItems($start, $items_per_page, $sort)
{
	global $scripturl, $smcFunc;

	$allowedSorts = array('mf.subject', 'mf.subject DESC', 'b.name', 'b.name DESC', 't.num_replies', 't.num_replies DESC', 'ml.poster_time DESC', 'ml.poster_time');

	if (!in_array($sort, $allowedSorts, true))
		$sort = 'ml.poster_time DESC';

	$request = $smcFunc['db_query']('', '
		SELECT t.id_topic, t.num_replies, b.id_board, b.name AS board_name,
			mf.subject, ml.poster_time
		FROM {db_prefix}topics AS t
			INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
			INNER JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)
			INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
		WHERE t.approved = {int:approved}
			AND FIND_IN_SET({int:guest_group}, b.member_groups) != 0
			AND FIND_IN_SET({int:guest_group}, b.deny_member_groups) = 0
			AND b.redirect = {string:empty}
		ORDER BY ' . $sort . '
		LIMIT {int:start}, {int:limit}',
		array('approved' => 1, 'guest_group' => -1, 'empty' => '', 'start' => (int) $start, 'limit' => (int) $items_per_page)
	);

	$items = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$subject = $row['subject'];
		censorText($subject);

		$items[] = array(
			'topic_link' => '<a href="' . $scripturl . '?topic=' . (int) $row['id_topic'] . '.0">' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</a>',
			'board_link' => '<a href="' . $scripturl . '?board=' . (int) $row['id_board'] . '.0">' . htmlspecialchars($row['board_name'], ENT_QUOTES, 'UTF-8') . '</a>',
			'num_replies' => comma_format($row['num_replies']),
			'last_updated' => timeformat((int) $row['poster_time']),
		);
	}

	$smcFunc['db_free_result']($request);

	return $items;
}

/**
 * Returns public topic URLs for the XML Sitemap.
 *
 * @return array
 */
function SitemapTopicsXml($start = 0, $limit = 50000)
{
	global $scripturl, $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT t.id_topic, ml.poster_time
		FROM {db_prefix}topics AS t
			INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
			INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
		WHERE t.approved = {int:approved}
			AND FIND_IN_SET({int:guest_group}, b.member_groups) != 0
			AND FIND_IN_SET({int:guest_group}, b.deny_member_groups) = 0
			AND b.redirect = {string:empty}
		ORDER BY t.id_topic
		LIMIT {int:start}, {int:limit}',
		array(
			'approved' => 1,
			'guest_group' => -1,
			'empty' => '',
			'start' => max(0, (int) $start),
			'limit' => max(1, min(SitemapXmlPageSize(), (int) $limit)),
		)
	);

	$urls = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$urls[] = array(
			'loc' => $scripturl . '?topic=' . (int) $row['id_topic'] . '.0',
			'lastmod' => gmdate('Y-m-d\TH:i:s\Z', (int) $row['poster_time']),
			'changefreq' => 'weekly',
			'priority' => SitemapGetPriority($row['poster_time']),
		);
	}

	$smcFunc['db_free_result']($request);

	return $urls;
}


/**
 * Returns the newest topic modification timestamp in one Sitemap page.
 *
 * @param int $start Start offset.
 * @param int $limit Number of topics in the page.
 *
 * @return int
 */
function SitemapTopicsPageLastmod($start, $limit)
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT MAX(sitemap_topics.poster_time)
		FROM (
			SELECT ml.poster_time
			FROM {db_prefix}topics AS t
				INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
				INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
			WHERE t.approved = {int:approved}
				AND FIND_IN_SET({int:guest_group}, b.member_groups) != 0
				AND FIND_IN_SET({int:guest_group}, b.deny_member_groups) = 0
				AND b.redirect = {string:empty}
			ORDER BY t.id_topic
			LIMIT {int:start}, {int:limit}
		) AS sitemap_topics',
		array(
			'approved' => 1,
			'guest_group' => -1,
			'empty' => '',
			'start' => max(0, (int) $start),
			'limit' => max(1, min(SitemapXmlPageSize(), (int) $limit)),
		)
	);

	list ($lastmod) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return (int) $lastmod;
}
