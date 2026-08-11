# SPEC-API v1.1.2 — FXIR Data API 契約仕様（単体完結・監査DESIGN-2026-08-08対応）

制定: 2026-08-08 ｜ 実装: site/api/v1/index.php ｜ 旧版: v1.0・v1.1・v1.1.1（保存。参照不要）

## 一　性格と境界
- 検証済みバッチデータのみ。リアルタイム検知は提供しない（P3・物理分離系統）
- Canonical Event First: 配信物は fxir.jp/data/ のcanonicalに由来し、先行・限定データは存在しない
- **完全Evidenceは無料公開。課金対象は認証・安定配送・履歴配送・レート上限・商用利用ライセンス・サポート**。「Proだけ真実を多く知れる」設計は禁止

## 二　認証・鍵
- 認証は `X-FXIR-KEY` ヘッダのみを正とする。`?key=` はβ限定の非推奨互換（`Deprecation: query-key` を付す）。正式版で廃止
- 鍵形式 `fxir_<plan>_<CSPRNG 32B base64url>`（256bit以上）。平文表示は発行時1回のみ。サーバ保持は `{key_hash(SHA-256), prefix, plan, status(active|revoked), created_at, last_used_at}`。revoke可（`tools/issue_key.py`）

## 三　エンドポイント（GET・v1固定）
`/ping`（認証不要）｜`/episodes`｜`/episodes/full`｜`/events`｜`/ratecheck`｜`/calendar`｜`/registry`｜`/corrections`｜`/status`｜`/cftc/latest`｜`/cftc/history`(CSV)｜`/h41/latest`｜`/h41/history`(CSV)｜`/research/{WP-001|N-003}/claims`
予定: `/research/{id}/provenance`・`/sources`・versioned snapshots（§九）

## 四　HTTPメソッド
GETのみ。その他は `405 {"error":"GET only"}`。

## 五　応答（Verifiable Envelope）と検証対象の定義
JSONは `{"meta":{schema, generated_at, source_commit, sha256, canonical_url}, "data": <canonicalと同一>}`。CSVは封筒なし（ヘッダのみ）。
- **SHA-256は、canonical fileとして保存済みのraw bytesに対して計算する。JSONの再整形・改行コード変換・Content-Encoding（gzip等）適用後の表現に対しては計算しない。** 検証は `curl -s <canonical_url> | sha256sum` と `meta.sha256`（=`X-FXIR-Content-SHA256`）の一致で行う
- `source_commit` は**full 40桁のGit commit SHA**（未確定時null）。7桁短縮は表示UIに限る
- ヘッダ: `X-FXIR-Content-SHA256`・`X-FXIR-As-Of`（payloadのgenerated_at/as_of）・`X-FXIR-Schema-Version`・`X-FXIR-Version: v1`・`X-FXIR-Canonical`
- 証拠状態の値内蔵（`{"value":…,"state":"ESTIMATE"}`型）は `/episodes/full`・`/events`・`/research/{id}/claims` で提供済み。summary系canonicalへの全面適用はschema v1.2

## 六　エラー（全てJSON・v1.1.2改訂）
| code | body.error | 条件 |
|---|---|---|
| **401** | missing key | 資格情報の欠落。`WWW-Authenticate: FXIR-KEY` を付す |
| **401** | invalid key | 鍵が台帳に存在しない。`WWW-Authenticate` 付き |
| **403** | key revoked | 鍵は既知だが失効（権限なし） |
| 404 | unknown endpoint | 未定義パス（`see:/api/v1/ping`付） |
| 405 | GET only | GET以外 |
| 429 | rate limit | 上限超過。`Retry-After` 秒＋§八ヘッダ |
| **503** | key store not provisioned | **鍵を提示されたが**台帳が未設置（運用者側の一時状態に限る）。資格情報の欠落に503を返さない |

## 六の二　時刻
APIの時刻表現（`/ping` の `time`、`X-RateLimit-Reset` のepoch）は**UTC**とする。JST正本原則（METHOD-v1.0 §一）に対する明示的例外として本条に定める（レート制限のUTC日次と整合させるため）。

## 七　キャッシュ
`Cache-Control: public, max-age=60`・`ETag:"<canonical sha256>"`。条件付きGET（If-None-Match→304）は**未実装**（実装時に本仕様を改定して告知。破壊的変更には当たらない）。

## 八　レート制限
- 単位は**UTC日次、00:00:00 UTCリセット**（rolling 24hではない）
- plan別: beta 500/日（個人・評価・best effort）／pro 5,000/日（商用可・安定配送・サポート）
- 全認証済み応答に `X-RateLimit-Limit`・`X-RateLimit-Remaining`・**`X-RateLimit-Reset`（次回リセットのUnix epoch秒・UTC）** を付す。429には `Retry-After` も付す

## 九　「履歴」の定義（無料とProの境界）
- **無料**: 現在のcanonical全部＋研究再現に必要な公開履歴（`/cftc/history`・`/h41/history`・アーカイブ版正本・Data Package v1.0）。過去の真実を有料壁の後ろに置かない
- **Pro（配送機能・実装予定）**: versioned snapshots（任意時点のcanonical束）・過去時点レスポンス（as-of query）・長期一括取得。**課金は「過去状態を機械的に便利に取り出す配送」に対して**であり、内容独占ではない

## 十　バージョニング
パス`/v1`固定・破壊的変更禁止。必要時は`/v2`新設、v1は最低12ヶ月併存。封筒スキーマは`meta.schema`と`X-FXIR-Schema-Version`で管理。

## 十一　運用・免責
アクセスログは日時・鍵ハッシュ先頭8桁・パスのみ（IP・個体識別は記録しない）。鍵台帳・カウンタは配信対象外。 本APIの利用は投資助言の提供を意味しない（会則第42条）。
