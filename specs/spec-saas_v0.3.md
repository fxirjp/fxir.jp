# SPEC-SAAS v0.3 — 配送層のfxir.jp内設置（v0.2差分改定）

為替介入研究所 ｜ 2026-08-08 ｜ v0.2の§4.2のみを差し替え。他の条項は v0.2 を正とする。

## §4.2（改） 配信・課金層 — MVP は fxir.jp 上の PHP

- **MVP配送層**: fxir.jp/api/（PHP 8.3）。キー認証つきData Package配信・Stripe webhook受け口のみを担う
- 採用理由: ①追加インフラゼロ ②**無料Canonical側と同一ホスト＝共倒れはあっても「有料だけ生存・先行」する障害が構造的に起きない**（Canonical Event First原則に有利）
- **データ生成はPython/GitHub Actionsのまま**。取得workflowが site/api/packages/ へ同期→deploy-site workflowがFTPSで自動反映。PHP側にロジックを二重実装しない
- サイト方針: コンテンツページは静的生成を維持（ページ自体のハッシュ固定・攻撃面ゼロ）。PHPは /api/ と webhook のみ
- `BILLING_ENABLED=false` をコードに固定。**法務確認完了のコミットをもって true へ変更**（禁止事項七の実装）
- 秘密情報（keys.php/config.php/ログ）は .htaccess で直アクセス拒否。キー発行はv0では手動
- Cloudflare Workers / 専用VPS への移行条件は v0.2 §4.2 のまま（有料50件超 or 系列10本超 or P3リアルタイム要件）

## 改定履歴
| 版 | 日付 | 内容 |
|---|---|---|
| v0.3 | 2026-08-08 | 配送層をWorkersからfxir.jp内PHPへ（MVP限定・移行条件維持） |
