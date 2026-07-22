<?php

/**
 * Sitemap for Search Engines
 *
 * @package Sitemap
 * @author Matthias Feik
 * @copyright 2026 Matthias Feik
 * @license MIT
 */

/**
 * Displays the HTML Sitemap.
 *
 * @return void
 */
function template_sitemap_main()
{
	global $context, $txt;

	echo '
	<div id="sitemap">
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['sitemap'], '
			</h3>
		</div>';

	if (!empty($context['sitemap_sections']))
	{
		echo '
		<div class="buttonlist">
			<ul>';

		foreach ($context['sitemap_sections'] as $name => $section)
		{
			echo '
				<li>
					<a href="', $section['url'], '"', !empty($section['active']) ? ' class="active"' : '', '>
						<span>', $section['title'], '</span>
					</a>
				</li>';
		}

		echo '
			</ul>
		</div>';
	}

	echo '
		<div class="clear"></div>';

	if (!empty($context['default_list']))
		template_show_list($context['default_list']);

	echo '
	</div>';
}
