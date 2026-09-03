<?php declare(strict_types = 0);

namespace Modules\HoloztekRadarChart\Includes;

use Zabbix\Widgets\CWidgetField;

require_once __DIR__ . '/helpers.php';

class CWidgetFieldItems extends CWidgetField {

	public const DEFAULT_VIEW = CWidgetFieldItemsView::class;

	public const AGG_LAST = 0;
	public const AGG_MAX  = 1;
	public const AGG_MIN  = 2;
	public const AGG_AVG  = 3;

	public const DIR_NORMAL   = 0;
	public const DIR_REVERSED = 1;

	public const MIN_ITEMS = 3;
	public const MAX_ITEMS = 8;

	public const DEFAULT_VALUE = [];

	public function __construct() {
		parent::__construct('items', null);
		$this->setDefault(self::DEFAULT_VALUE);
	}

	public function setValue($value): static {
		$items = (array) $value;

		$this->value = array_values(array_filter(array_map(static function ($item) {
			if (!is_array($item)) return null;
			return [
				'itemid'    => (int) ($item['itemid'] ?? 0),
				'name'      => (string) ($item['name'] ?? ''),
				'label'     => (string) ($item['label'] ?? ''),
				'min_val'   => (string) ($item['min_val'] ?? '0'),
				'max_val'   => (string) ($item['max_val'] ?? '100'),
				'direction' => (int) ($item['direction'] ?? self::DIR_NORMAL),
				'agg_func'  => (int) ($item['agg_func'] ?? self::AGG_LAST),
			];
		}, $items)));

		return $this;
	}

	public function fromApi(array $values): void {
		$items = [];

		foreach ($values as $name => $value) {
			if (preg_match('/^items\.(\d+)\.(itemid|name|label|min_val|max_val|direction|agg_func)$/', $name, $m)) {
				$items[(int) $m[1]][$m[2]] = $value;
			}
		}

		ksort($items);
		$this->setValue(array_values($items));
	}

	protected function getValidationRules(bool $strict = false): array {
		return ['type' => API_OBJECTS, 'fields' => [
			'itemid'    => ['type' => API_INT32],
			'name'      => ['type' => API_STRING_UTF8, 'length' => 255],
			'label'     => ['type' => API_STRING_UTF8, 'length' => 255],
			'min_val'   => ['type' => API_STRING_UTF8, 'length' => 64],
			'max_val'   => ['type' => API_STRING_UTF8, 'length' => 64],
			'direction' => ['type' => API_INT32, 'in' => '0:1'],
			'agg_func'  => ['type' => API_INT32, 'in' => '0:3'],
		]];
	}

	/**
	 * min_val / max_val の数値性と min < max を、ウィジェット保存／API 経路でも検証する。
	 *
	 * getValidationRules() は min_val / max_val を文字列長でしか見ないため、
	 * アイテム編集モーダル（actions/ItemEdit.php）を通らない経路
	 * （Zabbix API、dashboard.update、hidden field 改変、移行データ）では
	 * min >= max や非数値が保存されうる。ここで同じ制約を担保する。
	 * direction（0/1）と agg_func（0:3）は getValidationRules() 側で担保済み。
	 */
	public function validate(bool $strict = false): array {
		$errors = parent::validate($strict);

		if ($errors) {
			return $errors;
		}

		foreach ($this->getValue() as $index => $item) {
			// 最小値の空欄は 0 として扱う（旧設定・未入力互換）
			$min_val = ($item['min_val'] === '') ? '0' : $item['min_val'];
			$max_val = $item['max_val'];

			$item_error = null;
			if ($max_val === '' || !is_numeric($max_val)) {
				$item_error = _holoztek_rc('Max value must be a number.');
			}
			elseif (!is_numeric($min_val)) {
				$item_error = _holoztek_rc('Min value must be a number.');
			}
			elseif ((float) $min_val >= (float) $max_val) {
				$item_error = _holoztek_rc('Minimum value must be less than maximum value.');
			}

			if ($item_error !== null) {
				$errors[] = _holoztek_rc('Item').' '.($index + 1).': '.$item_error;
			}
		}

		return $errors;
	}

	public function toApi(array &$widget_fields = []): void {
		foreach ($this->getValue() as $i => $item) {
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_STR,   'name' => 'items.'.$i.'.itemid',    'value' => (string) $item['itemid']];
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_STR,   'name' => 'items.'.$i.'.name',      'value' => $item['name']];
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_STR,   'name' => 'items.'.$i.'.label',     'value' => $item['label']];
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_STR,   'name' => 'items.'.$i.'.min_val',   'value' => $item['min_val']];
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_STR,   'name' => 'items.'.$i.'.max_val',   'value' => $item['max_val']];
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_INT32, 'name' => 'items.'.$i.'.direction', 'value' => $item['direction']];
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_INT32, 'name' => 'items.'.$i.'.agg_func',  'value' => $item['agg_func']];
		}
	}
}
