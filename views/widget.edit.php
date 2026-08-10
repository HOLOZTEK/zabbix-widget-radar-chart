<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

use Modules\HoloztekRadarChart\Includes\CWidgetFieldItemsView;

(new CWidgetFormView($data))
	->addField(
		new CWidgetFieldMultiSelectGroupView($data['fields']['groupids'])
	)
	->addField(
		new CWidgetFieldMultiSelectHostView($data['fields']['hostids'])
	)
	->addField(
		new CWidgetFieldPatternSelectHostView($data['fields']['host_patterns'])
	)
	->addField(
		new CWidgetFieldItemsView($data['fields']['items'])
	)
	->addItem([
		new CLabel(_holoztek_rc('Grid')),
		new CFormField(
			(new CDiv([
				(new CNumericBox('grid_columns', $data['fields']['grid_columns']->getValue(), 2)),
				(new CTag('span', true, _holoztek_rc('columns ×'))),
				(new CNumericBox('grid_rows', $data['fields']['grid_rows']->getValue(), 2)),
				(new CTag('span', true, _holoztek_rc('rows'))),
			]))->addClass('rc-style-row')
		)
	])
	->addItem([
		new CLabel(_holoztek_rc('Style')),
		new CFormField(
			(new CDiv([
				(new CDiv([
					(new CTag('span', true, _holoztek_rc('Line')))->addClass('rc-style-label'),
					(new CColor('line_color',  $data['fields']['line_color']->getValue())),
					(new CNumericBox('line_width', $data['fields']['line_width']->getValue(), 2)),
				]))->addClass('rc-style-row'),
				(new CDiv([
					(new CTag('span', true, _holoztek_rc('Point')))->addClass('rc-style-label'),
					(new CColor('point_color', $data['fields']['point_color']->getValue())),
					(new CNumericBox('point_size', $data['fields']['point_size']->getValue(), 2)),
				]))->addClass('rc-style-row'),
				(new CDiv([
					(new CTag('span', true, _holoztek_rc('Fill')))->addClass('rc-style-label'),
					(new CColor('fill_color',  $data['fields']['fill_color']->getValue())),
					(new CNumericBox('fill_opacity', $data['fields']['fill_opacity']->getValue(), 3)),
					(new CTag('span', true, '%')),
				]))->addClass('rc-style-row'),
				(new CDiv([
					(new CTag('span', true, _holoztek_rc('Chart')))->addClass('rc-style-label'),
					(new CColor('chart_color', $data['fields']['chart_color']->getValue())),
					(new CNumericBox('chart_line_width', $data['fields']['chart_line_width']->getValue(), 2)),
				]))->addClass('rc-style-row'),
				(new CDiv([
					(new CTag('span', true, _holoztek_rc('Title')))->addClass('rc-style-label'),
					(new CColor('title_color', $data['fields']['title_color']->getValue())),
					(new CNumericBox('title_size', $data['fields']['title_size']->getValue(), 2)),
				]))->addClass('rc-style-row'),
			]))->addClass('rc-style-block')
		)
	])
	->addField(
		new CWidgetFieldTimePeriodView($data['fields']['time_period'])
	)
	->includeJsFile('widget.edit.js.php')
	->addJavaScript('holoztek_radar_chart_form.init();')
	->show();
