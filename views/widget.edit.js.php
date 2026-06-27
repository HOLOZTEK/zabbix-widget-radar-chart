<?php declare(strict_types = 0); ?>

window.widget_radar_chart_form = new class {

	#form;
	#list_items;
	#item_index;

	init() {
		this.#form       = document.getElementById('widget-dialogue-form');
		this.#list_items = document.getElementById('list_items');

		for (const cp of this.#form.querySelectorAll('.color-picker input')) {
			$(cp).colorpicker({appendTo: '.overlay-dialogue-body', use_default: false});
		}

		// host_patterns フィールドで * や Server* 等を直接タグとして入力できるよう addNew を有効化する。
		// zbx_formatDomId('host_patterns[]') = 'host_patterns_' なので、セレクターは #host_patterns_。
		// addNew: true のとき新規タグの hidden input 名が host_patterns[][new] になるため、
		// MutationObserver で即座に host_patterns[] へ正規化する。
		const $hp = jQuery('#host_patterns_');
		if ($hp.length) {
			$hp.multiSelect('modify', {addNew: true});

			new MutationObserver(mutations => {
				for (const m of mutations) {
					for (const node of m.addedNodes) {
						if (node.nodeType !== 1) continue;
						for (const inp of node.querySelectorAll('[name="host_patterns[][new]"]')) {
							inp.name = 'host_patterns[]';
						}
					}
				}
			}).observe(this.#form, {childList: true, subtree: true});
		}

		if (!this.#list_items) return;

		new CSortable(this.#list_items.querySelector('tbody'), {
			selector_handle: 'div.<?= ZBX_STYLE_DRAG_ICON ?>',
			freeze_end: 1
		}).on(CSortable.EVENT_SORT, () => {
			this.#renumber();
			ZABBIX.Dashboard.reloadWidgetProperties();
		});

		this.#list_items.addEventListener('click', (e) => this.#processAction(e));
		this.#updateButtons();
	}

	// sort_order[items][] を持つ行だけをアイテム行として返す（Addボタン行を除外）
	#getItemRows() {
		return [...this.#list_items.querySelectorAll('tbody tr')]
			.filter(row => row.querySelector('[name="sort_order[items][]"]'));
	}

	#processAction(e) {
		const target = e.target;
		let popup;

		switch (target.getAttribute('name')) {
			case 'add':
				this.#item_index = this.#getItemRows().length;

				popup = PopUp(
					'widget.radar-chart.item.edit',
					{},
					{dialogueid: 'rc-item-edit-overlay', dialogue_class: 'modal-popup-generic'}
				).$dialogue[0];

				popup.addEventListener('dialogue.submit', (e) => this.#updateItems(e));
				break;

			case 'edit':
				const fields = getFormFields(this.#form);
				this.#item_index = target.closest('tr')
					.querySelector('[name="sort_order[items][]"]').value;

				const item = fields.items?.[this.#item_index] ?? {};

				popup = PopUp(
					'widget.radar-chart.item.edit',
					{...item, edit: 1},
					{dialogueid: 'rc-item-edit-overlay', dialogue_class: 'modal-popup-generic'}
				).$dialogue[0];

				popup.addEventListener('dialogue.submit', (e) => this.#updateItems(e));
				break;

			case 'remove':
				target.closest('tr').remove();
				this.#renumber();
				this.#updateButtons();
				ZABBIX.Dashboard.reloadWidgetProperties();
				break;
		}
	}

	#updateItems(e) {
		const data  = e.detail;
		const input = document.createElement('input');
		input.setAttribute('type', 'hidden');

		if (data.edit) {
			this.#form.querySelectorAll(`[name^="items[${this.#item_index}]["]`)
				.forEach(node => node.remove());
			delete data.edit;
		}

		for (const [key, value] of Object.entries(data)) {
			if (value === null) continue;
			input.setAttribute('name',  `items[${this.#item_index}][${key}]`);
			input.setAttribute('value', value);
			this.#form.appendChild(input.cloneNode());
		}

		this.#updateButtons();
		ZABBIX.Dashboard.reloadWidgetProperties();
	}

	#renumber() {
		let idx = 0;
		for (const row of this.#list_items.querySelectorAll('tbody tr')) {
			const sort_input = row.querySelector('[name="sort_order[items][]"]');
			if (!sort_input) continue;
			sort_input.value = idx;
			for (const input of row.querySelectorAll('[name^="items["]')) {
				input.name = input.name.replace(/^items\[\d+\]/, `items[${idx}]`);
			}
			idx++;
		}
	}

	#updateButtons() {
		const rows = this.#getItemRows().length;
		const min  = <?= \Modules\RadarChart\Includes\CWidgetFieldItems::MIN_ITEMS ?>;
		const max  = <?= \Modules\RadarChart\Includes\CWidgetFieldItems::MAX_ITEMS ?>;

		this.#list_items.querySelectorAll('[name="remove"]').forEach(btn => {
			btn.disabled = rows <= min;
		});

		const add_btn = this.#list_items.querySelector('[name="add"]');
		if (add_btn) add_btn.disabled = rows >= max;
	}
};
