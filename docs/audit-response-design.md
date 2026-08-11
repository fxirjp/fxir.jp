# DESIGN-2026-08-08 対応記録
- D-1（HIGH）: **是正**。api.htmlの2表（エンドポイント・応答ステータス）を共通ラッパー`div.tbl`で内包
- D-2: **是正＋SPEC-API v1.1.2改版**。資格情報欠落=401（WWW-Authenticate: FXIR-KEY付）／失効=403／503は「鍵提示済みかつ台帳未設置」の運用者側状態に限定。api.htmlに応答ステータス表を掲載
- D-3: **是正**。無料=Data Package／有料=Data Delivery に3ページ統一（research見出しの「準備中」削除・data.html/ダッシュボード文言更新）
- D-4: **是正（全文公開）**。監査4本（初回・R1・DESIGN＋各対応記録）を/docs/へ収載し、corrections.htmlの出典リンクを実体へ接続。第28・60条の鎖を回復
- D-5: **是正**。凡例を480px未満で折返し（flex-wrap）
- D-6: **是正**。「データ8ファイル＋README/LICENSE/MANIFEST」表記へ
- D-7: **是正**。dashboardにog:title/description・不可視h1・フッター共通リンク6本を追加（ダークテーマは維持）
- D-8: **是正**。KPI帯を480px未満で2列化
- D-9: **是正（仕様明記方式）**。APIはUTCと定め、METHOD §一の明示的例外としてSPEC v1.1.2 §六の二に規定
