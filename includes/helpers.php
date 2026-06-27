<?php declare(strict_types = 0);

if (!function_exists('_rc')) {
	function _rc(string $string): string {
		return dgettext('radar-chart', $string);
	}
}
