%define _rpmfilename %%{NAME}-%%{VERSION}.%%{ARCH}.rpm
Name:           zabbix-widget-radar-chart
Version:        1.0.7
Release:        0
Summary:        Radar Chart widget for Zabbix dashboard
License:        MIT
BuildArch:      noarch
Requires:       php >= 8.1
Requires:       php-fpm >= 8.1

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
- Per-item axis scale (min/max normalization with 0-100% clipping) and optional
  axis direction reversal; reversal affects the plotted position only, while
  tooltip value, unit, aggregate and timestamp always use the real value
- Numeric items only (float and unsigned integer); non-numeric items are rejected
- Red axis labels for items with no data
- Hover tooltip showing value, unit, and collection time (or aggregation period)
- Toggle button to hide hosts where all items are missing
- Warning banner when History data limit is reached during aggregation
  (History row limit and Latest-fetch warning threshold are configurable
  from the widget settings)
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
* Wed Sep 02 2026 claude <noreply> - 1.0.7-0
- リリース前の機能改善3点。
  1. アイテムごとに最小値を設定可能にした（従来は最大値のみ）。描画位置を
     (value - min) / (max - min) で 0〜1 に正規化し、範囲外は 0/1 にクリップ
     する。min >= max は保存不可。負数レンジ（例 -100〜-30）も設定可能。
     max の「正の数」制約は撤去。既存設定は min=0 補完で従来と同じ描画。
  2. アイテムごとに軸方向（通常／反転）を選択可能にした。反転は
     1 - ((value - min) / (max - min)) として「min→外周 / max→中心」に描画する。
     反転するのはチャート上の描画位置のみで、ツールチップの値・単位・集計結果・
     収集時刻は実値のまま。既存設定は direction=通常 補完。
  3. 最終ページでホスト数がグリッドに満たない場合もセルサイズを固定した
     （従来は残りチャートが空き領域まで拡大していた）。#effective_grid を常に
     設定グリッド（列×行）に固定し、余りは空トラックとして残す。ページ切替で
     チャートサイズ・軸ラベル位置・タイトルサイズが変化しない。
  includes/CWidgetFieldItems.php にフィールド min_val（文字列）・direction
  （int 0/1）を追加。views/item.edit.php に Min value 入力欄・Direction 選択を
  追加。一覧テーブルは Max value 列を Range 列（min – max）へ置換し Direction
  列を追加。actions/WidgetView.php は indicators に min_val/direction を付与。
  正規化・反転・クリップは assets/js/class.widget.js 側で実施し、各軸 indicator
  を min:0/max:1 に統一。locale ja_JP に Min value/Range/Direction/Normal/
  Reversed とエラー文言を追加（en_US は msgid フォールバック）。README 更新。
- （2026-09-03 資材差し替え・バージョン据え置き）actions/WidgetView.php で
  max_val の補完を PHP falsy 演算子 `?:` から null 合体 `??` へ変更。min=-50 /
  max=0 のように max がちょうど 0 の負数レンジで、描画側 indicator の max が
  100 に化けて正規化がずれる不具合を修正。旧設定で max_val キー未設定時は
  従来どおり 100 へ補完。回帰確認スクリプト scripts/test-normalization.js を
  追加（node 実行）。tag v1.0.7 を本修正コミットへ移動し配布資材を再ビルド。

* Tue Sep 01 2026 claude <noreply> - 1.0.6-0
- History 集計の取得件数上限（従来ハードコード 50,000）と、Latest 個別取得の
  描画遅延警告の閾値（従来ハードコード 500）をウィジェット設定画面から変更
  できるようにした。
  includes/WidgetForm.php に IntegerBox フィールド hist_limit（範囲
  1,000〜1,000,000・既定 50,000）と latest_warn_threshold（範囲
  10〜100,000・既定 500）を追加。views/widget.edit.php の Style ブロック直後に
  「Data limits」セクション（History rows / Latest fetch warning）を追加。
  actions/WidgetView.php は両値をフィールドから読み、未設定時は従来の既定値に
  フォールバック（$hist_limit は下限 1,000、$latest_fetch_warn_threshold は
  下限 10 でクランプ）。
  locale ja_JP に「Data limits」「History rows」「Latest fetch warning」の
  訳を追加（en_US は msgid フォールバック）。README.md の機能・制約・設定項目
  表を更新。

* Mon Aug 31 2026 claude <noreply> - 1.0.5-0
- コードレビュー issue #6 対応（5件）。
  README.md の RPM インストール例を 1.0.3 固定から <version> プレースホルダへ。
  README.md の手動インストール例を Zabbix 7.x / 8.x で別コマンドに分割。
  actions/WidgetView.php の Latest 取得コメントを実装（itemid 単位 limit=1 の
  個別取得）と整合させ、正確性と API 呼び出し回数のトレードオフを明記。
  Latest の個別取得は「対象ホスト数 × Latest 軸数」に比例するため、閾値
  （既定500）超過時に描画遅延の警告バナーを表示（正確性を損なうバッチ取得へは
  戻さない）。あわせて警告文言をハードコードしていた JS 側の不具合（2種類目の
  警告が誤表示になる）を修正し、サーバー生成の実文字列をそのまま表示するよう変更。
  PHP 依存を 8.3 以上から 8.1 以上へ引き下げ（SIGSEGV の実証再現条件は 8.0.x
  のみ。公開ステージングの PHP 8.1 依存とも整合）。php-fpm 依存は RPM 側のみ
  維持（RHEL 系は PHP-FPM 運用。Debian パッケージは Apache mod_php 運用のため
  php のみ要求）。

* Tue Aug 11 2026 claude <noreply> - 1.0.4-0
- コードレビュー issue #3 対応（6件）。
  【高】Latest値取得がvalue_type単位のバッチ取得(limit=50000)だったため、
  他itemidの履歴が多いと期間内に存在する別itemidの最新値が上限外に
  押し出され欠落しうる不具合を修正。itemid単位でlimit=1の個別取得に変更
  （actions/WidgetView.php）。
  item編集モーダルのsubmitイベントリスナーにevent.preventDefault()が
  無く、ネイティブフォーム送信とfetch送信が競合しうる不具合を修正
  （views/item.edit.js.php）。
  スクリプトタグ埋め込みのjson_encode呼び出しにJSON_HEX_TAG/HEX_AMP/
  HEX_APOS/HEX_QUOTを追加し、アイテム名に</script>等が含まれる場合の
  スクリプト崩壊リスクに対応（views/item.edit.php、item.edit.js.php。
  API応答本体のjson_encodeは対象外）。
  README.mdの手動インストール例を旧パス(modules/radar-chart)から
  holoztek_radar_chartへ修正し、Zabbix 7/8のmodules・ui/modules
  パス差異の注記を追加。
  旧ID(radar-chart)からのアップグレード手順の説明を、実装（author不明の
  旧ディレクトリは自動削除せず警告のみ）と一致するよう修正。
  debian/controlにphp(>=8.3)の依存を追加（190/191はApache mod_php運用で
  php-fpm未使用のためphp-fpmの依存は付与しない）。

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
