<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

$view = new CWidgetView($data);

if (!empty($data['chart_data'])) {
	$view->setVar('chart_data', $data['chart_data']);
}
if (!empty($data['error'])) {
	$view->setVar('error', $data['error']);
}

$view->addItem(new CDiv())->show();
