<?php declare(strict_types = 0);

namespace Modules\HoloztekRadarChart\Includes;

use CButton,
	CCol,
	CColHeader,
	CDiv,
	CList,
	CTable,
	CVar;

use CWidgetFieldView;

class CWidgetFieldItemsView extends CWidgetFieldView {

	public function __construct(CWidgetFieldItems $field) {
		$this->field = $field;
	}

	public function getView(): CTable {
		$items = $this->field->getValue();

		// 最低 MIN_ITEMS 行を常に表示
		$empty = ['itemid' => 0, 'name' => '', 'label' => '', 'max_val' => '100', 'agg_func' => 0];
		while (count($items) < CWidgetFieldItems::MIN_ITEMS) {
			$items[] = $empty;
		}

		$agg_labels = [
			CWidgetFieldItems::AGG_LAST => _holoztek_rc('Latest'),
			CWidgetFieldItems::AGG_MAX  => _holoztek_rc('Max'),
			CWidgetFieldItems::AGG_MIN  => _holoztek_rc('Min'),
			CWidgetFieldItems::AGG_AVG  => _holoztek_rc('Avg'),
		];

		$view = (new CTable())
			->setId('list_' . $this->field->getName())
			->setHeader([
				'',
				(new CColHeader(_holoztek_rc('Item')))->addStyle('width: 28%'),
				(new CColHeader(_holoztek_rc('Label')))->addStyle('width: 18%'),
				(new CColHeader(_holoztek_rc('Max value')))->addStyle('width: 13%'),
				(new CColHeader(_holoztek_rc('Aggregation')))->addStyle('width: 13%'),
				_holoztek_rc('Action'),
			]);

		foreach ($items as $i => $item) {
			$can_remove = count($items) > CWidgetFieldItems::MIN_ITEMS;

			$column_data = [
				new CVar('sort_order[items][]',          $i),
				new CVar('items[' . $i . '][itemid]',   $item['itemid']),
				new CVar('items[' . $i . '][name]',     $item['name']),
				new CVar('items[' . $i . '][label]',    $item['label']),
				new CVar('items[' . $i . '][max_val]',  $item['max_val']),
				new CVar('items[' . $i . '][agg_func]', $item['agg_func']),
			];

			$view->addRow([
				(new CCol((new CDiv())->addClass(ZBX_STYLE_DRAG_ICON)))->addClass(ZBX_STYLE_TD_DRAG_ICON),
				(new CDiv($item['name'] ?: _holoztek_rc('(not selected)')))
					->setTitle($item['name'])
					->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS),
				(new CDiv($item['label']))->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS),
				(new CDiv($item['max_val']))->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS),
				(new CDiv($agg_labels[$item['agg_func']] ?? ''))->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS),
				(new CList([
					(new CButton('edit', _holoztek_rc('Edit')))
						->addClass(ZBX_STYLE_BTN_LINK)
						->removeId(),
					(new CButton('remove', _holoztek_rc('Remove')))
						->addClass(ZBX_STYLE_BTN_LINK)
						->setEnabled($can_remove)
						->removeId(),
					$column_data,
				]))->addClass(ZBX_STYLE_HOR_LIST),
			]);
		}

		$can_add = count($items) < CWidgetFieldItems::MAX_ITEMS;

		$view->addRow(
			(new CCol(
				(new CButton('add', _holoztek_rc('Add')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->setEnabled($can_add && !$this->isDisabled())
			))->setColSpan($view->getNumCols())
		);

		return $view;
	}
}
