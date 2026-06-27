<?php declare(strict_types = 0);

namespace Modules\RadarChart\Includes;

use CWidgetsData;

use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldColor,
	CWidgetFieldIntegerBox,
	CWidgetFieldMultiSelectGroup,
	CWidgetFieldMultiSelectHost,
	CWidgetFieldPatternSelectHost,
	CWidgetFieldTimePeriod
};

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		return $this
			->addField(
				(new CWidgetFieldMultiSelectGroup('groupids', _('Host groups')))
					->setMultiple(true)
					->setInType(CWidgetsData::DATA_TYPE_HOST_GROUP_ID)
					->acceptWidget()
			)
			->addField(
				(new CWidgetFieldMultiSelectHost('hostids', _('Hosts')))
					->setMultiple(true)
					->setInType(CWidgetsData::DATA_TYPE_HOST_ID)
					->acceptWidget()
			)
			->addField(
				new CWidgetFieldPatternSelectHost('host_patterns', _('Host patterns'))
			)
			->addField(
				new CWidgetFieldItems()
			)
			->addField(
				(new CWidgetFieldIntegerBox('grid_columns', _('Columns'), 1, 6))
					->setDefault(3)
			)
			->addField(
				(new CWidgetFieldIntegerBox('grid_rows', _('Rows'), 1, 6))
					->setDefault(2)
			)
			->addField(
				(new CWidgetFieldColor('line_color', _('Line color')))->setDefault('4a90d9')
			)
			->addField(
				(new CWidgetFieldIntegerBox('line_width', _('Line width'), 1, 10))->setDefault(2)
			)
			->addField(
				(new CWidgetFieldColor('fill_color', _('Fill color')))->setDefault('4a90d9')
			)
			->addField(
				(new CWidgetFieldIntegerBox('fill_opacity', _('Fill opacity (%)'), 0, 100))->setDefault(15)
			)
			->addField(
				(new CWidgetFieldColor('point_color', _('Point color')))->setDefault('4a90d9')
			)
			->addField(
				(new CWidgetFieldIntegerBox('point_size', _('Point size'), 1, 20))->setDefault(4)
			)
			->addField(
				(new CWidgetFieldColor('chart_color', _('Chart color')))->setDefault('b0c4de')
			)
			->addField(
				(new CWidgetFieldIntegerBox('chart_line_width', _('Chart line width'), 1, 5))->setDefault(1)
			)
			->addField(
				(new CWidgetFieldColor('title_color', _('Title color')))->setDefault('333333')
			)
			->addField(
				(new CWidgetFieldIntegerBox('title_size', _('Title size'), 8, 24))->setDefault(11)
			)
			->addField(
				(new CWidgetFieldTimePeriod('time_period', _('Time period')))
					->setDefault([
						CWidgetField::FOREIGN_REFERENCE_KEY => CWidgetField::createTypedReference(
							CWidgetField::REFERENCE_DASHBOARD, CWidgetsData::DATA_TYPE_TIME_PERIOD
						)
					])
					->setDefaultPeriod(['from' => 'now-1h', 'to' => 'now'])
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			);
	}
}
