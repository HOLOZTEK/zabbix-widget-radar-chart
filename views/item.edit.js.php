<?php declare(strict_types = 0); ?>

window.holoztek_rc_item_edit = new class {

	#overlay;
	#dialogue;
	#form;

	init({form_id, itemid, item_name}) {
		this.#overlay  = overlays_stack.getById('rc-item-edit-overlay');
		this.#dialogue = this.#overlay.$dialogue[0];
		this.#form     = document.getElementById(form_id);

		this.#form.removeAttribute('style');
		this.#overlay.recoverFocus();
		this.#form.addEventListener('submit', (e) => {
			e.preventDefault();
			this.submit();
		});

		// アイテム選択ボタン
		this.#dialogue.querySelector('[name="rc_item_select"]')
			.addEventListener('click', () => this.#selectItem());

		// popup.generic は element.value = v の直接代入でフィールドを更新するため
		// Object.defineProperty でセッターを横取りしてラベルを自動入力する
		const name_input  = document.getElementById('rc_item_name');
		const label_input = document.getElementById('rc_label');
		if (name_input && label_input) {
			const proto_desc = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
			Object.defineProperty(name_input, 'value', {
				configurable: true,
				get() { return proto_desc.get.call(this); },
				set(v) {
					proto_desc.set.call(this, v);
					if (!label_input.value.trim()) {
						label_input.value = v;
					}
				}
			});
		}
	}

	#selectItem() {
		const form_name = this.#form.getAttribute('name');

		PopUp('popup.generic', {
			srctbl:          'items',
			srcfld1:         'itemid',
			srcfld2:         'name',
			dstfrm:          form_name,
			dstfld1:         'rc_itemid',
			dstfld2:         'rc_item_name',
			real_hosts:      1,
			resolve_macros:  1,
			numeric:         1,
		}, {
			dialogueid:     'rc-item-select-popup',
			dialogue_class: 'modal-popup-generic',
		});
	}

	submit() {
		const curl   = new Curl(this.#form.getAttribute('action'));
		const fields = getFormFields(this.#form);

		// name フィールドは読み取り専用テキストボックス（id=rc_item_name）から取得
		const name_el = document.getElementById('rc_item_name');
		if (name_el) fields.name = name_el.value;

		this.#overlay.setLoading();

		fetch(curl.getUrl(), {
			method:  'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
			body:    urlEncodeData(fields),
		})
			.then(r => r.json())
			.then(response => {
				if ('error' in response) throw {error: response.error};

				overlayDialogueDestroy(this.#overlay.dialogueid);
				this.#dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
			})
			.catch(exception => {
				for (const el of this.#form.parentNode.children) {
					if (el.matches('.msg-good, .msg-bad, .msg-warning')) el.remove();
				}

				const title    = exception?.error?.title;
				const messages = exception?.error?.messages ?? [<?= json_encode(_holoztek_rc('Unexpected server error.'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>];

				this.#form.parentNode.insertBefore(makeMessageBox('bad', messages, title)[0], this.#form);
			})
			.finally(() => this.#overlay.unsetLoading());
	}
};
