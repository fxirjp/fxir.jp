# SPEC-SAAS v0.2 — FXIR Data 構想仕様

為替介入研究所 ｜ 起草 2026-08-08 ｜ v0.1からの改定: 4点（改定履歴参照）

## 0. 一行定義（固定）

**研究結論は永久に無料。売るのは、検証済み構造化データの配送。**

## 1. 会則整合（本仕様の最上位制約）

| # | 原則 | 根拠 | 実装 |
|---|---|---|---|
| 1 | **Canonical Event First原則** — 相場に影響しうる情報は、fxir.jp上の無料Canonical Event（event_id・SHA-256・published_at）が成立した後にのみ、有料チャネルへの配送を解禁する。「同時送信」では配送遅延差で有料先行が起こりうるため、順序で保証する | 第39条2項・第41条 | §4.1のイベント基盤。各チャネルのsent_atをイベントログに記録し、適合を事後証明可能にする |
| 2 | **結論の有料限定禁止** — WP/Note/速報/Weeklyの本文・結論・グラフ・Methodologyは常に無料で検証可能 | 第60条 | 有料側は同一情報のData Package（§3） |
| 3 | **売買推奨ゼロ** | 第42条 | Auditor Layer Aの禁止語検査 |
| 4 | **AI出力の未監査配信禁止** — Writer生成物はAuditor（SPEC-AI-001）＋人間承認後のみ | 第14条 | パイプラインで強制 |
| 5 | **OANDA生値の再配信禁止** — 商品データは公的一次＋自家計算派生値のみ | 第8条・データ来歴方針 | 価格OHLC/tickはAPIに載せない |

## 2. 法務ゲート（課金開始の必須前提）

- **確認対象は課金方式ではなく、提供内容そのもの。** 「観測・データのみ／売買推奨なし／会員限定の投資判断提供なし」の構成について、弁護士に事前確認（3〜10万円）。**確認完了まで、形態を問わずいかなる課金も開始しない**
- 特商法表記・プライバシーの商用拡張、Stripe・インボイス要否判断

## 3. 商品ライン（MVP順）

| P | 商品 | 無料（常時公開） | 有料（Data Package） | 状態 |
|---|---|---|---|---|
| **P1** | **FXIR Weekly** | 本文全文・結論・主要グラフ・Methodology | CSV/JSON全系列・履歴比較・機械可読Evidence・API-readyパッケージ・ローカル分析用ファイル | **#001は無料版のみで発行**（今週金曜データ）。有料は法務確認後 |
| **P2** | **FXIR Data API** | — | episodes DB／cftc_jpy／h41_foreign／派生統計のJSON配信 | 法務確認後 |
| **P3** | **FXIR Alert** | Canonical Event＋X | Webhook/Discord配送（Canonical成立後のみ） | Detector実装後 |

P1は最初から**Readingを売らず、Data Packageを売る**。無料本文と有料の境界が曖昧にならず、P2 APIへの導線になる。

価格素案（検証対象）: API 個人¥1,980/月・商用¥9,800/月、Weekly Package ¥500/号。**90日で有料合計10件未満なら価格・形態を改定**。

## 4. アーキテクチャ

### 4.1 Canonical Event基盤（P3の中核・P1/P2にも適用）

```
Detector / Weekly生成
      ↓
Event Package生成（観測・証拠状態・出典）
      ↓
SHA-256 / event_id 確定
      ↓
fxir.jp/events/{id} 公開 ＝ canonical_published_at 記録
      ↓
   ┌──────┬─────────┬──────────┐
   X     Discord    Webhook/API
  FREE    PAID        PAID
```

イベントログ（各イベントに保存・公開）:

```json
{
  "event_id": "FXIR-EVT-20260808-001",
  "status": "SUSPECTED",
  "sha256": "...",
  "canonical_published_at": "...",
  "x_sent_at": "...",
  "discord_sent_at": "...",
  "webhook_sent_at": "..."
}
```

**有料サービスはCanonical Eventより先行してはいけない。** 障害時は有料側を止め、無料側を止めない。

### 4.2 配信・課金層

- repoがsource of truth。api.fxir.jpはCloudflare Workers＋KVの読み取り専用ミラー
- お名前.com共用サーバは静的サイト専用。PostgreSQL/FastAPI/専用VPSは移行条件（有料50件超 or 系列10本超 or P3リアルタイム要件）まで採用保留
- P3のリアルタイム検出はYenDoller系と物理分離した専用環境（第40条）

## 5. AIエージェントとの関係

| Agent | 役割 | 優先度 |
|---|---|---|
| **Evidence Auditor** | **3層監査パイプライン（SPEC-AI-001）**: Layer A決定論チェック／Layer B AI意味監査／Layer C人間承認。AIは拒否権候補を出せるが、単独で承認も棄却もできない | **1（実装中）** |
| Source Reader | 一次資料の構造化（位置参照つき） | 2 |
| Research Writer | 構造化データ→文面。自由Web検索なし。Auditor通過後のみ配信 | 3 |
| Market Event Detector | Python計算→AIは説明列挙のみ。「介入確定」を言わせない | 4（Phase 2） |
| Hypothesis Engine | 反証条件つき仮説生成。商品化しない | 5 |

## 6. 禁止事項

一　研究結論・速報本文・Weekly本文を有料限定にすること。
二　Canonical Event成立前の有料配送。
三　売買ポイント・投資判断の提供。
四　OANDA生値（OHLC/tick）のAPI再配信。
五　Auditor・人間承認を経ないAI生成物の配信。
六　証拠状態を超える表示（第11条・第36条）。
七　法務確認完了前の課金開始（形態を問わない）。

## 7. 直近の実行順

1. **SPEC-AI-001 Evidence Auditor実装**（Layer A本日着手）
2. P1 #001を**無料版として**今週金曜データで発行
3. 法務確認の予約 → 完了後にP1課金・P2着手
4. Canonical Event基盤（fxir.jp/events/）の静的実装

## 改定履歴

| 版 | 日付 | 内容 |
|---|---|---|
| v0.1 | 2026-08-08 | 初版起草 |
| v0.2 | 2026-08-08 | ①「P1は法務確認前でも開始可」を削除（法務ゲートの例外を廃し、確認対象を提供内容に統一）②同時公開原則をCanonical Event First原則へ技術仕様化 ③AuditorをAI単体から3層監査パイプラインへ ④P1をData Package販売に再定義 |
