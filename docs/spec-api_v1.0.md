# SPEC-API v1.0 — FXIR Data API（トレーダー向け・検証済みバッチデータ）

制定: 2026-08-08 ｜ 実装: site/api/v1/index.php ｜ 上位: SPEC-SAAS（P2）

## 一　性格（何であり、何でないか）
- 本APIは**検証済みバッチデータ**を配信する。リアルタイム介入検知・シグナル配信は提供しない（それはP3 Alertの領分であり、取引系と物理分離した別系統で将来提供する。COI開示第二項）
- **Canonical Event First**: 全エンドポイントは fxir.jp/data/ の同一ファイルをそのまま配信する。APIだけの先行データ・限定データは存在しない（第39条・第60条）
- 売り物はデータではなく**配送**: 鍵つき安定エンドポイント・版付きパス・履歴の整備・更新の予見可能性

## 二　認証・プラン
- ヘッダ `X-FXIR-KEY`（または `?key=`）。鍵はサーバ側でSHA-256ハッシュのみ保持（平文非保存・第61条）
- プラン: `beta`（500req/日・無料・info@fxir.jp で手動発行）／`pro`（5,000req/日・**有料開始は法務確認完了後**。βキーには移行案内を送る）
- 価格は法務確認と併せてSPEC-SAAS v0.4で確定する（本仕様では定めない）

## 三　エンドポイント（GET・v1固定）
`/api/v1/ping`（認証不要）｜`/episodes`｜`/episodes/full`｜`/events`｜`/ratecheck`｜`/calendar`｜`/registry`｜`/corrections`｜`/status`｜`/cftc/latest`｜`/cftc/history`(CSV)｜`/h41/latest`｜`/h41/history`(CSV)

## 四　応答規約
- 全応答に `X-Content-SHA256`・`ETag`・`Cache-Control: max-age=60`・`X-RateLimit-*`
- エラーはJSON `{"error": ...}`。401 invalid key／429 rate limit（Retry-After付）／404 unknown endpoint／405 GET only／503 β鍵未発行案内
- 破壊的変更は行わない。必要時は `/v2` を新設し、v1は最低12ヶ月併存する

## 五　更新スケジュール（ベストエフォート。SLAの明文化は有料開始時）
CFTC: 公表（金曜15:30 ET）後の自動取得、通常**土曜午前JST**までに反映｜H.4.1: 公表（木曜16:30 ET）後、通常**金曜午前JST**までに反映｜episodes/events/calendar: 研究更新に随時追従

## 六　運用・監査
- アクセスログは日時・鍵ハッシュ先頭8桁・パスのみ保持（第45条。IP・個体識別は記録しない）
- 鍵ファイル・レート制限カウンタは配信対象外（.htaccess遮断）
- 本APIの利用は投資助言の提供を意味しない（会則第42条）
