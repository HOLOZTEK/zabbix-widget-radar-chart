<?php declare(strict_types = 0);

namespace Modules\RadarChart\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;

use Modules\RadarChart\Includes\CWidgetFieldItems;

class WidgetView extends CControllerDashboardWidgetView {

	protected function init(): void {
		parent::init();
	}

	protected function doAction(): void {
		$fields        = $this->fields_values;
		$groupids_raw  = array_values(array_filter(array_map('intval', (array) ($fields['groupids']      ?? []))));
		$hostids       = array_values(array_filter(array_map('intval', (array) ($fields['hostids']       ?? []))));
		$host_patterns = array_values(array_filter((array) ($fields['host_patterns'] ?? [])));
		$items_cfg     = (array) ($fields['items'] ?? []);
		$grid_columns  = max(1, (int) ($fields['grid_columns'] ?? 3));
		$grid_rows     = max(1, (int) ($fields['grid_rows'] ?? 2));

		$style = [
			'line_color'       => $fields['line_color']       ?: '4a90d9',
			'line_width'       => max(1, (int) ($fields['line_width']       ?? 2)),
			'fill_color'       => $fields['fill_color']       ?: '4a90d9',
			'fill_opacity'     => max(0, min(100, (int) ($fields['fill_opacity']  ?? 15))),
			'point_color'      => $fields['point_color']      ?: '4a90d9',
			'point_size'       => max(1, (int) ($fields['point_size']       ?? 4)),
			'chart_color'      => $fields['chart_color']      ?: 'b0c4de',
			'chart_line_width' => max(1, (int) ($fields['chart_line_width'] ?? 1)),
			'title_color'      => $fields['title_color']      ?: '333333',
			'title_size'       => max(8, min(24, (int) ($fields['title_size']      ?? 11))),
		];

		$time_period = $fields['time_period'] ?? [];
		$period_from = (int) ($time_period['from_ts'] ?? strtotime('-1 hour'));
		$period_to   = (int) ($time_period['to_ts']   ?? time());

		$widget_name = $this->getInput('name', $this->widget->getDefaultName());

		// アイテム設定（itemid が設定済みのもののみ）
		$items_cfg = array_values(array_filter($items_cfg, fn($i) => (int) ($i['itemid'] ?? 0) > 0));

		if (count($items_cfg) < CWidgetFieldItems::MIN_ITEMS) {
			$this->setResponse(new CControllerResponseData([
				'name'  => $widget_name,
				'error' => _('At least 3 items must be configured.'),
				'user'  => ['debug_mode' => $this->getDebugMode()],
			]));
			return;
		}

		// ホストグループ → サブグループ展開
		$groupids = $groupids_raw ? getSubGroups($groupids_raw) : null;

		// ホスト指定が何もなければエラー
		if (!$groupids && !$hostids && !$host_patterns) {
			$this->setResponse(new CControllerResponseData([
				'name'  => $widget_name,
				'error' => _('Specify at least one host or host group.'),
				'user'  => ['debug_mode' => $this->getDebugMode()],
			]));
			return;
		}

		$host_base = ['output' => ['hostid', 'name'], 'monitored_hosts' => true];
		$hosts_map = [];

		// groupids によるホスト取得
		if ($groupids) {
			foreach (API::Host()->get($host_base + ['groupids' => $groupids]) as $h) {
				$hosts_map[(int) $h['hostid']] = $h;
			}
		}

		// 個別 hostids によるホスト取得
		if ($hostids) {
			foreach (API::Host()->get($host_base + ['hostids' => $hostids]) as $h) {
				$hosts_map[(int) $h['hostid']] = $h;
			}
		}

		// host_patterns によるホスト取得（groupids フィルターを AND 適用）
		if ($host_patterns) {
			$pattern_params = $host_base;
			if ($groupids) {
				$pattern_params['groupids'] = $groupids;
			}
			// '*' 単独はすべてのホストを意味するので name フィルターを省略する
			if (!in_array('*', $host_patterns, true)) {
				$pattern_params['search']                 = ['name' => $host_patterns];
				$pattern_params['searchWildcardsEnabled'] = true;
				$pattern_params['searchByAny']            = true;
			}
			foreach (API::Host()->get($pattern_params) as $h) {
				$hosts_map[(int) $h['hostid']] = $h;
			}
		}

		if (!$hosts_map) {
			$this->setResponse(new CControllerResponseData([
				'name'  => $widget_name,
				'error' => _('No hosts found.'),
				'user'  => ['debug_mode' => $this->getDebugMode()],
			]));
			return;
		}

		uasort($hosts_map, fn($a, $b) => strcmp($a['name'], $b['name']));
		$hosts    = array_values($hosts_map);
		$all_hids = array_column($hosts, 'hostid');

		// 参照アイテムのキー・value_type・単位を取得
		$ref_itemids = array_column($items_cfg, 'itemid');
		$ref_items   = API::Item()->get([
			'output'  => ['itemid', 'key_', 'value_type', 'units'],
			'itemids' => $ref_itemids,
		]);
		$ref_by_id = array_column($ref_items, null, 'itemid');

		// アイテム設定にキーと value_type を付加
		foreach ($items_cfg as &$ic) {
			$ref = $ref_by_id[$ic['itemid']] ?? null;
			$ic['key_']       = $ref ? $ref['key_']       : '';
			$ic['value_type'] = $ref ? (int) $ref['value_type'] : 0;
		}
		unset($ic);

		// 各アイテムキーについて全ホストのアイテムを検索
		$key_items = [];
		foreach ($items_cfg as $ic) {
			if ($ic['key_'] === '') continue;
			$found = API::Item()->get([
				'output'    => ['itemid', 'hostid', 'key_', 'lastvalue', 'lastclock', 'value_type'],
				'hostids'   => $all_hids,
				'filter'    => ['key_' => $ic['key_']],
				'monitored' => true,
			]);
			foreach ($found as $fi) {
				$key_items[$ic['key_']][(int) $fi['hostid']] = $fi;
			}
		}

		// value_type ごとにヒストリー集計が必要なアイテムを収集
		$hist_need = []; // [key_ => [hostid => [itemid, value_type]]]
		foreach ($items_cfg as $ic) {
			if ($ic['agg_func'] == CWidgetFieldItems::AGG_LAST) continue;
			if ($ic['key_'] === '') continue;
			foreach ($key_items[$ic['key_']] ?? [] as $hid => $fi) {
				$vt = (int) $fi['value_type'];
				if ($vt != ITEM_VALUE_TYPE_FLOAT && $vt != ITEM_VALUE_TYPE_UINT64) continue;
				$hist_need[$ic['key_']][$hid] = ['itemid' => (int) $fi['itemid'], 'value_type' => $vt];
			}
		}

		// ヒストリー集計: value_type ごとにバッチ取得
		$hist_agg = []; // [key_ => [hostid => [max, min, sum, cnt]]]
		foreach ($hist_need as $key_ => $hid_map) {
			$vtype_iids = [];
			$iid_hid    = [];
			foreach ($hid_map as $hid => $info) {
				$vtype_iids[$info['value_type']][] = $info['itemid'];
				$iid_hid[$info['itemid']] = $hid;
			}
			foreach ($vtype_iids as $vtype => $iids) {
				$history = API::History()->get([
					'output'    => ['itemid', 'value'],
					'itemids'   => $iids,
					'time_from' => $period_from,
					'time_till' => $period_to,
					'history'   => $vtype,
					'limit'     => 50000,
				]);
				foreach ($history as $row) {
					$hid = $iid_hid[$row['itemid']] ?? null;
					if ($hid === null) continue;
					$v = (float) $row['value'];
					if (!isset($hist_agg[$key_][$hid])) {
						$hist_agg[$key_][$hid] = ['max' => $v, 'min' => $v, 'sum' => $v, 'cnt' => 1];
					} else {
						$a = &$hist_agg[$key_][$hid];
						if ($v > $a['max']) $a['max'] = $v;
						if ($v < $a['min']) $a['min'] = $v;
						$a['sum'] += $v;
						$a['cnt']++;
						unset($a);
					}
				}
			}
		}

		// ホストごとのチャートデータ構築
		$chart_hosts = [];
		foreach ($hosts as $host) {
			$hid             = (int) $host['hostid'];
			$values          = [];
			$clocks          = [];  // 最新値: lastclock、集計値: 0（時間帯表示）
			$no_data_indices = [];  // データなし or 値なし のアイテムインデックス

			foreach ($items_cfg as $idx => $ic) {
				$key_  = $ic['key_'];
				$agg   = (int) $ic['agg_func'];
				$fi    = $key_items[$key_][$hid] ?? null;

				if ($fi === null || $key_ === '') {
					$values[]          = 0.0;
					$clocks[]          = 0;
					$no_data_indices[] = $idx;
					continue;
				}

				if ($agg === CWidgetFieldItems::AGG_LAST) {
					$clock     = (int) ($fi['lastclock'] ?? 0);
					$values[]  = $clock ? round((float) $fi['lastvalue'], 4) : 0.0;
					$clocks[]  = $clock;
					if (!$clock) $no_data_indices[] = $idx;
				} else {
					$agg_data = $hist_agg[$key_][$hid] ?? null;
					$clocks[] = 0;  // 集計値は時間帯で表示
					if ($agg_data === null) {
						$values[]          = 0.0;
						$no_data_indices[] = $idx;
					} else {
						$values[] = round(
							match ($agg) {
								CWidgetFieldItems::AGG_MAX => $agg_data['max'],
								CWidgetFieldItems::AGG_MIN => $agg_data['min'],
								CWidgetFieldItems::AGG_AVG => $agg_data['sum'] / $agg_data['cnt'],
								default                    => 0.0,
							},
							4
						);
					}
				}
			}

			$chart_hosts[] = [
				'hostid'          => $hid,
				'name'            => $host['name'],
				'values'          => $values,
				'clocks'          => $clocks,
				'no_data_indices' => $no_data_indices,
			];
		}

		// アイテム表示定義（単位を参照アイテムから取得）
		$indicators = [];
		foreach ($items_cfg as $ic) {
			$ref = $ref_by_id[$ic['itemid']] ?? null;
			$indicators[] = [
				'label'   => $ic['label'] ?: ($ic['name'] ?: $ic['key_']),
				'max_val' => (float) ($ic['max_val'] ?: 100),
				'units'   => $ref ? (string) $ref['units'] : '',
				'agg'     => (int) $ic['agg_func'],
			];
		}

		$this->setResponse(new CControllerResponseData([
			'name'       => $widget_name,
			'chart_data' => [
				'hosts'        => $chart_hosts,
				'indicators'   => $indicators,
				'period_from'  => $period_from,
				'period_to'    => $period_to,
				'grid_columns' => $grid_columns,
				'grid_rows'    => $grid_rows,
				'total'        => count($chart_hosts),
				'style'        => $style,
			],
			'user' => ['debug_mode' => $this->getDebugMode()],
		]));
	}
}
