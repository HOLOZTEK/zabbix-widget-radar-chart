%define _rpmfilename %%{NAME}-%%{VERSION}%{dist}.%%{ARCH}.rpm
Name:           zabbix-widget-radar-chart
Version:        0.1.0
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
- Red axis labels for items with no data
- Hover tooltip showing value, unit, and collection time (or aggregation period)
- Toggle button to hide hosts where all items are missing
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
