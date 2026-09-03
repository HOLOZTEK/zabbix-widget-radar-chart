#!/usr/bin/env node
/*
 * 描画位置正規化の回帰確認スクリプト（依存なし・Node 標準のみ）。
 *
 * assets/js/class.widget.js の #buildOption() 内 plot_values 生成ロジックと、
 * actions/WidgetView.php の indicator（min_val / max_val / direction）補完ロジックを
 * 再現し、想定値と一致するか検証する。
 *
 *   実行: node scripts/test-normalization.js
 *
 * class.widget.js の正規化式を変更した場合は、本スクリプトの normalize() も
 * 合わせて更新すること。
 */

'use strict';

// ─── class.widget.js #buildOption() の plot_values 生成と同一ロジック ───
// host.values の 1 要素ぶん（実値 v・軸 index i）を 0.0〜1.0 の描画位置へ変換する。
function normalize(v, indicator, is_no_data) {
	if (is_no_data) {
		return 0;
	}

	const ind  = indicator ?? {};
	const min  = ind.min_val ?? 0;
	const max  = ind.max_val ?? 1;
	const span = max - min;

	let n = span > 0 ? (v - min) / span : 0;
	n = Math.max(0, Math.min(1, n));

	if (ind.direction === 1) {
		n = 1 - n;
	}

	return n;
}

// ─── actions/WidgetView.php の $indicators[] 補完と同一ロジック ───
// CWidgetFieldItems 経由の 1 アイテム設定（$ic）を描画側 indicator 形へ変換する。
//   min_val: (float) ($ic['min_val'] ?? 0)
//   max_val: (float) ($ic['max_val'] ?? 100)   ← v1.0.7 差し替えで ?: から ?? へ修正
//   direction: (int) ($ic['direction'] ?? 0)
function buildIndicator(ic) {
	const toFloat = (x) => {
		// PHP の (float) キャスト相当（先頭の数値部分を採用、非数値は 0）
		const f = parseFloat(x);
		return Number.isNaN(f) ? 0 : f;
	};

	return {
		min_val:   toFloat(ic.min_val ?? 0),
		max_val:   toFloat(ic.max_val ?? 100),
		direction: parseInt(ic.direction ?? 0, 10) || 0
	};
}

let failures = 0;

function approx(a, b) {
	return Math.abs(a - b) < 1e-9;
}

function check(name, actual, expected) {
	const ok = approx(actual, expected);
	if (!ok) {
		failures++;
	}
	const mark = ok ? 'PASS' : 'FAIL';
	console.log(`  [${mark}] ${name}: got ${actual}, expected ${expected}`);
}

// ══════════════════════════════════════════════════════════════════════
// 1. 描画位置正規化（class.widget.js #buildOption 相当）
// ══════════════════════════════════════════════════════════════════════
console.log('normalize() — 描画位置の正規化・クリップ・反転');

// --- 基本レンジ min=0 / max=100 ---
const r0 = {min_val: 0, max_val: 100, direction: 0};
const r0r = {min_val: 0, max_val: 100, direction: 1};

check('min の値 → 中心(0)',            normalize(0,   r0,  false), 0);
check('min の値 → 反転で外周(1)',      normalize(0,   r0r, false), 1);
check('max の値 → 外周(1)',            normalize(100, r0,  false), 1);
check('max の値 → 反転で中心(0)',      normalize(100, r0r, false), 0);
check('中間値 50 → 0.5',              normalize(50,  r0,  false), 0.5);
check('中間値 50 → 反転で 0.5',        normalize(50,  r0r, false), 0.5);
check('min 未満 -10 → 0 へクリップ',   normalize(-10, r0,  false), 0);
check('min 未満 -10 → 反転で 1',       normalize(-10, r0r, false), 1);
check('max 超過 150 → 1 へクリップ',   normalize(150, r0,  false), 1);
check('max 超過 150 → 反転で 0',       normalize(150, r0r, false), 0);

// --- 負数レンジ min=-100 / max=-30 ---
const n0  = {min_val: -100, max_val: -30, direction: 0};
const n0r = {min_val: -100, max_val: -30, direction: 1};

check('負数レンジ 端 -100 → 0',        normalize(-100, n0,  false), 0);
check('負数レンジ 端 -30 → 1',         normalize(-30,  n0,  false), 1);
check('負数レンジ 中点 -65 → 0.5',      normalize(-65,  n0,  false), 0.5);
check('負数レンジ 中点 -65 → 反転 0.5', normalize(-65,  n0r, false), 0.5);

// --- 追加回帰: min=-50 / max=0 / value=-25（max がちょうど 0）---
const z0  = {min_val: -50, max_val: 0, direction: 0};
const z0r = {min_val: -50, max_val: 0, direction: 1};

check('min=-50 max=0 value=-25 → 通常 0.5', normalize(-25, z0,  false), 0.5);
check('min=-50 max=0 value=-25 → 反転 0.5', normalize(-25, z0r, false), 0.5);
check('min=-50 max=0 value=-50 → 通常 0',   normalize(-50, z0,  false), 0);
check('min=-50 max=0 value=0   → 通常 1',   normalize(0,   z0,  false), 1);

// --- span <= 0（min >= max）: 保存はモーダルで拒否するが描画側の安全弁 ---
const bad = {min_val: 100, max_val: 100, direction: 0};
check('span=0 → 0',                    normalize(50, bad, false), 0);

// --- データなし軸は方向に関わらず中心(0) ---
check('データなし → 通常 0', normalize(999, r0,  true), 0);
check('データなし → 反転でも 0', normalize(999, r0r, true), 0);

// ══════════════════════════════════════════════════════════════════════
// 2. indicator 補完（WidgetView.php ?: → ?? 差し替えの回帰確認）
// ══════════════════════════════════════════════════════════════════════
console.log('\nbuildIndicator() — WidgetView.php の min_val / max_val 補完');

// max_val = '0'（文字列ゼロ）… ?: だと 100 に化けていた。?? では 0.0 のまま。
check('max_val="0" → 0.0 のまま（100 に化けない）', buildIndicator({min_val: '-50', max_val: '0'}).max_val, 0);
check('min_val="-50" → -50.0',                      buildIndicator({min_val: '-50', max_val: '0'}).min_val, -50);

// 旧設定で max_val キー自体が無い → 100 へ補完される
check('max_val 未設定 → 100 へ補完',   buildIndicator({min_val: '0'}).max_val, 100);
check('min_val 未設定 → 0 へ補完',     buildIndicator({max_val: '100'}).min_val, 0);

// 通常の値はそのまま
check('max_val="100" → 100.0',        buildIndicator({min_val: '0', max_val: '100'}).max_val, 100);
check('max_val="-30" → -30.0',        buildIndicator({min_val: '-100', max_val: '-30'}).max_val, -30);

// ══════════════════════════════════════════════════════════════════════
// 3. 補完 → 正規化の通し確認（max=0 の負数レンジがフォールバックしないこと）
// ══════════════════════════════════════════════════════════════════════
console.log('\n通し確認 — buildIndicator → normalize');

const ind_z = buildIndicator({min_val: '-50', max_val: '0', direction: '0'});
check('min=-50/max=0 設定, value=-25 → 0.5（100 フォールバックなし）',
	normalize(-25, ind_z, false), 0.5);

const ind_z_rev = buildIndicator({min_val: '-50', max_val: '0', direction: '1'});
check('min=-50/max=0 設定, value=-25, 反転 → 0.5',
	normalize(-25, ind_z_rev, false), 0.5);

// もし ?: のままなら max=100 となり (-25-(-50))/(100-(-50)) = 25/150 ≒ 0.1667 になる（退行検知）
const ind_z_buggy = {min_val: -50, max_val: 100, direction: 0};
check('（退行例示）?: のままなら 0.5 にならない',
	approx(normalize(-25, ind_z_buggy, false), 0.5) ? 1 : 0, 0);

// ══════════════════════════════════════════════════════════════════════
console.log('');
if (failures > 0) {
	console.error(`${failures} 件の検証に失敗しました。`);
	process.exit(1);
}
console.log('すべての検証に合格しました。');
