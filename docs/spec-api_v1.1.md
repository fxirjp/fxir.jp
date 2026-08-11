# SPEC-API v1.1 — FXIR Data API（v1.0を置換。外部レビュー5点対応）

制定: 2026-08-08 ｜ 実装: site/api/v1/index.php ｜ 旧版: spec-api_v1.0.md（保存）

## 一　性格（不変の3本柱＋境界の確定）
- 検証済みバッチデータのみ。リアルタイム検知はP3（物理分離系統）
- **Canonical Event First**: 配信物は fxir.jp/data/ の同一canonicalに由来し、先行・限定データは存在しない
- **研究に必要な完全Evidenceは無料公開**（`/episodes/full`含む）。課金の対価は**認証・安定配送・履歴・レート上限・商用利用ライセンス・サポート**であり、データ内容ではない。「Proだけ真実を多く知れる」設計は禁止

## 二　認証（v1.1改定）
- **`X-FXIR-KEY` ヘッダのみを正**とする。`?key=` はβ期間限定の非推奨互換（応答に `Deprecation: query-key` を付す）。正式版で廃止
- 鍵形式: `fxir_<plan>_<CSPRNG 32バイトのbase64url>`（256bit以上）。**平文表示は発行時1回のみ**
- サーバ保持: `{key_hash(SHA-256), prefix(先頭12字), plan, status(active|revoked), created_at, last_used_at}`。revoke可能
- 発行ツール: `tools/issue_key.py`（発行・失効エントリ生成）

## 三　エンドポイント
v1.0の13本＋**`/research/{id}/claims`**（WP-001・N-003の構造化claims — どの主張がFACTでどれがHYPOTHESISかを機械取得）。
予定: `/research/{id}/provenance`・`/sources`

## 四　応答（v1.1: Verifiable Envelope）
JSONは全て次の封筒で返す（canonical原本は生のまま公開を維持し、封筒はAPI表現）:
```json
{"meta": {"schema": "fxir.events.v1", "generated_at": "...",
          "source_commit": "abc1234またはnull",
          "sha256": "<canonical原本のSHA-256>",
          "canonical_url": "https://fxir.jp/data/events.json"},
 "data": { ... canonicalと同一 ... }}
```
検証手順: `curl canonical_url | sha256sum` が `meta.sha256` と一致すれば、APIレスポンス→canonical→（初回push後は）Git commit→計算コード→原資料まで追跡できる。
ヘッダ: `X-FXIR-Content-SHA256`（=meta.sha256）・`X-FXIR-As-Of`・`X-FXIR-Schema-Version: 1.1`・`ETag`・`X-RateLimit-*`。CSVは封筒なし（ヘッダのみ）。
値の単位・証拠状態のフィールド内蔵（`{"value":12.70,"unit":"JPY trillion","evidence_state":"ESTIMATE","next_verification":"2026-08-28"}`型）は、`/episodes/full`で実装済み。summary系canonicalへの全面適用はschema v1.2で行う。

## 五　プラン（内容差ではなく配送差）
| | beta（無料） | pro（法務確認後・有料） |
|---|---|---|
| レート | 500/日 | 5,000/日 |
| 用途 | 個人・評価 | **商用利用可** |
| 配送 | best effort | 安定配送・履歴・サポート・将来のWebhook統合 |

## 六　運用
ログは日時・鍵ハッシュ8桁・パスのみ。破壊的変更禁止（/v2新設・12ヶ月併存）。投資助言ではない（第42条）。
