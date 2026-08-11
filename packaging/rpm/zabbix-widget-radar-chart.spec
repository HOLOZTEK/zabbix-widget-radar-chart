%define _rpmfilename %%{NAME}-%%{VERSION}.%%{ARCH}.rpm
Name:           zabbix-widget-radar-chart
Version:        1.0.3
Release:        0
Summary:        Radar Chart widget for Zabbix dashboard
License:        MIT
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
- Per-item aggregation: latest value (within the dashboard time period), max, min,
  or average over time period; Max/Min/Avg automatically switch between History
  (under 2 hours) and Trend (2 hours or more, falling back to History per item/host
  when Trend data is absent)
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
LICENSEDIR=%{buildroot}%{_licensedir}/%{name}

install -d ${LICENSEDIR}
install -m 644 ${SRCDIR}/LICENSE                                                ${LICENSEDIR}/

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
install -m 644 ${SRCDIR}/locale/ja_JP/LC_MESSAGES/holoztek-radar-chart.po          ${STAGEDIR}/locale/ja_JP/LC_MESSAGES/
install -m 644 ${SRCDIR}/locale/ja_JP/LC_MESSAGES/holoztek-radar-chart.mo          ${STAGEDIR}/locale/ja_JP/LC_MESSAGES/
install -m 644 ${SRCDIR}/locale/en_US/LC_MESSAGES/holoztek-radar-chart.po          ${STAGEDIR}/locale/en_US/LC_MESSAGES/
install -m 644 ${SRCDIR}/locale/en_US/LC_MESSAGES/holoztek-radar-chart.mo          ${STAGEDIR}/locale/en_US/LC_MESSAGES/
install -m 644 ${SRCDIR}/views/item.edit.js.php                                     ${STAGEDIR}/views/
install -m 644 ${SRCDIR}/views/item.edit.php                                        ${STAGEDIR}/views/
install -m 644 ${SRCDIR}/views/widget.edit.js.php                                   ${STAGEDIR}/views/
install -m 644 ${SRCDIR}/views/widget.edit.php                                      ${STAGEDIR}/views/
install -m 644 ${SRCDIR}/views/widget.view.php                                      ${STAGEDIR}/views/

%files
%license %{_licensedir}/%{name}/LICENSE
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
MODDIR=${ZBXMODDIR}/holoztek_radar_chart
OLDMODDIR=${ZBXMODDIR}/radar-chart

# 自パッケージ専有のディレクトリなので無条件で置き換える
rm -rf "${MODDIR}"
mkdir -p "${MODDIR}"
cp -rp "${SRCSTAGE}/." "${MODDIR}/"

# v1.0.1以前は modules/radar-chart という汎用的すぎる名前を使っていたため、
# 別ベンダーのモジュールが同名ディレクトリを先に使っている可能性がある。
# 自動削除は「author が HOLOZTEK と明記されている」または「id が既に
# holoztek_radar_chart（新形式、当パッケージ以外が書く可能性は実質無い）」
# の場合のみに限定する。旧形式id(radar-chart)かつauthor欄なしのケース
# （v1.0.0のHOLOZTEK製と、authorを書いていない別ベンダー製が区別不能）は
# 自動削除せず警告のみとし、手動確認・削除を促す。
if [ -f "${OLDMODDIR}/manifest.json" ]; then
    ID_VAL=$(grep -Eo '"id"[[:space:]]*:[[:space:]]*"[^"]*"' "${OLDMODDIR}/manifest.json" | head -1 | sed -E 's/.*:[[:space:]]*"([^"]*)"/\1/')
    AUTHOR_VAL=$(grep -Eo '"author"[[:space:]]*:[[:space:]]*"[^"]*"' "${OLDMODDIR}/manifest.json" | head -1 | sed -E 's/.*:[[:space:]]*"([^"]*)"/\1/')
    AUTO_OK=0
    if [ "${AUTHOR_VAL}" = "HOLOZTEK" ] || [ "${ID_VAL}" = "holoztek_radar_chart" ]; then
        AUTO_OK=1
    fi
    if [ "${AUTO_OK}" -eq 1 ]; then
        rm -rf "${OLDMODDIR}"
    else
        echo "Warning: ${OLDMODDIR} exists (id=${ID_VAL:-unknown}, author=${AUTHOR_VAL:-unset}) but could not be confirmed as a HOLOZTEK radar-chart install. Leaving it in place; please verify manually (e.g. namespace=RadarChart, js_class=CWidgetRadarChart indicates the old HOLOZTEK v1.0.0 install) and remove it yourself if appropriate. It may instead belong to a different vendor's module." >&2
    fi
fi

%preun
if [ $1 -eq 0 ]; then
    rm -rf /usr/share/zabbix/ui/modules/holoztek_radar_chart 2>/dev/null || true
    rm -rf /usr/share/zabbix/modules/holoztek_radar_chart 2>/dev/null || true
fi

%changelog
* Tue Aug 11 2026 claude <noreply> - 1.0.3-0
- 【高】v1.0.2で追加した旧ディレクトリ自動削除の安全確認ロジックが
  不十分だった不具合を修正。旧ロジックはauthor欄が空の場合も無条件で
  HOLOZTEK由来と判定していたため、authorを書いていない別ベンダーの
  radar-chartモジュール（旧id: radar-chart）を誤って削除しうる状態
  だった。修正後は「author=="HOLOZTEK"」または「id==holoztek_radar_chart
  （新形式、自パッケージ以外が書く可能性は実質無い）」の場合のみ
  自動削除し、旧id・author空欄のケース（v1.0.0のHOLOZTEK製自身と
  区別不能）は自動削除せず警告のみとして手動確認・削除を促す方式に
  変更（%post。tree-navigator v1.4.10と同種の修正）。

* Tue Aug 11 2026 claude <noreply> - 1.0.2-0
- 【高】モジュール配置ディレクトリ(modules/radar-chart)が汎用的すぎる名前で
  他ベンダーモジュールとのファイルシステム上の衝突リスクが残っていた不具合を
  修正。配置先を modules/holoztek_radar_chart へ変更（manifest.idは既に
  holoztek_radar_chartのためdashboard widget typeの再変更は不要）。%post/
  %preunのMODDIRを新パスへ変更し、旧ディレクトリ(modules/radar-chart)は
  無条件rm -rfせず、manifest.jsonのid（radar-chartまたはholoztek_radar_chart）
  とauthor（未設定または"HOLOZTEK"）を確認した上でHOLOZTEK由来と判定できた
  場合のみ削除する安全確認ロジックを追加（DEB側debian/postinst・prermも
  同様に修正）。tree-navigator v1.4.9で同種の不具合を修正した際に同じ脆弱な
  パターンがradar-chartにも残っていることが判明したため対応
- scripts/check-version-consistency.sh を追加し、manifest.json/RPM spec/
  debian/changelogのバージョン一致をリリース前に検査可能に

* Mon Aug 10 2026 claude <noreply> - 1.0.1-0
- コードレビュー指摘対応: モジュール識別子（id/namespace/action/js_class）に
  HOLOZTEKプレフィックスを付与し、他ベンダーモジュールとの将来的な衝突を回避
  （manifest.jsonの id を holoztek_radar_chart、namespace を
  HoloztekRadarChart、js_class を CWidgetHoloztekRadarChart、action を
  widget.holoztek_radar_chart.view / .item.edit へ変更。author/description
  も追加）。パッケージ名・モジュールディレクトリ名(radar-chart)は互換性
  維持のため据え置き
- グローバル関数_rc()を_holoztek_rc()へリネーム（function_existsガードは
  維持）。gettextドメインをradar-chartからholoztek-radar-chartへ変更
  （.po/.moファイル名も追随）
- JSグローバルwindow.widget_radar_chart_form / window.rc_item_editを
  それぞれwindow.holoztek_radar_chart_form / window.holoztek_rc_item_edit
  へ変更し他モジュールとの識別子衝突リスクを解消
- CSSの.dashboard-widget-radar-chartを.dashboard-widget-holoztek_radar_chart
  へ変更（Zabbixコアがmanifest.idから自動導出するクラス名に追随）
- RPMのLICENSE同梱を標準化: %license / %{_licensedir}経由でのインストールに
  変更（従来はステージング先に生ファイルとして同梱するのみで、rpm標準の
  ライセンスディレクトリには配置されていなかった）
- README.mdに旧ID(radar-chart)からのアップグレード手順を追加

* Mon Aug 10 2026 claude <noreply> - 1.0.0-0
- 無償公開に向けたリリース。機能変更なし。反映内容:
  1. ライセンスをProprietaryからMITへ変更
  2. RPMをel9/el10個別ビルドから単一noarchファイルに統一（_rpmfilenameから
     %%{dist}を除去）。BuildArch: noarchでコンパイル済みバイナリを含まない
     ため、170/171どちらでビルドしても同一内容
  3. Debianパッケージング(.deb)をdebian/配下に追加

* Mon Jul 13 2026 claude <noreply> - 0.1.5-0
- Latest（最新値）を lastvalue ベースの現在値ではなく、ダッシュボード指定期間内の最新
  History 値に変更（period_from〜period_to内で最も新しい値を採用）
- Max/Min/Avg集計を指定期間に応じてHistory/Trendへ自動切替: 期間が2時間未満はHistory、
  2時間以上はTrend（value_max/value_min/value_avg+numによる加重平均）を優先して使用。
  Trendにデータが存在しないアイテム/ホストの組み合わせはHistoryへ自動フォールバック
- README.mdを更新（Latestの仕様変更・Max/Min/AvgのHistory/Trend自動切替について明記）
- レーダー軸ホバー検出をECharts内部座標系ベースに修正（ツールチップの値が別軸のものと
  入れ替わる不具合を解消。中心座標・各軸角度の自前推測をやめ、ECharts内部の実座標を使用）
- Giteaコードレビュー（issue #1）対応

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
