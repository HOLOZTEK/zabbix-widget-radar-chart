<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

use Modules\HoloztekRadarChart\Includes\CWidgetFieldItems;

$form = (new CForm())
	->setId('rc_item_edit_form')
	->setName('rc_item_edit_form')
	->addStyle('display: none;')
	->addVar('action', $data['action'])
	->addVar('update', 1);

if ($data['edit']) {
	$form->addVar('edit', 1);
}

$form->addItem((new CSubmitButton())->addClass(ZBX_STYLE_FORM_SUBMIT_HIDDEN));

$agg_options = [
	CWidgetFieldItems::AGG_LAST => _holoztek_rc('Latest'),
	CWidgetFieldItems::AGG_MAX  => _holoztek_rc('Max'),
	CWidgetFieldItems::AGG_MIN  => _holoztek_rc('Min'),
	CWidgetFieldItems::AGG_AVG  => _holoztek_rc('Avg'),
];

$agg_select = (new CSelect('agg_func'))
	->setId('rc_agg_func')
	->setValue($data['agg_func']);

foreach ($agg_options as $val => $label) {
	$agg_select->addOption(new CSelectOption($val, $label));
}

$form->addItem(
	(new CFormGrid())
		->addItem([
			(new CLabel(_holoztek_rc('Item'), 'rc_item_name'))->setAsteriskMark(),
			new CFormField(
				(new CDiv([
					(new CTextBox('name', $data['name'], true))
						->setId('rc_item_name')
						->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
						->setAttribute('placeholder', _holoztek_rc('Select item...')),
					(new CButton('rc_item_select', _holoztek_rc('Select')))
						->addClass(ZBX_STYLE_BTN_GREY)
						->removeId(),
					(new CInput('hidden', 'itemid', $data['itemid']))->setId('rc_itemid'),
				]))->addClass('input-group')
			),
		])
		->addItem([
			new CLabel(_holoztek_rc('Label'), 'rc_label'),
			new CFormField(
				(new CTextBox('label', $data['label']))
					->setId('rc_label')
					->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
					->setAttribute('placeholder', _holoztek_rc('defaults to item name'))
			),
		])
		->addItem([
			(new CLabel(_holoztek_rc('Max value'), 'rc_max_val'))->setAsteriskMark(),
			new CFormField(
				(new CTextBox('max_val', $data['max_val']))
					->setId('rc_max_val')
					->setWidth(ZBX_TEXTAREA_TINY_WIDTH)
					->setAttribute('placeholder', '100')
			),
		])
		->addItem([
			new CLabel(_holoztek_rc('Aggregation'), 'rc_agg_func'),
			new CFormField($agg_select),
		])
)->addItem(
	(new CScriptTag('holoztek_rc_item_edit.init(' . json_encode([
		'form_id'  => 'rc_item_edit_form',
		'itemid'   => $data['itemid'],
		'item_name'=> $data['name'],
	], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');'))->setOnDocumentReady()
);

$output = [
	'header'        => $data['edit'] ? _holoztek_rc('Update item') : _holoztek_rc('New item'),
	'body'          => $form->toString(),
	'buttons'       => [[
		'title'    => $data['edit'] ? _holoztek_rc('Update') : _holoztek_rc('Add'),
		'keepOpen' => true,
		'isSubmit' => true,
		'action'   => 'holoztek_rc_item_edit.submit()',
	]],
	'script_inline' => $this->readJsFile('item.edit.js.php', null, ''),
];

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output, JSON_THROW_ON_ERROR);
