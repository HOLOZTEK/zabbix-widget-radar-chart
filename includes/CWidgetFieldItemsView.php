<?php declare(strict_types = 0);

namespace Modules\RadarChart\Includes;

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
			CWidgetFieldItems::AGG_LAST => _('Latest'),
			CWidgetFieldItems::AGG_MAX  => _('Max'),
			CWidgetFieldItems::AGG_MIN  => _('Min'),
			CWidgetFieldItems::AGG_AVG  => _('Avg'),
		];

		$view = (new CTable())
			->setId('list_' . $this->field->getName())
			->setHeader([
				'',
				(new CColHeader(_('Item')))->addStyle('width: 28%'),
				(new CColHeader(_('Label')))->addStyle('width: 18%'),
				(new CColHeader(_('Max value')))->addStyle('width: 13%'),
				(new CColHeader(_('Aggregation')))->addStyle('width: 13%'),
				_('Action'),
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
				(new CDiv($item['name'] ?: _('(not selected)')))
					->setTitle($item['name'])
					->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS),
				(new CDiv($item['label']))->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS),
				(new CDiv($item['max_val']))->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS),
				(new CDiv($agg_labels[$item['agg_func']] ?? ''))->addClass(ZBX_STYLE_OVERFLOW_ELLIPSIS),
				(new CList([
					(new CButton('edit', _('Edit')))
						->addClass(ZBX_STYLE_BTN_LINK)
						->removeId(),
					(new CButton('remove', _('Remove')))
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
				(new CButton('add', _('Add')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->setEnabled($can_add && !$this->isDisabled())
			))->setColSpan($view->getNumCols())
		);

		return $view;
	}
}
