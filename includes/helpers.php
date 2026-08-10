<?php declare(strict_types = 0);

if (!function_exists('_holoztek_rc')) {
	bindtextdomain('holoztek-radar-chart', __DIR__ . '/../locale');
	bind_textdomain_codeset('holoztek-radar-chart', 'UTF-8');

	function _holoztek_rc(string $string): string {
		return dgettext('holoztek-radar-chart', $string);
	}
}
