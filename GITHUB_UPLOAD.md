# GitHubへのアップロード手順（ブラウザのみ・所要10分）

リポジトリ: https://github.com/fxirjp/fxir.jp

## なぜこれが最優先なのか

当研究所は「**規則が結果より前に存在した**」ことを看板にしている。これを第三者が検証する経路は2つある。

| 経路 | 何を証明するか | 現状 |
|---|---|---|
| **OpenTimestamps** | そのハッシュが、その時刻より前に存在したこと | ✅ 完了（Bitcoinブロック963725・963751に確定） |
| **GitHub** | そのハッシュが、**何の文書のものか** | ❌ **未完了** |

**片方しか成立していない。** 現状、外部の人は「何かがBitcoinに刻まれている」ことは確認できるが、「それが本当にこの事前登録なのか」を確かめられない。この作業でそれが埋まる。

## 現状の実測（2026-08-27）

GitHubは8月中旬で止まっている。中核ファイル27点を突合した結果:

- **一致 6件**（kaisoku・wp-001・pr-001・pr-002・n-003・n-004）
- **未収載 17件** — N-008（v1.0/v1.1）、計算スクリプト、PR-004、PR-005、METHOD v1.1、SPEC-KIJUN v0.3、N-006 v1.1、N-007、wp-001_en、**OTS受領書5件すべて**、.gitattributes
- **内容が古い 4件** — HASHES.txt、registry.json、live.json、corrections.json

**事前登録が1本も入っていない。** 8月に凍結・刻印した研究成果が、公開検証の経路上に存在しない状態である。

---

## 手順0（最重要・必ず先に）— `.gitattributes` を置く

これを先に置かないと、以降のファイルが改行コードを書き換えられ、**掲示ハッシュと一致しなくなる**。当所は同じ原因（C-1）で既に6件の不一致を起こしている。

1. https://github.com/fxirjp/fxir.jp を開く
2. **Add file** → **Create new file**
3. ファイル名に `.gitattributes` と入力
4. 同梱の `.gitattributes` の中身を貼り付け
5. Commit message: `Add .gitattributes: 改行コード自動変換の禁止（ハッシュ保全）`
6. **Commit changes**

## 手順1 — 一括アップロード

1. 同梱zipをPCで展開（`docs/` `data/` `img/` とファイルが入っている）
2. リポジトリのトップで **Add file** → **Upload files**
3. 展開した中身を**フォルダごとドラッグ&ドロップ**（1ファイルずつだと階層が崩れる）
4. Commit message:
   ```
   事前登録・OTS受領書・研究成果の同期（N-008 / PR-004 / PR-005 / METHOD v1.1 / SPEC-KIJUN v0.3）
   ```
5. **Commit changes**

## 手順2 — 確認

アップロード後、**サイトの自己検証ページ**（https://fxir.jp/selfcheck.html）を実行し、一致69・不一致0・未配置0 であることを確認する。

加えて、GitHub側のハッシュが一致するかを確認する。

```bash
for f in docs/n-008.md docs/n008_compute.py docs/pr-004.md docs/pr-005.md docs/method_v1.1_episode_boundary.md; do
  echo -n "$f  "
  curl -sL "https://raw.githubusercontent.com/fxirjp/fxir.jp/main/$f" | sha256sum | cut -c1-16
done
```

**期待値（先頭16桁）**

| ファイル | SHA-256 |
|---|---|
| docs/n-008.md | `ac5b5b9ff60d5362` |
| docs/n008_compute.py | `cf79f0402aaa2c92` |
| docs/pr-004.md | `c7322ffbfec98ea4` |
| docs/pr-005.md | `04b71f6744246179` |
| docs/method_v1.1_episode_boundary.md | `270d598432a8cfab` |

一致しない場合は改行コードが変換されている。`.gitattributes` が先に入っているか確認。

## 手順3 — 誰でも検証できる状態になったことの確認

https://opentimestamps.org/ を開き、**正本と受領書を両方アップロード**する。

| 正本 | 受領書 | 期待される結果 |
|---|---|---|
| `docs/n-008.md` | `docs/n-008.md.ots` | Bitcoinブロック **963751** で確定 |
| `docs/pr-004.md` | `docs/pr-004.md.ots` | ブロック **963725** |
| `docs/pr-005.md` | `docs/pr-005.md.ots` | ブロック **963725** |
| `docs/spec-kijun_v0.3.md` | `docs/spec-kijun_v0.3.md.ots` | ブロック **963725** |
| `docs/method_v1.1_episode_boundary.md` | `docs/method_v1.1_episode_boundary.md.ots` | Pending（8/27刻印・確定待ち） |

**これが通れば、「規則が結果より前に存在した」ことを、当研究所を一切信頼せずに検証できる状態になる。**

---

## 同梱内容（80ファイル）

- `docs/` — 正本の研究文書・仕様・ハッシュ台帳・**OTS受領書6件**
- `data/` — 公開データ（CSV・JSON）
- `img/` — 図
- `selfcheck.html` — 自己検証ページ
- `.gitattributes` — 改行コード保護

すべて本番から取得し、**バンドル内で台帳69件の自己検証が一致69・不一致0・未配置0** であることを確認済み。全ファイルLF。

## 注意
- 認証情報（トークン・パスワード）をチャットに貼らないこと。ブラウザのログイン済みセッションで完結する
- FTPSでのサイト更新とは別作業（GitHubは時間証明の公開、FTPSは表示更新）
