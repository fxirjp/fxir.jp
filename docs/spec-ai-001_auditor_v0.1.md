# SPEC-AI-001 — Evidence Auditor v0.1

為替介入研究所 ｜ 制定 2026-08-08 ｜ 会則第14・17・53条準拠 ｜ 実装: `ai/auditor/`

## 1. 目的と設計思想

公開前の全文書（WP・Note・Weekly・速報・PR）を検査し、一次資料に裏付けられていない記述を公開前に止める。

**Auditorは3層の監査パイプラインであり、単体のAIではない。**

| 層 | 実体 | 判定できること | 権限 |
|---|---|---|---|
| **Layer A** | 決定論コード（Python） | 機械的に真偽が定まる違反 | **CRITICAL＝自動ブロック**（機械が確実に誤りと証明できるため） |
| **Layer B** | AI意味監査 | 意味・論理・修辞レベルの違反候補 | CRITICAL候補を出せるが自動ブロックしない。**人間の判断を強制する** |
| **Layer C** | 人間 | 最終承認 | 全文書に必須。PASSでも省略不可 |

非対称性が本質: **AIは単独で研究を承認できず、単独で研究を葬ることもできない。** 真実認定権は常に「決定論コード＋一次資料＋人間」の側にある（会則第14条）。

## 2. 入力スキーマ

```json
{
  "document_id": "FXIR-2026-001",
  "version": "1.0",
  "document": "本文全文...",
  "claims": [
    {
      "claim_id": "C-001",
      "text": "E5の半減期は5営業日である",
      "status": "FACT",
      "evidence": [{"calculation_id": "CALC-HL-E5"}, {"source_id": "SRC-OANDA-1D", "location": "2026-05-07..05-13"}],
      "based_on": [],
      "method": null,
      "falsification_condition": null,
      "values": [{"name": "half_life", "value": 5, "unit": "営業日"}]
    }
  ],
  "sources": [
    {"source_id": "SRC-OANDA-1D", "organization": "OANDA", "tier": 2,
     "published_at": null, "retrieved_at": "2026-08-08", "sha256": "...", "numbers": [5]}
  ],
  "calculations": [
    {"calculation_id": "CALC-HL-E5", "script": "research/WP-001/code/halflife_audit.py",
     "output_value": 5, "reproduced": true}
  ]
}
```

## 3. 出力スキーマ

```json
{
  "audit_id": "FXIR-AUD-20260808-001",
  "document_id": "FXIR-2026-001",
  "layer": "A",
  "result": "PASS | WARN | FAIL",
  "violations": [
    {"severity": "CRITICAL", "rule": "FACT_WITHOUT_PRIMARY_EVIDENCE",
     "claim_id": "C-001", "message": "...", "evidence": []}
  ],
  "stats": {"critical": 0, "warning": 0, "info": 0},
  "audited_at": "..."
}
```

判定: CRITICAL≥1 → **FAIL（公開禁止）** ／ WARNING≥1 → **WARN（人間が各項目を明示的に解決・承認）** ／ それ以外 → PASS（Layer C承認は依然必須）。

## 4. Layer A — 決定論ルール（v0.1実装済み）

| ID | ルール | 重大度 |
|---|---|---|
| A-01 | FACT_WITHOUT_PRIMARY_EVIDENCE — status=FACTでTier1/2出典も再現済み計算も無い | CRITICAL |
| A-02 | ESTIMATE_WITHOUT_METHOD — 推計に手法記録なし（第16条） | CRITICAL |
| A-03 | HYPOTHESIS_WITHOUT_FALSIFIER — 仮説に反証条件なし（第20条「可能な限り」） | WARNING |
| A-04 | NUMBER_NOT_IN_EVIDENCE — 主張中の数値が、参照する計算出力・出典数値のどこにも存在しない | CRITICAL |
| A-05 | STATUS_ESCALATION — 依拠先より強い証拠状態を主張（推計→FACT等、第11条） | CRITICAL |
| A-06 | CALC_NOT_REPRODUCED — FACTの根拠計算がreproduced≠true（第17条） | CRITICAL |
| A-07 | DATE_INCONSISTENT — retrieved_at＜published_at等の時系列矛盾 | CRITICAL |
| A-08 | TZ_MISSING — 時刻記載にタイムゾーン明示なし（第46条） | WARNING |
| A-09 | UNIT_MISSING — 数値に単位なし | WARNING |
| A-10 | SAMPLE_SIZE_MISSING — 統計語を使いながらn/標本の記載なし（第22条） | WARNING |
| A-11 | FORBIDDEN_ASSERTION — 「介入確定」「弾切れ」「円崩壊」「絶対防衛」「必ず勝てる」等（第36条） | CRITICAL |
| A-12 | EVIDENCE_DANGLING — 存在しないsource_id/calculation_idへの参照 | CRITICAL |

数値・日付・単位・TZ・営業日・標本数・計算式・参照整合は**AIに判定させない**。すべて本層。

## 5. Layer B — AI意味監査（v0.1はプロンプト固定・実行はAPIキー投入後）

検出対象: 因果飛躍／証拠より強い表現／可能性の事実化／後知恵／cherry picking／代替仮説不足／引用が主張を実際に支持しているか／根拠なき「急激」「異常」。

**固定システムプロンプト（変更は本仕様の改版を要する）**:

> あなたは為替介入研究所のEvidence Auditorである。
> この文書を良くすることを目的とするな。誤りを発見することだけを目的とせよ。
> 著者の結論を支持しようとしてはならない。文書を救おうとしてはならない。
> 判断不能なら「判断不能」とせよ。それは正式な出力である。
> 出力は指定JSONのみとし、賞賛・要約・改善提案の散文を含めてはならない。

- Research Writerとはモデル呼び出し・コンテキスト・人格を完全分離する（自己監査の禁止、第53条）
- Layer BのCRITICALは自動ブロックせず、人間に「棄却か、理由記録つき却下か」の判断を強制する
- 統計値の再計算はさせない（Layer Aの領分）

## 6. 運用

- 実行: `python ai/auditor/auditor.py input.json`（Layer A）／`--layer ab`（B併用、要ANTHROPIC_API_KEY）
- 監査結果JSONは文書とともにリポジトリ保存（第25条のEvidence Package構成物）
- 回帰テスト: 実事故由来のゴールデンケースを常備 — ①「>120日」型の存在しない観測 ②推計のFACT化 ③定義曖昧（直前速度型）
- Auditor自体の変更もバージョン管理し、どの版の監査を通過したかを文書側に記録

## 改定履歴

| 版 | 日付 | 内容 |
|---|---|---|
| v0.1 | 2026-08-08 | 初版。Layer A 12ルール実装、Layer Bプロンプト固定 |
