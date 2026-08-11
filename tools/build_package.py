#!/usr/bin/env python3
"""FXIR Data Package v1.0 — 決定的ビルド（同一入力→同一zip・STORED・固定mtime）"""
import hashlib, json, pathlib, zipfile

R = pathlib.Path(__file__).resolve().parents[1]
VER = "1.0"
OUT = R / "site" / "dist"
OUT.mkdir(parents=True, exist_ok=True)
ZIPNAME = f"fxir-data-package-v{VER}.zip"

README = """# FXIR Data Package v{ver}

為替介入研究所（fxir.jp）の公開データ確定版。**本パッケージは無料で恒久公開される。**
有料のData Delivery（準備中・法務確認後）は、同一データのAPI配送・週次更新保証・整形サポートを
提供する「配送形態」であり、内容の先行提供や独占は含まない（会則第60・61条）。

## 内容
- data/episodes_v1.0.json / .csv — 介入エピソードDB E1〜E6（証拠状態・+α判定・50%回復線つき）
- data/events.json — イベント台帳（E6＋レートチェックRC-1〜4、要素別証拠状態）
- data/ratecheck.json — Rate Check Monitor 集計（N-003準拠・記述値）
- data/cftc_jpy.csv — CFTC円建玉 週次系列（研究所稼働後の蓄積分）
- data/h41_foreign.csv — FRB H.4.1 海外勘定 週次系列（同上）
- verify/WP-001-claims.json / N-003-claims.json — 構造化claims（機械監査PASS）
- PACKAGE_MANIFEST.json — 全ファイルのSHA-256

## 検証
各ファイルのSHA-256はPACKAGE_MANIFEST.jsonに記載。zip自体のSHA-256は
https://fxir.jp/dist/fxir-data-package-v{ver}.manifest.json に掲示される。
Don't trust, verify.

## 出典・注意
派生統計の原資料は財務省・FRB・CFTC・FRED・OANDA（詳細は各claims/provenance参照）。
OANDAの価格原系列そのものは再配布ライセンス制約により含まれない（ハッシュで同一性確認可能）。
本データは研究目的で提供され、特定の金融商品の売買を推奨しない。
"""

LICENSE = """FXIR Data Package License (v1.0)

Creative Commons Attribution 4.0 International (CC BY 4.0) に準拠して提供する。
- 表示: 「為替介入研究所（FXIR, fxir.jp）」のクレジットと、可能なら対象バージョンのSHA-256を付すこと。
- 免責: 現状有姿で提供され、正確性・完全性・特定目的適合性を保証しない。
- 本ライセンスはFXIRが生成した派生データ・構造化claimsに適用される。原一次資料の権利は各機関に帰属する。
全文: https://creativecommons.org/licenses/by/4.0/deed.ja
"""

FILES = [  # (zip内パス, 実ファイル)
    ("data/episodes_v1.0.json", R / "data/episodes/episodes_v1.0.json"),
    ("data/episodes_v1.0.csv",  R / "data/episodes/episodes_v1.0.csv"),
    ("data/events.json",        R / "site/data/events.json"),
    ("data/ratecheck.json",     R / "site/data/ratecheck.json"),
    ("data/cftc_jpy.csv",       R / "data/cftc/cftc_jpy.csv"),
    ("data/h41_foreign.csv",    R / "data/h41/h41_foreign.csv"),
    ("verify/WP-001-claims.json", R / "site/data/verify/WP-001-claims.json"),
    ("verify/N-003-claims.json",  R / "site/data/verify/N-003-claims.json"),
]

entries, manifest_files = [], []
for arc, src in FILES:
    b = src.read_bytes()
    entries.append((arc, b))
    manifest_files.append({"path": arc, "sha256": hashlib.sha256(b).hexdigest(), "bytes": len(b)})

pkg_manifest = {"package": "fxir-data-package", "version": VER, "date": "2026-08-08",
                "policy": "無料で恒久公開。 有料Data Deliveryは同一データの配送形態（会則第60・61条）",
                "files": manifest_files}
mb = json.dumps(pkg_manifest, ensure_ascii=False, indent=1).encode()
entries = [("README.md", README.format(ver=VER).encode()),
           ("LICENSE.md", LICENSE.encode()),
           ("PACKAGE_MANIFEST.json", mb)] + entries

zp = OUT / ZIPNAME
with zipfile.ZipFile(zp, "w", zipfile.ZIP_STORED) as z:
    for arc, b in entries:
        zi = zipfile.ZipInfo(arc, date_time=(2026, 8, 8, 0, 0, 0))
        zi.external_attr = 0o644 << 16
        z.writestr(zi, b)

zsha = hashlib.sha256(zp.read_bytes()).hexdigest()
(OUT / f"fxir-data-package-v{VER}.manifest.json").write_text(json.dumps({
    "package": "fxir-data-package", "version": VER, "date": "2026-08-08",
    "zip": ZIPNAME, "zip_sha256": zsha, "zip_bytes": zp.stat().st_size,
    "policy": pkg_manifest["policy"], "files": manifest_files},
    ensure_ascii=False, indent=1), encoding="utf-8")
print(ZIPNAME, zp.stat().st_size, "bytes  sha256:", zsha)
