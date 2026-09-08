# zabbix-widget-radar-chart

[English](README.md) | 日本語

## 概要

Radar Chart は、複数ホストの数値アイテムをレーダーチャートで比較する Zabbix ダッシュボードウィジェットです。3〜8 個のアイテムを軸として表示し、Tree Navigator、Topology Navigator、または互換ウィジェットからホストグループ・ホストのコンテキストを受け取れます。

<table>
  <tr>
    <td align="center" valign="top" width="50%">
      <strong>単一ホストの表示</strong><br>
      <a href="screenshots/radar-chart-dashboard-single.png" target="_blank"><img src="screenshots/radar-chart-dashboard-single.png" height="280" alt="単一ホストの Radar Chart" /></a>
    </td>
    <td align="center" valign="top" width="50%">
      <strong>複数ホストの表示</strong><br>
      <a href="screenshots/radar-chart-dashboard-multiple.png" target="_blank"><img src="screenshots/radar-chart-dashboard-multiple.png" height="280" alt="複数ホストを比較する Radar Chart" /></a>
    </td>
  </tr>
</table>

## Radar Chart を使う理由

CPU、メモリ、ストレージ、遅延などの値は、共通のスケールで比較するとホストごとの偏りや外れ値を把握しやすくなります。Radar Chart は設定した範囲に値を正規化し、複数ホストの状態をコンパクトに比較できます。

ツールチップでは収集した実値、単位、時刻を確認できるため、概況の比較と初期調査を同じダッシュボードで行えます。

## 機能

<table>
  <tr><th align="left" nowrap>機能</th><th align="left">説明</th></tr>
  <tr><td nowrap>複数ホストのグリッド表示</td><td>最大 6 × 6 のグリッドにホストごとのレーダーチャートを表示します。ページを切り替えてもセルサイズは固定です。</td></tr>
  <tr><td nowrap>ホスト指定</td><td>ホストグループ、個別ホスト、ワイルドカードを使ったホストパターンを指定できます。ホストグループとパターンを併用した場合、パターンはそのグループ内に限定されます。</td></tr>
  <tr><td nowrap>ウィジェット連携</td><td>Tree Navigator、Topology Navigator、または互換ウィジェットからホストグループ・ホストを受け取れます。</td></tr>
  <tr><td nowrap>アイテムごとの集計</td><td>最新値、最大値、最小値、平均値を選択できます。最大値・最小値・平均値は 2 時間未満では History を使い、それより長い期間では Trend を優先し、必要に応じて History にフォールバックします。</td></tr>
  <tr><td nowrap>アイテムごとのスケール</td><td>最小値・最大値で値を正規化します。負数レンジと軸方向の反転に対応しています。</td></tr>
  <tr><td nowrap>運用時のフィードバック</td><td>詳細ツールチップ、データ欠損表示、全欠損ホストの非表示、ページング、集計警告を提供します。</td></tr>
</table>

<table>
  <tr>
    <td align="center" valign="top" width="50%">
      <strong>値の詳細</strong><br>
      <a href="screenshots/radar-chart-tooltip.png" target="_blank"><img src="screenshots/radar-chart-tooltip.png" width="300" alt="Radar Chart のツールチップ" /></a>
    </td>
    <td align="center" valign="top" width="50%">
      <strong>データ欠損とページング</strong><br>
      <a href="screenshots/radar-chart-missing-data-pagination.png" target="_blank"><img src="screenshots/radar-chart-missing-data-pagination.png" width="300" alt="データ欠損とページング" /></a>
    </td>
  </tr>
</table>

## 設定項目

<table>
  <tr><th align="left" nowrap>項目</th><th align="left">説明</th></tr>
  <tr><td nowrap>ホストグループ</td><td>対象ホストグループを直接選択するか、他のウィジェットから受け取ります。</td></tr>
  <tr><td nowrap>ホスト</td><td>対象ホストを直接選択するか、他のウィジェットから受け取ります。</td></tr>
  <tr><td nowrap>ホストパターン</td><td>ワイルドカードを使ってホスト名を絞り込みます。ホストグループも指定した場合は、そのグループ内が対象です。</td></tr>
  <tr><td nowrap>アイテム</td><td>数値アイテムを 3〜8 個、レーダーチャートの軸として追加します。</td></tr>
  <tr><td nowrap>└ アイテム</td><td>軸に使用する数値アイテムを選択します。</td></tr>
  <tr><td nowrap>└ ラベル</td><td>軸のラベルを設定します。未指定時はアイテム名を表示します。</td></tr>
  <tr><td nowrap>└ 範囲（最小値 / 最大値）</td><td>正規化に使う範囲を設定します。最小値は最大値より小さくする必要があります。</td></tr>
  <tr><td nowrap>└ 軸方向</td><td>通常または反転を選択します。</td></tr>
  <tr><td nowrap>└ 集計方法</td><td>最新値、最大値、最小値、平均値を選択します。</td></tr>
  <tr><td nowrap>グリッド</td><td>列数と行数をそれぞれ 1〜6 の範囲で設定します。</td></tr>
  <tr><td nowrap>スタイル › 線</td><td>線の色と太さ（1〜10）を設定します。</td></tr>
  <tr><td nowrap>スタイル › 点</td><td>点の色と大きさ（1〜20）を設定します。</td></tr>
  <tr><td nowrap>スタイル › 塗りつぶし</td><td>塗りつぶしの色と不透明度（0〜100%）を設定します。</td></tr>
  <tr><td nowrap>スタイル › チャート</td><td>チャートの色と線の太さ（1〜5）を設定します。</td></tr>
  <tr><td nowrap>スタイル › タイトル</td><td>タイトルの色とサイズ（8〜24）を設定します。</td></tr>
  <tr><td nowrap>データ上限 › History 行数</td><td>History の取得件数上限を 1,000〜1,000,000（既定 50,000）の範囲で設定します。</td></tr>
  <tr><td nowrap>データ上限 › Latest 取得警告</td><td>Latest の取得回数に対する警告しきい値を 10〜100,000（既定 500）の範囲で設定します。</td></tr>
  <tr><td nowrap>期間</td><td>必須の集計期間を設定します。既定ではダッシュボードの期間を使用します。</td></tr>

ウィジェット設定では、対象範囲、アイテム、レイアウト、表示スタイルを選択します。

<a href="screenshots/radar-chart-settings-ja.png" target="_blank"><img src="screenshots/radar-chart-settings-ja.png" width="620" alt="Radar Chart ウィジェット設定（日本語）" /></a>

アイテムごとに最小値・最大値、軸方向、集計方法を設定します。最小値は最大値より小さくする必要があります。軸を反転しても変わるのはチャート上の位置だけで、ツールチップには取得した実値を表示します。

<a href="screenshots/radar-chart-item-settings-ja.png" target="_blank"><img src="screenshots/radar-chart-item-settings-ja.png" width="620" alt="Radar Chart アイテム設定（日本語）" /></a>

## 動作

ホストグループまたはホストを選択するナビゲーターと Radar Chart を同じダッシュボードページに追加し、Radar Chart の Host groups および Hosts 入力を連携元のウィジェットに接続します。ナビゲーターでグループまたはホストを選択すると、チャートの対象範囲が更新されます。

値はダッシュボードの期間に対して計算されます。Latest はその期間内で最も新しい History 値を使います。Max、Min、Avg は短い期間では History を使い、長い期間では Trend を優先します。値を取得できない軸は欠損として表示され、すべての値が欠損のホストは非表示にできます。ホスト数がグリッドのセル数を超える場合はページングを表示します。

<a href="screenshots/radar-chart-navigator-integration.png" target="_blank"><img src="screenshots/radar-chart-navigator-integration.png" width="750" alt="ホストグループナビゲーターと Radar Chart の連携" /></a>

## 動作要件

- Zabbix 7.0 以上
- PHP 8.1 以上
- RPM パッケージは Rocky Linux 9 または 10 を対象とします。互換する PHP と Zabbix パッケージがあれば、他の RHEL 互換ディストリビューションでも動作が見込まれます
- Zabbix フロントエンドがサポートするブラウザ

## インストール

### RPM パッケージ

```bash
dnf install ./zabbix-widget-radar-chart-<version>.noarch.rpm
```

RPM は PHP または php-common と php-fpm の 8.1 以上を必要とします。

### DEB パッケージ

```bash
apt install ./zabbix-widget-radar-chart_<version>_all.deb
```

Debian パッケージは PHP 8.1 以上を必要とします。

### ソースからのインストール

モジュールをフロントエンドのモジュールディレクトリへコピーし、**管理 → モジュール** でスキャンして有効化します。

```bash
# Zabbix 7.x
cp -r zabbix-widget-radar-chart /usr/share/zabbix/modules/holoztek_radar_chart

# Zabbix 8.x
cp -r zabbix-widget-radar-chart /usr/share/zabbix/ui/modules/holoztek_radar_chart
```

v1.0.0 以前から更新する場合は、モジュール ID が `radar-chart` から `holoztek_radar_chart` に変わります。所有元を確認してから旧モジュールを停止し、新しいモジュールを再スキャン・有効化したうえで、既存のダッシュボードウィジェット type を新 ID に更新してください。既存の設定フィールドと参照は保持されます。

## ドキュメント

- [パッケージ変更履歴](debian/changelog)
- [バージョン整合性チェック](scripts/check-version-consistency.sh)
- [正規化テスト](scripts/test-normalization.js)
- [サードパーティー通知](THIRD_PARTY_NOTICES.md)

## 構成

- 実行モジュール: リポジトリ直下、`actions/`、`assets/`、`includes/`、`locale/`、`views/`
- パッケージング: `packaging/rpm/`、`debian/`
- スクリーンショット: `screenshots/`
- チェック・テスト: `scripts/`

## メンテナ

HOLOZTEK が開発・保守しています。

## ライセンス

本プロジェクトは [MIT License](LICENSE) の下で提供されています。Apache License 2.0 の Apache ECharts を同梱しています。詳細は [NOTICE](NOTICE) と [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) を参照してください。
