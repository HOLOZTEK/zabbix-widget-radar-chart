<?php declare(strict_types = 0);

namespace Modules\RadarChart\Includes;

use Zabbix\Widgets\CWidgetField;

class CWidgetFieldItems extends CWidgetField {

	public const DEFAULT_VIEW = CWidgetFieldItemsView::class;

	public const AGG_LAST = 0;
	public const AGG_MAX  = 1;
	public const AGG_MIN  = 2;
	public const AGG_AVG  = 3;

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
				'itemid'   => (int) ($item['itemid'] ?? 0),
				'name'     => (string) ($item['name'] ?? ''),
				'label'    => (string) ($item['label'] ?? ''),
				'max_val'  => (string) ($item['max_val'] ?? '100'),
				'agg_func' => (int) ($item['agg_func'] ?? self::AGG_LAST),
			];
		}, $items)));

		return $this;
	}

	public function fromApi(array $values): void {
		$items = [];

		foreach ($values as $name => $value) {
			if (preg_match('/^items\.(\d+)\.(itemid|name|label|max_val|agg_func)$/', $name, $m)) {
				$items[(int) $m[1]][$m[2]] = $value;
			}
		}

		ksort($items);
		$this->setValue(array_values($items));
	}

	protected function getValidationRules(bool $strict = false): array {
		return ['type' => API_OBJECTS, 'fields' => [
			'itemid'   => ['type' => API_INT32],
			'name'     => ['type' => API_STRING_UTF8, 'length' => 255],
			'label'    => ['type' => API_STRING_UTF8, 'length' => 255],
			'max_val'  => ['type' => API_STRING_UTF8, 'length' => 64],
			'agg_func' => ['type' => API_INT32, 'in' => '0:3'],
		]];
	}

	public function toApi(array &$widget_fields = []): void {
		foreach ($this->getValue() as $i => $item) {
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_STR,   'name' => 'items.'.$i.'.itemid',   'value' => (string) $item['itemid']];
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_STR,   'name' => 'items.'.$i.'.name',     'value' => $item['name']];
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_STR,   'name' => 'items.'.$i.'.label',    'value' => $item['label']];
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_STR,   'name' => 'items.'.$i.'.max_val',  'value' => $item['max_val']];
			$widget_fields[] = ['type' => ZBX_WIDGET_FIELD_TYPE_INT32, 'name' => 'items.'.$i.'.agg_func', 'value' => $item['agg_func']];
		}
	}
}
