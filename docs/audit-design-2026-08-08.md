# fxir.jp デザイン・整合性チェック報告

**対象**：全15ページ（新規4ページを含む）＋配布ファイル
**検査日**：2026年8月8日（JST）
**検査幅**：320 / 360 / 390 / 414 / 768 / 1024 / 1440 px
**版**：DESIGN-2026-08-08 / v1.0

---

## 0. 結論

| 区分 | 件数 |
|---|---|
| HIGH（実害のある表示崩れ） | 1 |
| MEDIUM（整合性・API仕様） | 4 |
| LOW（表記・構造） | 4 |

**レイアウトの完成度は高い。** 320px から 1440px まで、**api.html を除く全14ページで横スクロールが一切発生しない**。ヘッダーナビは14項目を「その他 ▾」に集約しており、390px でもポップアップは画面内（L10–R365 / vw375）に収まる。wp-001 の16個の表はすべて `div.tbl`（`overflow-x:auto`）で内包されている。

指摘の中心は**新規追加分に集中している**。api.html の表だけが既存の表ラッパー規約から漏れており、Data API の HTTP ステータス設計に誤りがあり、「Data Package」という語が2つの別物を指している。

内部リンク37本は切れゼロ、表示ハッシュ13種はすべて実ファイルと一致した。前回監査の残件だった N-1（50%回復線）・N-2（source_commit）はいずれも解消を確認した。

---

## 1. HIGH

### 🔴 D-1. api.html だけがスマホで横スクロールする

**実測**

| 検査幅 | scrollWidth / viewport | 判定 |
|---|---|---|
| 390px | 485 / 371 | **114px 超過** |
| 360px | 485 / 345 | **140px 超過** |
| 320px | 485 / 305 | **180px 超過** |
| 他14ページ（全幅） | 一致 | 超過なし |

**原因（特定済み）**

「エンドポイント（GET・v1固定）」の表が `<article>` 直下に置かれており、**サイト共通の表ラッパー `div.tbl` が欠落している**。

| ページ | 表の親要素 | overflow-x | 表実幅 / 親幅 |
|---|---|---|---|
| **api.html** | **`article`** | **visible** | **469 / 343** |
| wp-001.html（16表） | `div.tbl` | auto | 341 / 343 |
| privacy.html | `div.tbl` | auto | 341 / 343 |

**影響**

ページ全体が横方向にスクロールするため、本文・見出し・ヘッダーまで一緒に流れる。加えて添付スクリーンショットのとおり、

- 第3列「**形式**」が完全に画面外
- 第2列「内容」も語中で切断（「介入エピソード（要約／証…」「イベント台帳（E6・レート…」「Rate Check Monitor（…」）

エンドポイント一覧は API ページの中核情報であり、スマホでは仕様が読めない状態にある。

**修正**

表を既存規約に合わせて 1 行で包む。

```html
<div class="tbl">
  <table> … エンドポイント一覧 … </table>
</div>
```

---

## 2. MEDIUM

### 🟠 D-2. Data API の未認証応答が 503（HTTP セマンティクスの誤り）

**実測** — 認証不要の `/ping` 以外、全9エンドポイントが同一応答を返す。

```
GET /api/v1/ping          → 200  {"ok":true,"service":"fxir-data-api", …}
GET /api/v1/events        → 503  {"error":"beta: keys are issued manually. contact info@fxir.jp（無料）"}
GET /api/v1/episodes      → 503  （同上）
GET /api/v1/ratecheck     → 503  （同上）
GET /api/v1/calendar      → 503  （同上）
GET /api/v1/registry      → 503  （同上）
GET /api/v1/corrections   → 503  （同上）
GET /api/v1/status        → 503  （同上）
GET /api/v1/cftc/latest   → 503  （同上）
GET /api/v1/h41/latest    → 503  （同上）
```

**問題**

1. **鍵の不在は 503 ではない。** 503 Service Unavailable は「サーバが一時的に応答できない」の意味であり、クライアント・監視ツール・CDN は自動リトライやバックオフの対象として扱う。認証情報の不足は **401 Unauthorized**（`WWW-Authenticate` 付き）、鍵はあるが権限不足なら **403 Forbidden** が正しい。
2. **status.html は全系 HEALTHY を表示している。** 一方 API は全エンドポイントで 503 を返す。外形監視から見ると「稼働状態は健全と自称しているがサービスは落ちている」という矛盾した信号になる。
3. **api.html に記載がない。** 応答規約の節は 429（Retry-After 付）と「エラーはJSON」に触れているが、**401 / 403 / 503 の記載は一切ない**（全文検索で確認）。実際に返る唯一のエラーが未記載という状態。

**修正**：未認証は 401、権限不足は 403 に変更し、api.html の応答規約にステータス表を追加する。β期間中も鍵さえあれば動くのであれば 503 を使う理由はない。

---

### 🟠 D-3.「Data Package」が2つの別物を指している

| 所在 | 「Data Package」の指すもの | 状態表記 |
|---|---|---|
| research.html 見出し | — | 「Data Package（**準備中**）」 |
| research.html 本体 | **無料の公開済み zip** | 「Data Package v1.0（**無料・確定版**）」 |
| research.html 下部 | 有料配送 | 「有料の **Data Delivery**（準備中・法務確認後）」 |
| data.html | **有料配送** | 「Data Package（準備中・法務確認後）は整形・API・更新保証の配送形態」 |
| dashboard | 有料配送の説明直後にボタン | 「DATA PACKAGE →」→ 遷移先は research.html（＝無料 zip のページ） |

**問題**

research.html では有料側を「Data Delivery」に改称して無料／有料を分離できているが、**data.html と dashboard が旧称のまま**で、同じ「Data Package」が有料の未提供サービスを指している。読者から見ると、無料で今すぐ落とせる確定版と、準備中の有料サービスが同名になる。

さらに research.html 内部でも、見出し「Data Package（**準備中**）」の直下に「Data Package v1.0（**無料・確定版**）」と実ダウンロードリンクが並んでおり、見出しが自己矛盾している。

**修正**：無料＝**Data Package**、有料＝**Data Delivery** に統一。research.html の見出しから「（準備中）」を削除し、data.html・dashboard の文言を「Data Delivery（準備中）」に置換する。

---

### 🟠 D-4. corrections.html の「監査報告R1」リンクが別文書を指している

**実測**

```
リンクテキスト: 監査報告R1
href:          docs/n-003.md
```

`docs/n-003.md` は「**レートチェックの証拠構造**」（Research Note）であり、監査報告ではない。

**加えて、監査報告そのものがサイト上に存在しない。**

| 探索パス | 応答 |
|---|---|
| `/docs/audit-2026-08-08.md` | 404 |
| `/docs/audit-2026-08-08-r1.md` | 404 |
| `/docs/audit_r1.md` | 404 |
| `/audit.html` | 404 |

一方、サイトは複数箇所で外部監査を訂正の根拠として引用している。

- WP-001 改訂履歴 v1.2：「**外部監査 AUDIT-2026-08-08 への対応**」
- corrections.html（エピソードDB E6）：「**外部監査R1・N-1指摘**」
- corrections.html（N-003）：「公開前監査の3指摘」

**問題**

corrections.html は「間違いの全記録。隠さない」を掲げるページである。そこで根拠として引用されている監査報告が非公開かつリンク先も誤っているため、読者は「何を指摘され、どう直したのか」を照合できない。訂正の**理由**は書かれているが、理由の**出典**に到達できない状態であり、第28条（来歴）・第60条（検証可能な形式での公開）の観点で鎖が切れている。

**修正**：監査報告を収載して正しくリンクする。全文公開が難しい場合でも、指摘 ID と要旨の一覧（AUDIT-2026-08-08 / R1 の指摘表）を掲載すれば照合可能になる。公開しない方針なら、リンクを外して「外部監査（非公開）」と明示するほうが正確。

---

### 🟠 D-5. ダッシュボードの証拠状態レジェンドがスマホで6割隠れる

**実測**（390px）

```
凡例コンテナ  div.wrap
overflow-x    auto
clientWidth   375
scrollWidth   800     → 425px（53%）が画面外
```

表示は「本ダッシュボードの色は、価格の方向ではなく証拠状態を表す: ■ CONFI…」で切断される。

**問題**

この凡例は CONFIRMED / ESTIMATE / UNVERIFIED / RETRACTED の**色の意味を定義する唯一の説明**であり、ダッシュボード全体の読み方の鍵にあたる。横スクロールは可能だが、細いスクロールバー以外に「続きがある」手掛かりがなく、スマホ利用者の多くは色の意味を知らないまま数値を読むことになる。

会則第10条・第11条が求める「証拠状態を区分して表示する」の実効性に関わるため、単なる見た目の問題ではない。

**修正**：480px 未満では `flex-wrap: wrap` で2〜3行に折り返す。凡例は情報量が少ないので、折り返しても縦の消費は限定的。

---

## 3. LOW

### 🟡 D-6. Data Package のファイル数表記が実体と合わない

research.html：「fxir-data-package-v1.0.zip（**85 KB・8ファイル**）」

**実測**

| 項目 | 表示 | 実測 | 判定 |
|---|---|---|---|
| サイズ | 85 KB | 87,084 B（85 kB） | ✅ |
| SHA-256 | `725759e3…5bca4` | 同一 | ✅ |
| ファイル数 | 8 | **11** | ❌ |

zip 内訳（中央ディレクトリ実測）：

```
README.md               1,583 B
LICENSE.md                584 B
PACKAGE_MANIFEST.json   1,350 B
data/episodes_v1.0.json 9,059 B
data/episodes_v1.0.csv    791 B
data/events.json        8,090 B
data/ratecheck.json       598 B
data/cftc_jpy.csv         280 B
data/h41_foreign.csv   49,212 B
verify/WP-001-claims.json 10,130 B
verify/N-003-claims.json   4,137 B
```

`PACKAGE_MANIFEST.json` の `files` 配列は 8 件（data 6 + verify 2）なので「8」はマニフェスト掲載数を指していると読める。ただし利用者が展開して数えると 11 になる。

**修正**：「データ8ファイル＋README・LICENSE・MANIFEST」等に。

なお、zip 内の `verify/WP-001-claims.json` はサイト配信中の `/data/verify/WP-001-claims.json` と**バイト単位で同一**（`e4093f16…`）であることを確認した。api.html の「サイトで無料公開中の同一ファイルを配信」という主張は裏付けられている。

---

### 🟡 D-7. ダッシュボードだけサイト共通の枠組みから外れている

| 項目 | 他14ページ | dashboard/ |
|---|---|---|
| ヘッダーナビ | 14リンク | **1リンク**（研究所トップのみ） |
| フッターリンク | 7 | **1** |
| 外部スタイルシート | 1 | **0**（インラインのみ） |
| `h1` | 1 | **0** |
| `og:title` | あり | **なし** |
| 配色 | ライト | ダーク（ターミナル調） |

ダークテーマと独自ヘッダーは「観測端末」としての意図的な差別化と読める（実際、視覚的な説得力はある）。ただし副作用が3点ある。

1. **回遊がトップ経由に限定される。** ダッシュボードから events / verify / registry へ直接行けない（本文中のリンクを除く）
2. **SNS 共有時にカードが生成されない。** og:title 未設定。X 運用を前提とする組織では実害が出やすい
3. **見出し構造がない。** `h1` 不在はスクリーンリーダーでの読み上げ順序に影響する

**修正**：配色とレイアウトは維持したまま、`og:title` / `og:description`、`h1`（視覚的に隠してもよい）、フッターへの共通リンク群の3点を追加する。

---

### 🟡 D-8. KPI 帯がスマホで3列114pxに圧縮される

`div.strip` は 390px 幅で `grid-template-columns: 114.3px 114.3px 114.3px`。「E6 NOT RECOVERED」「NEXT VERIFICATION」等が3行に折り返し、1タイル 114px に長いラベルと数値が同居する。

読めなくはないが、ダッシュボードで最初に目に入る要素としては窮屈。**修正**：480px 未満で2列（または1列）に。

---

### 🟡 D-9. API のタイムスタンプが JST でない

`/api/v1/ping` の応答：

```json
"time": "2026-08-08T09:56:59+00:00"
```

METHOD-v1.0 は「すべて JST で記録し、確定時刻は ISO 8601（+09:00）」と定めており、サイト内の他の時刻（verify.html の監査時刻、status.html の generated、dashboard の data generated：いずれも `2026-08-08T18:35:00+09:00`）はこれに従っている。API だけ UTC。

オフセットが明示されているため会則第46条には反しないが、自ら固定した METHOD-v1.0 からは外れる。**修正**：`+09:00` に統一するか、API 仕様書（SPEC-API）に「API は UTC」と明記する。

---

## 4. 良好と確認できた点

### 4.1 レスポンシブ

- **320 / 360 / 390 / 414 / 768 / 1024 px の全幅で、api.html を除く全14ページに横スクロールなし**（`scrollWidth == clientWidth`）
- ヘッダーナビ14項目は「ホーム・ダッシュボード・データ・研究」＋「その他 ▾」に集約。390px でもポップアップは L10–R365（viewport 375）に収まり、画面外へはみ出さない
- wp-001 の**16個の表すべて**が `div.tbl`（overflow-x:auto）で内包。privacy.html も同様
- index の `curl` コードブロックも横スクロールを内包し、ページ全体には波及しない
- 1440px では読み幅を絞った中央寄せ。研究テキストとして妥当で、崩れなし

### 4.2 メタ情報（新規4ページを含む全15ページ）

viewport / canonical / description / og:title / h1 は **dashboard を除く全ページで完備**（D-7 参照）。新規の registry・api・methodology・corrections はいずれも既存ページと同じ構成を守っている。

### 4.3 リンクとハッシュ

- **内部リンク 37本（JS描画後のDOM基準）、切れ 0**
- **表示されている 64桁ハッシュ 13種のうち 11種がサイト上の実ファイルと一致**

| ハッシュ | 対応ファイル | 掲載ページ |
|---|---|---|
| `a5691643…` | docs/kaisoku_v1.0.md | index, rules |
| `8641e2eb…` | docs/wp-001.md（v1.2） | wp-001, verify, registry |
| `7cca6358…` | docs/wp-001_v1.1.md | wp-001, verify |
| `1674ce1f…` | docs/wp-001_v1.0.md | wp-001, verify |
| `7947ce99…` | docs/wp-001.pdf | wp-001, verify |
| `df3ffb9b…` | research/WP-001/code/halflife_audit.py | verify |
| `97254ceb…` | data/episodes/build_episodes.py | verify |
| `e4093f16…` | data/verify/WP-001-claims.json | verify |
| `7a07369f…` | docs/pr-001.md | research, registry |
| `79619a12…` | docs/n-003.md | research, registry |
| `426c93bf…` | docs/methodology_v1.0.md | registry, methodology |

残る2種も正当である。

- `1fdae032…` — OANDA 日足。再配布不可のためサイトに実体なし（前回監査で手元ファイルとバイト一致を確認済み）
- `725759e3…` — data package zip。**実測一致**（D-6）

### 4.4 前回監査の残件

| 前回指摘 | 状態 |
|---|---|
| **N-1** 50%回復線 159.481 / 159.483 の不一致 | ✅ **解消**。dashboard・data.html とも `159.483` に統一。159.481 はサイト上から消滅。corrections.html に「エピソードDB（E6）50%回復線 159.481円 → 159.483円」として理由・影響範囲・回帰検証済みの旨とともに記録 |
| **N-2** `source_commit: "uncommi"` | ✅ **解消**。`"source_commit": null` ＋「未確定時はnull（第15条）」の注記。推奨どおりの処理 |
| **N-4** 直前速度の定義が一意でない | ✅ **解消**。METHOD-v1.0 を新設し、営業日定義（土日を除く週日・国内祝日は算入）を版番号とハッシュつきで固定 |

corrections.html には「WP-001 初期草稿 — PRE-PUBLICATION REVISION」も独立エントリとして掲載されており、前回 C-3 で整理した「公開前改訂と撤回の区別」が記録として定着している。

---

## 5. 修正の優先度

| 優先 | 指摘 | 工数の目安 |
|---|---|---|
| 1 | **D-1** api.html の表を `div.tbl` で包む | HTML 1行 |
| 2 | **D-2** 未認証応答を 401/403 に変更し、api.html に記載 | サーバ側＋文言 |
| 3 | **D-3** Data Package / Data Delivery の呼称統一 | 文言のみ（3ページ） |
| 4 | **D-4** 監査報告の収載とリンク修正 | 要方針判断 |
| 5 | **D-5** レジェンドの折り返し | CSS 1行 |
| 6 | D-6 / D-8 / D-9 | 文言・CSS・設定 |
| 7 | D-7 ダッシュボードの og・h1・フッター | HTML 数行 |

**D-1・D-3・D-5・D-6 は文言と CSS だけで完了し、正本の改版を要さない。**

---

## 附録：本チェックの再現手順

```bash
# 1. 横スクロールの検出（実機幅を iframe で再現）
#    390px の iframe に各ページを読み込み、documentElement の
#    scrollWidth と clientWidth を比較する。
#    → api.html のみ 485 / 371 で不一致

# 2. 表ラッパーの確認
#    各 <table> の parentElement と computed overflow-x を列挙
#    → api.html のみ article / visible。他は div.tbl / auto

# 3. リンク切れ（JS描画後のDOMで実施すること。静的取得では
#    verify・dashboard・data・events・status のリンクを取りこぼす）
#    → 内部リンク37本すべて 200

# 4. ハッシュ突合
for f in docs/kaisoku_v1.0.md docs/wp-001.md docs/wp-001_v1.1.md \
         docs/wp-001_v1.0.md docs/wp-001.pdf docs/pr-001.md docs/n-003.md \
         docs/methodology_v1.0.md data/verify/WP-001-claims.json \
         research/WP-001/code/halflife_audit.py data/episodes/build_episodes.py \
         dist/fxir-data-package-v1.0.zip; do
  echo "$(curl -s https://fxir.jp/$f | sha256sum | cut -d' ' -f1)  $f"
done

# 5. API のステータス
for e in ping events episodes ratecheck calendar registry corrections status; do
  echo "$e -> $(curl -s -o /dev/null -w '%{http_code}' https://fxir.jp/api/v1/$e)"
done   # 期待していた 401 が実際は 503
```

---

**デザイン・整合性チェック DESIGN-2026-08-08 v1.0**
本報告書は AI（Claude, `claude-opus-5`）が生成した。会則第14条により、本報告書の記述はそれ自体を FACT とは扱えない。横スクロール量・HTTP ステータス・ハッシュ・zip 内訳は上記手順により第三者が確認できる。視覚的な評価（窮屈さ、読みやすさ）は検査者の見解である。

**DON'T TRUST, VERIFY.**
