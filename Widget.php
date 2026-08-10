<?php declare(strict_types = 0);

namespace Modules\HoloztekRadarChart;

use Zabbix\Core\CWidget;

require_once __DIR__ . '/includes/helpers.php';

class Widget extends CWidget {

	public function getDefaultName(): string {
		return _holoztek_rc('Radar Chart');
	}

	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'No data available.'
					=> _holoztek_rc('No data available.'),
				'Previous page'
					=> _holoztek_rc('Previous page'),
				'Next page'
					=> _holoztek_rc('Next page'),
				'Showing all hosts — click to hide all-missing hosts'
					=> _holoztek_rc('Showing all hosts — click to hide all-missing hosts'),
				'All-missing hosts are hidden — click to show'
					=> _holoztek_rc('All-missing hosts are hidden — click to show'),
				'History data limit reached. Aggregated values may be incomplete.'
					=> _holoztek_rc('History data limit reached. Aggregated values may be incomplete.'),
			]
		];
	}
}
