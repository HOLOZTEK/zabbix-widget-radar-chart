%define _rpmfilename %%{NAME}-%%{VERSION}%{dist}.%%{ARCH}.rpm
Name:           zabbix-widget-radar-chart
Version:        0.1.4
Release:        0
Summary:        Radar Chart widget for Zabbix dashboard
License:        Proprietary
BuildArch:      noarch
Requires:       php >= 8.3
Requires:       php-fpm >= 8.3

%description
Zabbix dashboard widget that visualizes multiple host item values
as radar charts arranged in a grid layout.

Features:
- Grid layout (up to 6x6) for displaying multiple hosts simultaneously
- Flexible host selection: host groups, individual hosts, or host patterns (wildcard)
- Integration with Tree Navigator / Topology Navigator via host group linkage
- Per-item aggregation: latest value, max, min, or average over time period
- Numeric items only (float and unsigned integer); non-numeric items are rejected
- Red axis labels for items with no data
- Hover tooltip showing value, unit, and collection time (or aggregation period)
- Toggle button to hide hosts where all items are missing
- Warning banner when History data limit is reached during aggregation
- Pagination when displayed hosts exceed the grid capacity
- Customizable style: line, point, fill, chart, and title color/size
- Japanese and English locale support

%prep
# nothing

%install
SRCDIR=%{_sourcedir}/zabbix-widget-radar-chart
STAGEDIR=%{buildroot}/usr/share/zabbix-widget-radar-chart

install -d ${STAGEDIR}/actions
install -d ${STAGEDIR}/assets/css
install -d ${STAGEDIR}/assets/js
install -d ${STAGEDIR}/includes
install -d ${STAGEDIR}/locale/ja_JP/LC_MESSAGES
install -d ${STAGEDIR}/locale/en_US/LC_MESSAGES
install -d ${STAGEDIR}/views

install -m 644 ${SRCDIR}/LICENSE                                                    ${STAGEDIR}/
install -m 644 ${SRCDIR}/NOTICE                                                     ${STAGEDIR}/
install -m 644 ${SRCDIR}/README.md                                                  ${STAGEDIR}/
install -m 644 ${SRCDIR}/THIRD_PARTY_NOTICES.md                                     ${STAGEDIR}/
install -m 644 ${SRCDIR}/manifest.json                                              ${STAGEDIR}/
install -m 644 ${SRCDIR}/Module.php                                                 ${STAGEDIR}/
install -m 644 ${SRCDIR}/Widget.php                                                 ${STAGEDIR}/
install -m 644 ${SRCDIR}/actions/ItemEdit.php                                       ${STAGEDIR}/actions/
install -m 644 ${SRCDIR}/actions/WidgetView.php                                     ${STAGEDIR}/actions/
install -m 644 ${SRCDIR}/assets/css/widget.css                                      ${STAGEDIR}/assets/css/
install -m 644 ${SRCDIR}/assets/js/echarts.min.js                                   ${STAGEDIR}/assets/js/
install -m 644 ${SRCDIR}/assets/js/class.widget.js                                  ${STAGEDIR}/assets/js/
install -m 644 ${SRCDIR}/includes/CWidgetFieldItems.php                             ${STAGEDIR}/includes/
install -m 644 ${SRCDIR}/includes/CWidgetFieldItemsView.php                         ${STAGEDIR}/includes/
install -m 644 ${SRCDIR}/includes/helpers.php                                       ${STAGEDIR}/includes/
install -m 644 ${SRCDIR}/includes/WidgetForm.php                                    ${STAGEDIR}/includes/
install -m 644 ${SRCDIR}/locale/ja_JP/LC_MESSAGES/radar-chart.po                   ${STAGEDIR}/locale/ja_JP/LC_MESSAGES/
install -m 644 ${SRCDIR}/locale/ja_JP/LC_MESSAGES/radar-chart.mo                   ${STAGEDIR}/locale/ja_JP/LC_MESSAGES/
install -m 644 ${SRCDIR}/locale/en_US/LC_MESSAGES/radar-chart.po                   ${STAGEDIR}/locale/en_US/LC_MESSAGES/
install -m 644 ${SRCDIR}/locale/en_US/LC_MESSAGES/radar-chart.mo                   ${STAGEDIR}/locale/en_US/LC_MESSAGES/
install -m 644 ${SRCDIR}/views/item.edit.js.php                                     ${STAGEDIR}/views/
install -m 644 ${SRCDIR}/views/item.edit.php                                        ${STAGEDIR}/views/
install -m 644 ${SRCDIR}/views/widget.edit.js.php                                   ${STAGEDIR}/views/
install -m 644 ${SRCDIR}/views/widget.edit.php                                      ${STAGEDIR}/views/
install -m 644 ${SRCDIR}/views/widget.view.php                                      ${STAGEDIR}/views/

%files
/usr/share/zabbix-widget-radar-chart/

%post
if [ -d /usr/share/zabbix/ui/modules ]; then
    ZBXMODDIR=/usr/share/zabbix/ui/modules
elif [ -d /usr/share/zabbix/modules ]; then
    ZBXMODDIR=/usr/share/zabbix/modules
else
    echo "Warning: Zabbix modules directory not found. Skipping module installation." >&2
    exit 0
fi

SRCSTAGE=/usr/share/zabbix-widget-radar-chart
MODDIR=${ZBXMODDIR}/radar-chart

rm -rf "${MODDIR}"
mkdir -p "${MODDIR}"
cp -rp "${SRCSTAGE}/." "${MODDIR}/"

%preun
if [ $1 -eq 0 ]; then
    rm -rf /usr/share/zabbix/ui/modules/radar-chart 2>/dev/null || true
    rm -rf /usr/share/zabbix/modules/radar-chart 2>/dev/null || true
fi

%changelog
* Sun Jul 12 2026 claude <noreply> - 0.1.4-0
- アイテムのマッチングロジックをキー（key_）基準からアイテム名（name）基準に変更
  （テンプレート・ホストの所属を無視し、アイテム名でのみマッチング）
- 同一ホスト内でアイテム名が重複する場合はアイテムIDが若番の方を採用

* Sat Jun 28 2026 claude <noreply> - 0.1.3-0
- Widget.phpのファイルスコープでrequire_onceを追加（CModuleManager::getWidgetsDefaults()がModule::init()より前にWidget::getDefaultName()を呼び出すため_rc()が未定義になるバグを修正）
- bindtextdomainをhelpers.phpに移動（function_existsガードと一体化）
- README.mdのRPMインストール例バージョンを0.1.3に更新
- locale ja_JP / en_US の Project-Id-Version を0.1.3に更新

* Fri Jun 27 2026 claude <noreply> - 0.1.2-0
- _rc()の読み込みをModule::init()に移動（アクション単体実行時でも必ず利用可能）
- helpers.phpにfunction_exists('_rc')ガードを追加
- NOTICEファイルを追加（ECharts / ZRender ASFポリシー準拠）
- READMEを最新化（RPMバージョン修正・数値アイテム制約・groupidsスコープ・History上限・RPMビルド手順追加）
- RPM specにREADME.mdとNOTICEを追加
- HTMLエスケープをtooltip埋め込みHTML文字列のみに限定（title.text・軸名・系列名は生文字列）

* Fri Jun 27 2026 claude <noreply> - 0.1.1-0
- アイテム行数カウントバグ修正（Addボタン行を除外する#getItemRows()を追加）
- 数値アイテム（float/uint64）のみ選択・保存を許可（UI: numeric:1、サーバー: value_type検証）
- History集計でlimit到達時に警告バナーを表示（不完全な集計値のサイレント表示を抑止）
- tooltipのHTML特殊文字エスケープ対応（#escapeHtml追加）
- manifest.jsonのwidget.inにgroupidsを追加（_hostgroupid型）
- groupids + host_patternsの仕様を案Bに変更（groupidsをスコープとして使用）
- JS文言をgetTranslationStrings()経由に変更（t()関数で参照、.poで翻訳可能）
- Widget.phpにinit()とbindtextdomainを追加、_rc()ヘルパー関数を新設
- 全PHPファイルの文字列を_rc()に統一
- LICENSE・THIRD_PARTY_NOTICES.md（ECharts Apache 2.0）を追加

* Fri Jun 27 2026 claude <noreply> - 0.1.0-0
- 初回リリース
- グリッドレイアウト（最大6×6）で複数ホストのレーダーチャートを並列表示
- ホストグループ / 個別ホスト / ホストパターン（ワイルドカード）によるホスト指定
- Tree Navigator / Topology Navigator とのホストグループ連動
- アイテムごとに最新値 / 最大値 / 最小値 / 平均値の集計方法を選択
- データなしアイテムの軸ラベルを赤色で表示
- 頂点ホバーで値・単位・収集時刻（または集計期間）をツールチップ表示
- 全欠損ホスト非表示トグルボタン
- ページング対応
- 線・点・面・チャート・タイトルのスタイルカスタマイズ
- 日本語 / 英語ロケール対応
