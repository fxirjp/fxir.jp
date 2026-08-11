# SPEC-CFTC v1.0 — CFTC建玉（円）取得仕様

為替介入研究所 ｜ 制定 2026-08-08 ｜ 会則第8条一号・第45〜47条準拠

## 1. 目的

円ネットショートの再構築監視（WP-001 §8.4）。介入エピソードの前後で投機ポジションがどう変化したかを、公的一次データで週次記録する。

## 2. 一次ソース（2026-08-08 実測検証済み）

| # | ソース | URL | 状態 |
|---|---|---|---|
| 主 | 現行週 Legacy（Futures Only） | https://www.cftc.gov/dea/newcot/deafut.txt | 疎通・097741行確認【FACT】 |
| 主 | 現行週 TFF（Futures Only） | https://www.cftc.gov/dea/newcot/FinFutWk.txt | 疎通・097741行確認【FACT】 |
| 副 | Socrata API Legacy | https://publicreporting.cftc.gov/resource/6dca-aqww.json | 疎通確認【FACT】。無トークンはレート制限あり |
| 副 | Socrata API TFF | https://publicreporting.cftc.gov/resource/gpe5-46if.json | 疎通確認【FACT】 |
| 履歴 | 年次zip | cftc.gov/files/dea/history/ の deacot{YYYY}.zip ／ fut_fin_txt_{YYYY}.zip | 【UNVERIFIED・初回バックフィル時に実測】 |

対象銘柄: **JAPANESE YEN – CME、契約コード 097741**。実測時点の最新は2026-08-04（火）時点、非商業ネット −45,473枚。

## 3. 取得項目

| 系列 | 項目 | 派生 |
|---|---|---|
| Legacy | 建玉合計、非商業 Long/Short/Spread、商業 Long/Short | 非商業ネット＝L−S |
| TFF | Leveraged Funds Long/Short、Asset Manager Long/Short | 各ネット |

**閾値論の教訓**: 「円ショート−15万枚」等の警戒水準は、分母（非商業合計かLeveraged Funds単体か）で成立性が変わる（2024年7月極値: 非商業合計 約−18.4万枚、LevFunds単体 約−7.7万枚）。本仕様は両系列を必ず併録し、閾値定義は分析側の責務とする。

## 4. スケジュール（第46条: TZ明記）

| 事象 | 時刻 |
|---|---|
| データ基準日 | 毎週火曜引け時点 |
| CFTC公表 | 金曜 15:30 ET（＝JST 土曜 04:30 夏時間／05:30 冬時間） |
| 取得実行 | **土曜 07:00 JST**（GitHub Actions cron `0 22 * * 5` UTC） |
| 祝日順延 | report_date が前週から進んでいない場合、月・火 07:00 JST に再試行 |

## 5. 実装

- `scripts/fetch_cftc.py` が現行週2ファイルを取得 → 097741行を抽出 → `data/cftc/cftc_jpy.csv` に report_date キーで冪等追記
- 生ファイルを `data/cftc/raw/{report_date}_legacy.txt / _tff.txt` として保存（第44条）
- コミット時刻＝取得日時の記録（第45条）。コミットメッセージに report_date を含める

## 6. 証拠状態

公表値そのもの＝FACT。ネット等の派生値＝公開計算式による派生データ（第8条四号）。欠測・書式変更時は UNVERIFIED として空欄＋issueを起票し、推定で埋めない（第15条）。

## 7. バックフィル（初回のみ）

年次zipから2022-01以降を復元し、E1〜E6前後の建玉推移をDB化する。履歴zipの書式は現行週と異なる場合があるため、突合後にハッシュを記録する。
