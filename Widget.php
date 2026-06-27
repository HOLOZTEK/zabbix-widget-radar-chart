<?php declare(strict_types = 0);

namespace Modules\RadarChart;

use Zabbix\Core\CWidget;

require_once __DIR__ . '/includes/helpers.php';

class Widget extends CWidget {

	public function getDefaultName(): string {
		return _rc('Radar Chart');
	}

	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'No data available.'
					=> _rc('No data available.'),
				'Previous page'
					=> _rc('Previous page'),
				'Next page'
					=> _rc('Next page'),
				'Showing all hosts — click to hide all-missing hosts'
					=> _rc('Showing all hosts — click to hide all-missing hosts'),
				'All-missing hosts are hidden — click to show'
					=> _rc('All-missing hosts are hidden — click to show'),
				'History data limit reached. Aggregated values may be incomplete.'
					=> _rc('History data limit reached. Aggregated values may be incomplete.'),
			]
		];
	}
}
