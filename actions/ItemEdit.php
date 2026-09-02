<?php declare(strict_types = 0);

namespace Modules\HoloztekRadarChart\Actions;

use API,
	CController,
	CControllerResponseData;

use Modules\HoloztekRadarChart\Includes\CWidgetFieldItems;

class ItemEdit extends CController {

	protected function init(): void {
		$this->disableCsrfValidation();
	}

	protected function checkInput(): bool {
		$fields = [
			'itemid'    => 'int32',
			'name'      => 'string',
			'label'     => 'string',
			'min_val'   => 'string',
			'max_val'   => 'string',
			'direction' => 'in 0,1',
			'agg_func'  => 'in 0,1,2,3',
			'edit'      => 'in 1',
			'update'    => 'in 1',
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode([
					'error' => ['messages' => array_column(get_and_clear_messages(), 'message')]
				], JSON_THROW_ON_ERROR)]))->disableView()
			);
		}

		return $ret;
	}

	protected function checkPermissions(): bool {
		return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
	}

	protected function doAction(): void {
		if ($this->hasInput('update')) {
			$itemid  = (int) $this->getInput('itemid', 0);
			$min_val = trim($this->getInput('min_val', ''));
			$max_val = trim($this->getInput('max_val', ''));

			// 最小値の空欄は 0 として扱う（旧設定・未入力互換）
			if ($min_val === '') {
				$min_val = '0';
			}

			if ($itemid <= 0) {
				$this->setResponse(
					(new CControllerResponseData(['main_block' => json_encode([
						'error' => [
							'title'    => _holoztek_rc('Cannot save item'),
							'messages' => [_holoztek_rc('Item is required.')]
						]
					], JSON_THROW_ON_ERROR)]))->disableView()
				);
				return;
			}

			$num_error = null;
			if ($max_val === '' || !is_numeric($max_val)) {
				$num_error = _holoztek_rc('Max value must be a number.');
			}
			elseif (!is_numeric($min_val)) {
				$num_error = _holoztek_rc('Min value must be a number.');
			}
			elseif ((float) $min_val >= (float) $max_val) {
				$num_error = _holoztek_rc('Minimum value must be less than maximum value.');
			}

			if ($num_error !== null) {
				$this->setResponse(
					(new CControllerResponseData(['main_block' => json_encode([
						'error' => [
							'title'    => _holoztek_rc('Cannot save item'),
							'messages' => [$num_error]
						]
					], JSON_THROW_ON_ERROR)]))->disableView()
				);
				return;
			}

			// 数値アイテム（float / unsigned int）のみ受け付ける
			$item_data = API::Item()->get([
				'output'   => ['value_type'],
				'itemids'  => [$itemid],
				'webitems' => true,
			]);

			if (!$item_data || !in_array(
				(int) $item_data[0]['value_type'],
				[ITEM_VALUE_TYPE_FLOAT, ITEM_VALUE_TYPE_UINT64],
				true
			)) {
				$this->setResponse(
					(new CControllerResponseData(['main_block' => json_encode([
						'error' => [
							'title'    => _holoztek_rc('Cannot save item'),
							'messages' => [_holoztek_rc('Only numeric (float or unsigned integer) items are supported.')]
						]
					], JSON_THROW_ON_ERROR)]))->disableView()
				);
				return;
			}

			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode([
					'itemid'    => $itemid,
					'name'      => $this->getInput('name', ''),
					'label'     => $this->getInput('label', ''),
					'min_val'   => $min_val,
					'max_val'   => $max_val,
					'direction' => (int) $this->getInput('direction', CWidgetFieldItems::DIR_NORMAL),
					'agg_func'  => (int) $this->getInput('agg_func', CWidgetFieldItems::AGG_LAST),
					'edit'      => $this->hasInput('edit') ? 1 : null,
				], JSON_THROW_ON_ERROR)]))->disableView()
			);
			return;
		}

		$this->setResponse(new CControllerResponseData([
			'action'    => $this->getAction(),
			'itemid'    => (int) $this->getInput('itemid', 0),
			'name'      => $this->getInput('name', ''),
			'label'     => $this->getInput('label', ''),
			'min_val'   => $this->getInput('min_val', '0'),
			'max_val'   => $this->getInput('max_val', '100'),
			'direction' => (int) $this->getInput('direction', CWidgetFieldItems::DIR_NORMAL),
			'agg_func'  => (int) $this->getInput('agg_func', CWidgetFieldItems::AGG_LAST),
			'edit'      => $this->hasInput('edit') ? 1 : null,
			'user'      => ['debug_mode' => $this->getDebugMode()],
		]));
	}
}
