# Radar Chart Widget for Zabbix

複数のホストのアイテム値をレーダーチャートで並べて可視化するウィジェットです。ホストグループナビゲーターと連携し、任意のアイテムを3〜8つ軸として設定できます。

## 機能

- **複数ホストの並列表示**: グリッドレイアウト（最大 6×6）で複数ホストを一覧表示
- **柔軟なホスト指定**: ホストグループ / 個別ホスト / ホストパターン（ワイルドカード対応）の3方式
- **ナビゲーター連動**: Tree Navigator / Topology Navigator などのホストグループナビゲーターとリンク可能
- **集計方法選択**: 各アイテムごとに最新値 / 最大値 / 最小値 / 平均値を選択
  - 最新値はダッシュボード指定期間内の最新値（現在値ではない）
  - 最大値・最小値・平均値は、指定期間が2時間未満はHistory、2時間以上はTrendを優先して集計（Trendデータが無いアイテム/ホストはHistoryにフォールバック）
- **数値アイテムのみ対応**: float および unsigned integer 型のアイテムのみ選択可能
- **データなし表示**: 値が取得できないアイテムの軸ラベルを赤色で表示
- **ホバーツールチップ**: 頂点にカーソルを合わせると値・単位・収集時刻（または集計期間）を表示
- **全欠損ホスト非表示**: すべてのアイテムにデータがないホストをトグルボタンで非表示にする
- **ページング**: 表示ホスト数がグリッドを超えた場合に前/次ページボタンで切り替え
- **集計警告**: History集計で件数上限（既定50,000件）に達した場合、不完全な集計値である旨を警告表示。上限値と、最新値取得の警告閾値は設定画面で変更可能
- **スタイルカスタマイズ**: 線・点・面・チャート・タイトルの色やサイズを個別設定
- 日本語 / English ロケール対応

## 動作環境

| 項目 | 要件 |
|------|------|
| Zabbix | 7.0 以上 |
| PHP | **8.1 以上**（8.0 系では PHP エンジンの既知バグにより SIGSEGV が発生します） |
| OS | Rocky Linux 9 / Rocky Linux 10（その他 RHEL 系 Linux） |

> **注意**: PHP 8.0.x はダッシュボード描画時に複数ウィジェットのフォーム処理が並行実行される際、PHP エンジン内で SIGSEGV（セグメンテーション違反）が発生し、PHP-FPM ワーカーがクラッシュすることが確認されています。PHP 8.1 以上へのアップグレードで解消されます（実証済みの再現条件は 8.0.x のみ）。

## 制約事項

- **数値アイテムのみ**: float（小数）および unsigned integer（符号なし整数）型のアイテムのみ使用できます。文字列・テキスト・ログ型は選択不可です。
- **ホストグループとホストパターンの併用**: `Host groups` と `Host patterns` を両方指定した場合、ホストパターンの検索範囲がホストグループでスコープ限定されます（OR マージではありません）。
- **最新値は「現在値」ではない**: Latest（最新値）は lastvalue ではなく、ダッシュボードで指定した期間内の最新の History 値です。過去の期間を指定した場合、その期間の終端に最も近い値が表示されます。
- **Max/Min/Avg の集計元は期間により自動切替**: 指定期間が **2時間未満** の場合は History から、**2時間以上** の場合は Trend（`value_max`/`value_min`/`value_avg`+`num` による加重平均）から集計します。Trend にデータが存在しないアイテム・ホストの組み合わせは History に自動フォールバックします。Trend 使用時は Zabbix の trend 粒度（通常1時間単位）に基づく値になるため、瞬間的な変動は平滑化されます。
- **大量データ集計の制限**: History 集計（2時間未満の期間、または Trend フォールバック時）では、取得件数が上限（既定50,000件、設定画面の「Data limits > History rows」で 1,000〜1,000,000 の範囲で変更可能）に達すると集計値が不完全になる場合があります。長期間・大量ホストを対象とした集計には制約があります。警告バナーが表示された場合は、集計期間を短くするか対象ホスト数を減らす、または上限値を引き上げてください（Trend 集計自体はデータ量が少ないためこの上限には通常到達しません。上限を上げると API 応答とメモリ使用量が増えます）。
- **Latest（最新値）と対象ホスト数**: Latest は itemid ごとに期間内最新値を個別取得（`History.get` を `limit=1` で1回ずつ）するため、正確性と引き換えに API 呼び出し回数が「対象ホスト数 × Latest 集計を指定した軸数」に比例して増えます。ホストグループや `*` パターンで数百〜数千ホストを対象にすると、ダッシュボード描画が遅くなる場合があります。目安値（既定500、設定画面の「Data limits > Latest fetch warning」で 10〜100,000 の範囲で変更可能）を超えると警告バナーを表示します。対象ホストを絞り込むか、対象アイテムの集計方法を Max/Min/Avg（value_type 単位でバッチ取得）へ変更すると高速になります。

## インストール

### RPM パッケージ（推奨）

```bash
rpm -ivh zabbix-widget-radar-chart-<version>.noarch.rpm
```

RPM は `php` と `php-fpm`（いずれも 8.1 以上）を要求します（RHEL 系は Zabbix フロントエンドを PHP-FPM で運用するため）。Debian パッケージ（`.deb`）は `php (>= 2:8.1~)` のみを要求します（対象の Ubuntu 環境は Apache mod_php 運用で php-fpm を使用しないため）。

インストール後、Zabbix フロントエンドの **管理 → モジュール** からモジュールを有効化してください。

### 手動インストール

Zabbix のバージョンによってモジュール配置ディレクトリが異なります。

```bash
# Zabbix 7.x
cp -r zabbix-widget-radar-chart /usr/share/zabbix/modules/holoztek_radar_chart

# Zabbix 8.x
cp -r zabbix-widget-radar-chart /usr/share/zabbix/ui/modules/holoztek_radar_chart
```

## RPM ビルド手順

```bash
# ソースを SOURCES に配置
mkdir -p ~/rpmbuild/SOURCES ~/rpmbuild/SPECS
cp -r zabbix-widget-radar-chart ~/rpmbuild/SOURCES/
cp zabbix-widget-radar-chart/packaging/rpm/zabbix-widget-radar-chart.spec ~/rpmbuild/SPECS/

# .mo ファイルのコンパイル（msgfmt が必要）
msgfmt ~/rpmbuild/SOURCES/zabbix-widget-radar-chart/locale/ja_JP/LC_MESSAGES/holoztek-radar-chart.po \
  -o ~/rpmbuild/SOURCES/zabbix-widget-radar-chart/locale/ja_JP/LC_MESSAGES/holoztek-radar-chart.mo
msgfmt ~/rpmbuild/SOURCES/zabbix-widget-radar-chart/locale/en_US/LC_MESSAGES/holoztek-radar-chart.po \
  -o ~/rpmbuild/SOURCES/zabbix-widget-radar-chart/locale/en_US/LC_MESSAGES/holoztek-radar-chart.mo

# RPM ビルド
rpmbuild -bb ~/rpmbuild/SPECS/zabbix-widget-radar-chart.spec
```

## 設定項目

| 項目 | 説明 |
|------|------|
| Host groups | 対象ホストグループ（ナビゲーター連動または直接指定） |
| Hosts | 対象ホスト（直接指定） |
| Host patterns | ホスト名パターン（`*` でワイルドカード指定）。Host groups 指定時はそのグループ内に限定 |
| Items | 表示アイテム（3〜8個、数値型のみ）。各アイテムにラベル・最大値・集計方法を設定 |
| Grid | グリッド列数 × 行数（1〜6） |
| Style | 線・点・面・チャート・タイトルの色とサイズ |
| Data limits > History rows | Max/Min/Avg を History から集計する際の取得件数上限（1,000〜1,000,000、既定50,000）。この件数に達すると集計値が不完全な旨の警告を表示 |
| Data limits > Latest fetch warning | Latest 個別取得回数（対象ホスト数 × Latest 軸数）がこの値を超えると描画遅延の警告を表示（10〜100,000、既定500） |
| Time period | 集計期間（ダッシュボードの時間軸と連動） |

## 旧ID（radar-chart）からのアップグレード手順

v1.0.1 で、Zabbix モジュールの内部識別子（`manifest.json` の `id`）が
`radar-chart` から `holoztek_radar_chart` へ変更されました。この変更は
他ベンダーのモジュールとの名前衝突を避けるためのもので、v1.0.0 以前から
アップグレードする場合は以下の手順が必要です（v1.0.1 以降からのアップグレード
では不要です）。

1. **パッケージの更新**（RPM/DEB を新バージョンで上書きインストール、または
   ファイルを直接配置）。v1.0.2 以降、モジュール配置ディレクトリ名も
   `radar-chart` から `holoztek_radar_chart` へ変更されています
   （他ベンダーのモジュールとのファイルシステム上の衝突を避けるため）。
   RPM/DEB パッケージはインストール時に旧ディレクトリ（`radar-chart`）の
   `manifest.json` を確認し、`author` が `HOLOZTEK` と明記されているか、
   `id` が既に新形式（`holoztek_radar_chart`）である場合に限り、本パッケージ
   由来と判断して自動的に削除します。v1.0.0 時点の `manifest.json` には
   `author` が記載されていないため、v1.0.0 から直接アップグレードした場合は
   この条件に該当せず、旧ディレクトリは自動削除されずに警告ログを出力した
   うえでそのまま残ります（`author` 欄なしの旧ディレクトリは、他ベンダーの
   同名モジュールである可能性と区別できないための安全策です）。この場合は
   `namespace` が `RadarChart`・`js_class` が `CWidgetRadarChart` であること
   を確認したうえで、`/usr/share/zabbix/modules/radar-chart`（または
   `ui/modules/radar-chart`）を手動で削除してください。手動インストールの
   場合も同様に、旧ディレクトリを新しい `holoztek_radar_chart` ディレクトリ
   へ手動で移行してください。
2. **モジュールの再スキャンと再有効化**: Zabbix 管理画面 → 管理 → モジュール
   で「今すぐスキャン」を実行し、新しい ID（`holoztek_radar_chart`）の
   モジュールを検出させたうえで有効化します。旧 ID（`radar-chart`）の
   モジュールが一覧に残っている場合は無効化（または削除）してください。
3. **既存ダッシュボードのウィジェット type を更新**: 旧 ID で配置済みの
   ウィジェットは、Zabbix API で `type` フィールドのみを書き換えることで
   移行できます。`widgetid` ・設定フィールド（`fields`）・`reference` は
   変更不要です。
   ```php
   // 例: dashboard.get で対象ダッシュボードを取得後、
   // type が 'radar-chart' のウィジェットのみ書き換えて dashboard.update
   foreach ($dashboard['pages'] as &$page) {
       foreach ($page['widgets'] as &$widget) {
           if ($widget['type'] === 'radar-chart') {
               $widget['type'] = 'holoztek_radar_chart';
           }
       }
   }
   ```
   同様のロジックを `dashboard.update` の呼び出し前に適用してください。
4. **テンプレートダッシュボードも移行対象に含める**: 通常のダッシュボードに
   加え、ホストテンプレートに含まれるテンプレートダッシュボード
   （`templatedashboard.get` / `templatedashboard.update`）にも同じ手順を
   適用してください。テンプレート側の移行漏れがあると、そのテンプレートを
   使うホストの表示のみ旧 ID のまま残ってしまいます。

## License

This project is licensed under the MIT License.

Copyright (c) 2026 ttake-55
HOLOZTEK by ttake-55

This software bundles Apache ECharts (Apache License 2.0). See NOTICE and THIRD_PARTY_NOTICES.md for details.
