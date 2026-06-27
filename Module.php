<?php declare(strict_types = 0);

namespace Modules\RadarChart;

use Zabbix\Core\CModule;

class Module extends CModule {

	public function init(): void {
		require_once __DIR__ . '/includes/helpers.php';
		bindtextdomain('radar-chart', __DIR__ . '/locale');
		bind_textdomain_codeset('radar-chart', 'UTF-8');
	}
}
