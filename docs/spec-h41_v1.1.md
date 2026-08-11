# SPEC-H41 v1.1 — FRB H.4.1 週次監視（FIMAレポ上界系列の追加）

制定: 2026-08-08 ｜ 旧版: v1.0（保存・2系列） ｜ 実装: scripts/fetch_h41.py

## 追加系列
| FRED ID | H.4.1上の行 | 意味 |
|---|---|---|
| **WORAL** | Table 1「Repurchase agreements」（週水準・百万ドル） | Fedが実行中のレポ**総額**。FIMAレポ（Foreign official）はこの内訳 |

## 上界方式（本仕様の核）
Foreign official単独の週次系列はFREDに存在しないため、**総額WORALをFIMA使用の上界として監視する**。
- WORAL ≈ 0 の週: FIMA使用ゼロが**論理的に確定**（内訳≦総額）
- WORAL > 0 の週: FIMA使用の**可能性**。内訳確認はFed H.4.1原文Table 1「Foreign official」行で行い、確認結果をイベント台帳に記録する（自動化はv1.2）
根拠: Fed公式告知（2020-04-09）によりFIMAレポはTable 1「Repurchase agreements — Foreign official」に計上される。

## 意味論（N-004接続）
残弾3経路のうち①預金取崩し（月次・財務省）②証券圧縮（週次・WMTSECL1）に加え、③**FIMAレポ（週次・本系列）**が観測下に入る。日本政府は2026-08-03にFIMA活用方針を表明済みであり、実際に引かれれば本系列が非ゼロ化して即座に検出される。制度上、FIMA使用は隠せない。

## 履歴実績（参考・取得済み全履歴より）
2020年の創設期および2023年3月に大きな使用実績（数百億ドル規模）。**E6期間（2026-07-29〜08-05週）は1〜3百万ドル≒ゼロ＝FIMA未使用が確定**。

## 出力
h41_foreign.csv に WORAL・d_WORAL列を追加（全履歴バックフィル済み）。h41_latest.json に fima_repo_total_usd_mn・d_fima_repo_total・fima_note。他はv1.0に同じ。
