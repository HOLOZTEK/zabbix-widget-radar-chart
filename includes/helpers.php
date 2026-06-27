<?php declare(strict_types = 0);

if (!function_exists('_rc')) {
	bindtextdomain('radar-chart', __DIR__ . '/../locale');
	bind_textdomain_codeset('radar-chart', 'UTF-8');

	function _rc(string $string): string {
		return dgettext('radar-chart', $string);
	}
}
