<?php declare(strict_types = 0);

namespace Modules\HoloztekRadarChart\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;

use Modules\HoloztekRadarChart\Includes\CWidgetFieldItems;

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
				'error' => _holoztek_rc('At least 3 items must be configured.'),
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
				'error' => _holoztek_rc('Specify at least one host or host group.'),
				'user'  => ['debug_mode' => $this->getDebugMode()],
			]));
			return;
		}

		$host_base = ['output' => ['hostid', 'name'], 'monitored_hosts' => true];
		$hosts_map = [];

		// [仕様：案B]
		// groupids は host_patterns のスコープとして機能する。
		// host_patterns が指定されている場合、groupids 単独ではホストを直接追加しない。
		// host_patterns が指定されていない場合、groupids のホストをすべて追加する。
		if ($groupids && !$host_patterns) {
			foreach (API::Host()->get($host_base + ['groupids' => $groupids]) as $h) {
				$hosts_map[(int) $h['hostid']] = $h;
			}
		}

		// 個別 hostids によるホスト取得（常に追加）
		if ($hostids) {
			foreach (API::Host()->get($host_base + ['hostids' => $hostids]) as $h) {
				$hosts_map[(int) $h['hostid']] = $h;
			}
		}

		// host_patterns によるホスト取得
		// groupids が指定されていればそのグループ内でのみ絞り込む（スコープとして機能）
		// '*' 単独はすべてのホスト（スコープ内）を意味するので name フィルターを省略する
		if ($host_patterns) {
			$pattern_params = $host_base;
			if ($groupids) {
				$pattern_params['groupids'] = $groupids;
			}
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
				'error' => _holoztek_rc('No hosts found.'),
				'user'  => ['debug_mode' => $this->getDebugMode()],
			]));
			return;
		}

		uasort($hosts_map, fn($a, $b) => strcmp($a['name'], $b['name']));
		$hosts    = array_values($hosts_map);
		$all_hids = array_column($hosts, 'hostid');

		// 参照アイテムの名前・value_type・単位を取得
		// マッチングはアイテム名（テンプレート・ホストの所属は無視）を基準に行う
		$ref_itemids = array_column($items_cfg, 'itemid');
		$ref_items   = API::Item()->get([
			'output'  => ['itemid', 'name', 'value_type', 'units'],
			'itemids' => $ref_itemids,
		]);
		$ref_by_id = array_column($ref_items, null, 'itemid');

		// アイテム設定にマッチング用アイテム名と value_type を付加
		foreach ($items_cfg as &$ic) {
			$ref = $ref_by_id[$ic['itemid']] ?? null;
			$ic['item_name']  = $ref ? $ref['name']            : '';
			$ic['value_type'] = $ref ? (int) $ref['value_type'] : -1;
		}
		unset($ic);

		// 各アイテム名について全ホストのアイテムを検索（数値アイテムのみ）。
		// 同一ホスト内でアイテム名が重複する場合はアイテムIDが若番の方を採用する
		// （itemid昇順で取得し、ホストごとに最初の1件のみ採用することで実現）。
		$name_items = [];
		foreach ($items_cfg as $ic) {
			$item_name = $ic['item_name'];
			if ($item_name === '' || isset($name_items[$item_name])) continue;

			$found = API::Item()->get([
				'output'    => ['itemid', 'hostid', 'name', 'value_type'],
				'hostids'   => $all_hids,
				'filter'    => ['name' => $item_name],
				'monitored' => true,
				'sortfield' => 'itemid',
				'sortorder' => ZBX_SORT_UP,
			]);
			foreach ($found as $fi) {
				// 数値以外のアイテムはスキップ（表示しない）
				$vt = (int) $fi['value_type'];
				if ($vt !== ITEM_VALUE_TYPE_FLOAT && $vt !== ITEM_VALUE_TYPE_UINT64) continue;

				$hid = (int) $fi['hostid'];
				if (isset($name_items[$item_name][$hid])) continue;
				$name_items[$item_name][$hid] = $fi;
			}
		}

		$limit_hit  = false;
		$hist_limit = max(1000, (int) ($fields['hist_limit'] ?? 50000));

		// Latest（AGG_LAST）はダッシュボード指定期間内の最新値を使う（現在値=lastvalueではない）。
		// 取得は下のループで itemid 単位に sortfield=clock / sortorder=DESC / limit=1 の
		// 個別 History.get を行う。value_type 単位でまとめて limit=N のバッチ取得にすると、
		// 履歴量の多い別 itemid の行に押し出されて期間内最新値が欠落しうるため（v1.0.4 issue #3）、
		// 正確性を優先して個別取得にしている。代償として API 呼び出し回数が
		// 「対象ホスト数 × Latest 軸数」に比例するので、対象ホストが多い場合は
		// doAction 末尾の $latest_fetch_ops 判定で描画遅延の警告を出す（issue #6）。
		$last_need = []; // [item_name => [hostid => [itemid, value_type]]]
		foreach ($items_cfg as $ic) {
			if ($ic['agg_func'] != CWidgetFieldItems::AGG_LAST) continue;
			if ($ic['item_name'] === '') continue;
			foreach ($name_items[$ic['item_name']] ?? [] as $hid => $fi) {
				$last_need[$ic['item_name']][$hid] = ['itemid' => (int) $fi['itemid'], 'value_type' => (int) $fi['value_type']];
			}
		}

		$last_result = []; // [item_name => [hostid => ['value'=>, 'clock'=>]]]
		foreach ($last_need as $item_name => $hid_map) {
			foreach ($hid_map as $hid => $info) {
				// itemid 単位で limit=1 取得する。他 itemid の履歴量に関わらず、
				// この itemid の期間内最新値だけを確実に取得するため
				// （他 itemid の大量履歴に押し出されて欠落することを防ぐ）。
				$history = API::History()->get([
					'output'    => ['itemid', 'clock', 'value'],
					'itemids'   => [$info['itemid']],
					'time_from' => $period_from,
					'time_till' => $period_to,
					'history'   => $info['value_type'],
					'sortfield' => 'clock',
					'sortorder' => ZBX_SORT_DOWN,
					'limit'     => 1,
				]);

				if ($history) {
					$last_result[$item_name][$hid] = [
						'value' => (float) $history[0]['value'],
						'clock' => (int) $history[0]['clock'],
					];
				}
			}
		}

		// value_type ごとに Max/Min/Avg 集計が必要なアイテムを収集
		$hist_need = []; // [item_name => [hostid => [itemid, value_type]]]
		foreach ($items_cfg as $ic) {
			if ($ic['agg_func'] == CWidgetFieldItems::AGG_LAST) continue;
			if ($ic['item_name'] === '') continue;
			foreach ($name_items[$ic['item_name']] ?? [] as $hid => $fi) {
				$vt = (int) $fi['value_type'];
				$hist_need[$ic['item_name']][$hid] = ['itemid' => (int) $fi['itemid'], 'value_type' => $vt];
			}
		}

		// Max/Min/Avg 集計: 指定期間が2時間未満はHistory、2時間以上はTrendを優先して使う
		// （Trendにデータが無いitemidはHistoryにフォールバック）。
		// limit 到達時は不完全な集計になるため警告フラグを立てる（Historyのみ対象、
		// Trendは期間中の行数が少ないため実質上限に達しない）。
		$agg_result = []; // [item_name => [hostid => [max, min, avg]]]
		$period_duration = $period_to - $period_from;
		$use_trend = $period_duration >= 2 * SEC_PER_HOUR;

		if ($hist_need) {
			// itemid -> [item_name, hostid] 逆引き（トレンド結果の突合用）
			$iid_lookup = [];
			foreach ($hist_need as $item_name => $hid_map) {
				foreach ($hid_map as $hid => $info) {
					$iid_lookup[$info['itemid']] = [$item_name, $hid];
				}
			}

			$trend_seen = []; // itemid => true（トレンドデータが1件以上存在した）

			if ($use_trend) {
				$trend_acc = []; // itemid => [max, min, sum, numsum]

				foreach (API::Trend()->get([
					'output'    => ['itemid', 'num', 'value_min', 'value_max', 'value_avg'],
					'itemids'   => array_keys($iid_lookup),
					'time_from' => $period_from,
					'time_till' => $period_to,
				]) as $row) {
					$iid  = (int) $row['itemid'];
					$num  = (int) $row['num'];
					$vmax = (float) $row['value_max'];
					$vmin = (float) $row['value_min'];
					$vavg = (float) $row['value_avg'];

					if (!isset($trend_acc[$iid])) {
						$trend_acc[$iid] = ['max' => $vmax, 'min' => $vmin, 'sum' => $vavg * $num, 'numsum' => $num];
					} else {
						$a = &$trend_acc[$iid];
						if ($vmax > $a['max']) $a['max'] = $vmax;
						if ($vmin < $a['min']) $a['min'] = $vmin;
						$a['sum']    += $vavg * $num;
						$a['numsum'] += $num;
						unset($a);
					}
					$trend_seen[$iid] = true;
				}

				foreach ($trend_acc as $iid => $a) {
					[$item_name, $hid] = $iid_lookup[$iid];
					$agg_result[$item_name][$hid] = [
						'max' => $a['max'],
						'min' => $a['min'],
						'avg' => $a['numsum'] > 0 ? $a['sum'] / $a['numsum'] : 0.0,
					];
				}
			}

			// トレンド未使用（2時間未満）、またはトレンドにデータが無かったアイテムはHistoryで集計
			$hist_fallback_need = [];
			foreach ($hist_need as $item_name => $hid_map) {
				foreach ($hid_map as $hid => $info) {
					if (!isset($trend_seen[$info['itemid']])) {
						$hist_fallback_need[$item_name][$hid] = $info;
					}
				}
			}

			if ($hist_fallback_need) {
				$hist_agg = []; // [item_name => [hostid => [max, min, sum, cnt]]]

				foreach ($hist_fallback_need as $item_name => $hid_map) {
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
							'limit'     => $hist_limit,
						]);

						if (count($history) >= $hist_limit) {
							$limit_hit = true;
						}

						foreach ($history as $row) {
							$hid = $iid_hid[$row['itemid']] ?? null;
							if ($hid === null) continue;
							$v = (float) $row['value'];
							if (!isset($hist_agg[$item_name][$hid])) {
								$hist_agg[$item_name][$hid] = ['max' => $v, 'min' => $v, 'sum' => $v, 'cnt' => 1];
							} else {
								$a = &$hist_agg[$item_name][$hid];
								if ($v > $a['max']) $a['max'] = $v;
								if ($v < $a['min']) $a['min'] = $v;
								$a['sum'] += $v;
								$a['cnt']++;
								unset($a);
							}
						}
					}
				}

				foreach ($hist_agg as $item_name => $hid_map) {
					foreach ($hid_map as $hid => $a) {
						$agg_result[$item_name][$hid] = [
							'max' => $a['max'],
							'min' => $a['min'],
							'avg' => $a['sum'] / $a['cnt'],
						];
					}
				}
			}
		}

		// ホストごとのチャートデータ構築
		$chart_hosts = [];
		foreach ($hosts as $host) {
			$hid             = (int) $host['hostid'];
			$values          = [];
			$clocks          = [];  // 期間内最新値: その値のclock、集計値: 0（時間帯表示）
			$no_data_indices = [];  // データなし / 非数値アイテムのインデックス

			foreach ($items_cfg as $idx => $ic) {
				$item_name = $ic['item_name'];
				$agg       = (int) $ic['agg_func'];
				$fi        = $name_items[$item_name][$hid] ?? null;

				if ($fi === null || $item_name === '') {
					$values[]          = 0.0;
					$clocks[]          = 0;
					$no_data_indices[] = $idx;
					continue;
				}

				if ($agg === CWidgetFieldItems::AGG_LAST) {
					$last = $last_result[$item_name][$hid] ?? null;
					if ($last === null) {
						$values[]          = 0.0;
						$clocks[]          = 0;
						$no_data_indices[] = $idx;
					} else {
						$values[] = round($last['value'], 4);
						$clocks[] = $last['clock'];
					}
				} else {
					$agg_data = $agg_result[$item_name][$hid] ?? null;
					$clocks[] = 0;  // 集計値は時間帯で表示
					if ($agg_data === null) {
						$values[]          = 0.0;
						$no_data_indices[] = $idx;
					} else {
						$values[] = round(
							match ($agg) {
								CWidgetFieldItems::AGG_MAX => $agg_data['max'],
								CWidgetFieldItems::AGG_MIN => $agg_data['min'],
								CWidgetFieldItems::AGG_AVG => $agg_data['avg'],
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
				'label'     => $ic['label'] ?: ($ic['name'] ?: $ic['item_name']),
				'min_val'   => (float) ($ic['min_val'] ?? 0),
				'max_val'   => (float) ($ic['max_val'] ?? 100),
				'direction' => (int) ($ic['direction'] ?? 0),
				'units'     => $ref ? (string) $ref['units'] : '',
				'agg'       => (int) $ic['agg_func'],
			];
		}

		// Latest（個別 History.get）は「対象ホスト数 × Latest 軸数」に比例して API 呼び出しが
		// 増える。正確性維持のため個別取得は変えず、閾値を超えたら描画遅延を警告する（issue #6）。
		$latest_axis_count = 0;
		foreach ($items_cfg as $ic) {
			if ((int) $ic['agg_func'] === CWidgetFieldItems::AGG_LAST) $latest_axis_count++;
		}
		$latest_fetch_ops            = $latest_axis_count * count($all_hids);
		$latest_fetch_warn_threshold = max(10, (int) ($fields['latest_warn_threshold'] ?? 500));

		$warnings = [];
		if ($limit_hit) {
			$warnings[] = _holoztek_rc('History data limit reached. Aggregated values may be incomplete.');
		}
		if ($latest_fetch_ops > $latest_fetch_warn_threshold) {
			$warnings[] = _holoztek_rc('Large number of target hosts for Latest values. Dashboard rendering may be slow; narrow the host selection or use Max/Min/Avg aggregation.');
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
				'warnings'     => $warnings,
			],
			'user' => ['debug_mode' => $this->getDebugMode()],
		]));
	}
}
