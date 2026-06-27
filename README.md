# Radar Chart Widget for Zabbix

複数のホストのアイテム値をレーダーチャートで並べて可視化するウィジェットです。ホストグループナビゲーターと連携し、任意のアイテムを3〜8つ軸として設定できます。

## 機能

- **複数ホストの並列表示**: グリッドレイアウト（最大 6×6）で複数ホストを一覧表示
- **柔軟なホスト指定**: ホストグループ / 個別ホスト / ホストパターン（ワイルドカード対応）の3方式
- **ナビゲーター連動**: Tree Navigator / Topology Navigator などのホストグループナビゲーターとリンク可能
- **集計方法選択**: 各アイテムごとに最新値 / 最大値 / 最小値 / 平均値を選択
- **数値アイテムのみ対応**: float および unsigned integer 型のアイテムのみ選択可能
- **データなし表示**: 値が取得できないアイテムの軸ラベルを赤色で表示
- **ホバーツールチップ**: 頂点にカーソルを合わせると値・単位・収集時刻（または集計期間）を表示
- **全欠損ホスト非表示**: すべてのアイテムにデータがないホストをトグルボタンで非表示にする
- **ページング**: 表示ホスト数がグリッドを超えた場合に前/次ページボタンで切り替え
- **集計警告**: History集計で件数上限（50,000件）に達した場合、不完全な集計値である旨を警告表示
- **スタイルカスタマイズ**: 線・点・面・チャート・タイトルの色やサイズを個別設定
- 日本語 / English ロケール対応

## 動作環境

| 項目 | 要件 |
|------|------|
| Zabbix | 7.0 以上 |
| PHP | **8.3 以上**（8.0 系では PHP エンジンの既知バグにより SIGSEGV が発生します） |
| OS | Rocky Linux 9 / Rocky Linux 10（その他 RHEL 系 Linux） |

> **注意**: PHP 8.0.x はダッシュボード描画時に複数ウィジェットのフォーム処理が並行実行される際、PHP エンジン内で SIGSEGV（セグメンテーション違反）が発生し、PHP-FPM ワーカーがクラッシュすることが確認されています。PHP 8.3 以上へのアップグレードで解消されます。

## 制約事項

- **数値アイテムのみ**: float（小数）および unsigned integer（符号なし整数）型のアイテムのみ使用できます。文字列・テキスト・ログ型は選択不可です。
- **ホストグループとホストパターンの併用**: `Host groups` と `Host patterns` を両方指定した場合、ホストパターンの検索範囲がホストグループでスコープ限定されます（OR マージではありません）。
- **大量データ集計の制限**: 最新値以外の集計（Max/Min/Avg）では Zabbix History API を使用するため、取得件数が上限（50,000件）に達すると集計値が不完全になる場合があります。長期間・大量ホストを対象とした集計には制約があります。警告バナーが表示された場合は、集計期間を短くするか対象ホスト数を減らしてください。

## インストール

### RPM パッケージ（推奨）

```bash
# Rocky Linux 9 (el9)
rpm -ivh zabbix-widget-radar-chart-0.1.3.el9.noarch.rpm

# Rocky Linux 10 (el10)
rpm -ivh zabbix-widget-radar-chart-0.1.3.el10.noarch.rpm
```

インストール後、Zabbix フロントエンドの **管理 → モジュール** からモジュールを有効化してください。

### 手動インストール

```bash
cp -r zabbix-widget-radar-chart /usr/share/zabbix/modules/radar-chart
```

## RPM ビルド手順

```bash
# ソースを SOURCES に配置
mkdir -p ~/rpmbuild/SOURCES ~/rpmbuild/SPECS
cp -r zabbix-widget-radar-chart ~/rpmbuild/SOURCES/
cp zabbix-widget-radar-chart/packaging/rpm/zabbix-widget-radar-chart.spec ~/rpmbuild/SPECS/

# .mo ファイルのコンパイル（msgfmt が必要）
msgfmt ~/rpmbuild/SOURCES/zabbix-widget-radar-chart/locale/ja_JP/LC_MESSAGES/radar-chart.po \
  -o ~/rpmbuild/SOURCES/zabbix-widget-radar-chart/locale/ja_JP/LC_MESSAGES/radar-chart.mo
msgfmt ~/rpmbuild/SOURCES/zabbix-widget-radar-chart/locale/en_US/LC_MESSAGES/radar-chart.po \
  -o ~/rpmbuild/SOURCES/zabbix-widget-radar-chart/locale/en_US/LC_MESSAGES/radar-chart.mo

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
| Time period | 集計期間（ダッシュボードの時間軸と連動） |

## ライセンス

商用ライセンス（詳細は LICENSE ファイルを参照）

無断の再配布を禁じます。

このソフトウェアは Apache ECharts（Apache License 2.0）を同梱しています。詳細は NOTICE および THIRD_PARTY_NOTICES.md を参照してください。
