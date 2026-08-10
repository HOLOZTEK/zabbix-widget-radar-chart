'use strict';

class CWidgetHoloztekRadarChart extends CWidget {

	#charts          = [];
	#data            = null;
	#page            = 0;
	#hide_empty      = false;
	#filtered_total  = 0;
	#effective_grid  = {columns: 1, rows: 1};

	onStop() {
		this.#disposeCharts();
	}

	onResize() {
		if (!this.#data || this.#charts.length === 0) return;

		requestAnimationFrame(() => {
			const cell_size = this.#computeCellSize();
			this.#charts.forEach(c => {
				if (c) c.resize(cell_size);
			});
		});
	}

	setContents(response) {
		super.setContents(response);

		if (response.error) {
			this._body.textContent = response.error;
			return;
		}

		if (!response.chart_data) {
			this._body.textContent = t('No data available.');
			return;
		}

		this.#data = response.chart_data;
		this.#page = 0;
		requestAnimationFrame(() => this.#render());
	}

	#disposeCharts() {
		this.#charts.forEach(c => { if (c) c.dispose(); });
		this.#charts = [];
	}

	#computeCellSize() {
		const {grid_columns, grid_rows} = this.#data;
		const total    = this.#filtered_total;
		const per_page = grid_columns * grid_rows;
		const pages    = Math.max(1, Math.ceil(total / per_page));
		const {columns, rows} = this.#effective_grid;
		const size    = this._getContentsSize();
		const warnings = this.#data.warnings?.length ? 20 : 0;
		const toolbar  = 30;
		const gap      = 4;
		const padding  = 4;
		const w = Math.max(40, Math.floor((size.width  - padding * 2 - gap * (columns - 1)) / columns));
		const h = Math.max(40, Math.floor((size.height - toolbar - warnings - padding * 2 - gap * (rows - 1)) / rows));
		return {width: w, height: h};
	}

	#render() {
		if (!this.#data) return;

		const {hosts, indicators, grid_columns, grid_rows} = this.#data;

		// #hide_empty が true のとき、指定アイテムが「すべて欠損」のホストを除外する
		const total_items = indicators.length;
		const filtered    = this.#hide_empty
			? hosts.filter(h => (h.no_data_indices?.length ?? 0) < total_items)
			: hosts;

		this.#filtered_total = filtered.length;

		const per_page = grid_columns * grid_rows;
		const pages    = Math.max(1, Math.ceil(this.#filtered_total / per_page));

		if (this.#page >= pages) this.#page = Math.max(0, pages - 1);

		const page_hosts = filtered.slice(this.#page * per_page, (this.#page + 1) * per_page);

		this.#disposeCharts();
		this._body.innerHTML = '';

		// ─── ツールバー（常時表示）──────────────────────────────────────
		const tb = document.createElement('div');
		tb.className = 'rc-toolbar';

		// 全欠損非表示トグルボタン
		const toggle = document.createElement('button');
		toggle.className = `btn-icon ${this.#hide_empty ? 'zi-eye-off rc-toggle-active' : 'zi-eye'}`;
		toggle.title = this.#hide_empty
			? t('All-missing hosts are hidden — click to show')
			: t('Showing all hosts — click to hide all-missing hosts');
		toggle.addEventListener('click', () => {
			this.#hide_empty = !this.#hide_empty;
			this.#page = 0;
			this.#render();
		});
		tb.appendChild(toggle);

		// ページング（複数ページのときのみ）
		if (pages > 1) {
			const paging = document.createElement('div');
			paging.className = 'rc-paging';

			const prev = document.createElement('button');
			prev.className = 'btn-icon zi-chevron-left';
			prev.title = t('Previous page');
			prev.disabled = this.#page === 0;
			prev.addEventListener('click', () => { this.#page--; this.#render(); });

			const info = document.createElement('span');
			info.textContent = `${this.#page + 1} / ${pages}  (${this.#filtered_total} hosts)`;

			const next = document.createElement('button');
			next.className = 'btn-icon zi-chevron-right';
			next.title = t('Next page');
			next.disabled = this.#page >= pages - 1;
			next.addEventListener('click', () => { this.#page++; this.#render(); });

			paging.append(prev, info, next);
			tb.appendChild(paging);
		}

		this._body.appendChild(tb);

		// ─── 集計データ不完全警告（History limit 到達時）──────────────
		const warnings = this.#data.warnings ?? [];
		if (warnings.length) {
			const warn = document.createElement('div');
			warn.className = 'rc-warning';
			warn.textContent = t('History data limit reached. Aggregated values may be incomplete.');
			this._body.appendChild(warn);
		}

		// ─── グリッド構築 ──────────────────────────────────────────────
		const count    = page_hosts.length;
		const eff_cols = count <= 1 ? 1 : Math.min(count, grid_columns);
		const eff_rows = count <= 1 ? 1 : Math.ceil(count / eff_cols);
		this.#effective_grid = {columns: eff_cols, rows: eff_rows};

		const grid = document.createElement('div');
		grid.className = 'rc-grid';
		grid.style.gridTemplateColumns = `repeat(${eff_cols}, 1fr)`;
		grid.style.gridTemplateRows    = `repeat(${eff_rows}, 1fr)`;
		this._body.appendChild(grid);

		const cell_size = this.#computeCellSize();
		const style     = this.#data.style ?? {};

		page_hosts.forEach((host) => {
			const cell = document.createElement('div');
			cell.className = 'rc-cell';
			grid.appendChild(cell);

			const chart_div = document.createElement('div');
			chart_div.style.cssText = 'width:100%;height:100%;';
			cell.appendChild(chart_div);

			const chart = echarts.init(chart_div, null, cell_size);
			this.#charts.push(chart);

			// ─── 最近傍軸トラッキング ──────────────────────────────────
			// ECharts レーダーチャートは tooltip trigger:'item' が系列全体で発火するため、
			// マウス座標からレーダー中心への角度で最近傍軸インデックスを特定する。
			//
			// 以前は中心座標(cx,cy)・軸角度ともに自前で推測していたが、コンテナのアスペクト比や
			// タイトル有無によって ECharts が実際に描画する中心と一致しない場合があり、軸の取り違え
			// （ツールチップの値が別の軸のものになる）が発生していた。ECharts の RadarCoordinateSystem
			// が内部で保持する実際の cx/cy と各軸の angle（radar.coordinateSystem.getIndicatorAxes()）
			// をそのまま使い、pointToData() と同じ角度規約（画面Y反転）で最近傍軸を求めることで、
			// 描画結果と完全に一致させる。
			let hovered_axis = -1;

			chart.getZr().on('mousemove', (e) => {
				const coord = chart.getModel().getComponent('radar')?.coordinateSystem;
				const axes  = coord?.getIndicatorAxes?.() ?? [];
				if (!coord || axes.length === 0) { hovered_axis = -1; return; }

				const dx = e.offsetX - coord.cx;
				const dy = e.offsetY - coord.cy;
				if (dx === 0 && dy === 0) { hovered_axis = -1; return; }

				const angle = Math.atan2(-dy, dx);
				let minDist = Infinity;
				let nearest = -1;
				axes.forEach((axis, i) => {
					let d = Math.abs(angle - axis.angle);
					if (d > Math.PI) d = 2 * Math.PI - d;
					if (d < minDist) { minDist = d; nearest = i; }
				});
				hovered_axis = nearest;
			});

			chart.getZr().on('mouseout', () => { hovered_axis = -1; });

			chart.setOption(this.#buildOption(host, indicators, style, cell_size.width, () => hovered_axis));
		});
	}

	// HTML特殊文字をエスケープする（tooltip に埋め込む値に適用）
	static #escapeHtml(value) {
		return String(value).replace(/[&<>"']/g, ch => ({
			'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
		}[ch]));
	}

	// Unix タイムスタンプ → 'YYYY/MM/DD HH:mm:ss' 形式
	static #formatTime(unix_ts) {
		const d   = new Date(unix_ts * 1000);
		const pad = n => String(n).padStart(2, '0');
		return `${d.getFullYear()}/${pad(d.getMonth() + 1)}/${pad(d.getDate())} `
			 + `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
	}

	static #wrapLabel(text, maxChars) {
		if (text.length <= maxChars) return text;
		const words = text.split(' ');
		if (words.length > 1) {
			const lines = [];
			let line = '';
			for (const word of words) {
				const candidate = line ? line + ' ' + word : word;
				if (line && candidate.length > maxChars) {
					lines.push(line);
					line = word;
				} else {
					line = candidate;
				}
			}
			if (line) lines.push(line);
			return lines.join('\n');
		}
		const lines = [];
		for (let i = 0; i < text.length; i += maxChars) {
			lines.push(text.substring(i, i + maxChars));
		}
		return lines.join('\n');
	}

	#buildOption(host, indicators, style, cell_width, getAxis) {
		const line_color       = '#' + (style.line_color       || '4a90d9');
		const line_width       = style.line_width       ?? 2;
		const fill_color       = '#' + (style.fill_color       || '4a90d9');
		const fill_opacity     = (style.fill_opacity    ?? 15) / 100;
		const point_color      = '#' + (style.point_color      || '4a90d9');
		const point_size       = (style.point_size      ?? 4) * 2;
		const chart_color      = '#' + (style.chart_color      || 'b0c4de');
		const chart_line_width = style.chart_line_width ?? 1;
		const title_color      = '#' + (style.title_color      || '333333');
		const title_size       = style.title_size       ?? 11;

		const tip_fs  = Math.max(10, Math.min(13, Math.floor((cell_width ?? 200) / 16)));
		const time_fs = Math.max(9, tip_fs - 1);

		const {period_from, period_to} = this.#data;

		return {
			backgroundColor: 'transparent',
			title: {
				text:      host.name,
				textStyle: {fontSize: title_size, color: title_color, fontWeight: 'normal'},
				left:      'center',
				top:       2,
			},
			tooltip: {
				trigger:   'item',
				confine:   true,
				padding:   [6, 8],
				textStyle: {fontSize: tip_fs},
				formatter: () => {
					const i = getAxis?.() ?? -1;
					if (i < 0 || i >= indicators.length) return '';

					const ind   = indicators[i];
					const val   = host.values?.[i] ?? 0;
					const clock = host.clocks?.[i]  ?? 0;
					// tooltip は HTML で描画されるためエスケープ必須
					const unit  = CWidgetHoloztekRadarChart.#escapeHtml(ind.units ?? '');
					const val_e = CWidgetHoloztekRadarChart.#escapeHtml(val);

					const val_str = unit ? `${val_e} ${unit}` : `${val_e}`;

					let time_str = '';
					if (clock > 0) {
						time_str = CWidgetHoloztekRadarChart.#escapeHtml(CWidgetHoloztekRadarChart.#formatTime(clock));
					} else if (period_from && period_to) {
						time_str = CWidgetHoloztekRadarChart.#escapeHtml(
							CWidgetHoloztekRadarChart.#formatTime(period_from)
							+ ' 〜 '
							+ CWidgetHoloztekRadarChart.#formatTime(period_to)
						);
					}

					const time_html = time_str
						? `<br><span style="color:#888;font-size:${time_fs}px">${time_str}</span>`
						: '';

					return `${val_str}${time_html}`;
				},
			},
			radar: {
				indicator: indicators.map((ind, i) => ({
					name:  CWidgetHoloztekRadarChart.#wrapLabel(ind.label, 8),
					max:   ind.max_val,
					color: (host.no_data_indices ?? []).includes(i) ? '#e53935' : undefined,
				})),
				radius:    '58%',
				center:    ['50%', '56%'],
				nameGap:   4,
				axisName: {
					fontSize: 10,
					color:    '#555',
				},
				splitLine: {lineStyle: {color: chart_color, width: chart_line_width}},
				splitArea: {show: false},
				axisLine:  {lineStyle: {color: chart_color, width: chart_line_width}},
			},
			series: [{
				type: 'radar',
				data: [{
					value:      host.values,
					name:       host.name,
					areaStyle:  {color: fill_color, opacity: fill_opacity},
					lineStyle:  {width: line_width, color: line_color},
					itemStyle:  {color: point_color},
					symbolSize: point_size,
				}],
			}],
		};
	}
}
