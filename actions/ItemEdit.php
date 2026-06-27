<?php declare(strict_types = 0);

namespace Modules\RadarChart\Actions;

use CController,
	CControllerResponseData;

use Modules\RadarChart\Includes\CWidgetFieldItems;

class ItemEdit extends CController {

	protected function init(): void {
		$this->disableCsrfValidation();
	}

	protected function checkInput(): bool {
		$fields = [
			'itemid'   => 'int32',
			'name'     => 'string',
			'label'    => 'string',
			'max_val'  => 'string',
			'agg_func' => 'in 0,1,2,3',
			'edit'     => 'in 1',
			'update'   => 'in 1',
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
			$max_val = trim($this->getInput('max_val', ''));

			if ($itemid <= 0) {
				$this->setResponse(
					(new CControllerResponseData(['main_block' => json_encode([
						'error' => [
							'title'    => _('Cannot save item'),
							'messages' => [_('Item is required.')]
						]
					], JSON_THROW_ON_ERROR)]))->disableView()
				);
				return;
			}

			if ($max_val === '' || !is_numeric($max_val) || (float) $max_val <= 0) {
				$this->setResponse(
					(new CControllerResponseData(['main_block' => json_encode([
						'error' => [
							'title'    => _('Cannot save item'),
							'messages' => [_('Max value must be a positive number.')]
						]
					], JSON_THROW_ON_ERROR)]))->disableView()
				);
				return;
			}

			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode([
					'itemid'   => $itemid,
					'name'     => $this->getInput('name', ''),
					'label'    => $this->getInput('label', ''),
					'max_val'  => $max_val,
					'agg_func' => (int) $this->getInput('agg_func', CWidgetFieldItems::AGG_LAST),
					'edit'     => $this->hasInput('edit') ? 1 : null,
				], JSON_THROW_ON_ERROR)]))->disableView()
			);
			return;
		}

		$this->setResponse(new CControllerResponseData([
			'action'   => $this->getAction(),
			'itemid'   => (int) $this->getInput('itemid', 0),
			'name'     => $this->getInput('name', ''),
			'label'    => $this->getInput('label', ''),
			'max_val'  => $this->getInput('max_val', '100'),
			'agg_func' => (int) $this->getInput('agg_func', CWidgetFieldItems::AGG_LAST),
			'edit'     => $this->hasInput('edit') ? 1 : null,
			'user'     => ['debug_mode' => $this->getDebugMode()],
		]));
	}
}
