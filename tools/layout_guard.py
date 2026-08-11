#!/usr/bin/env python3
"""Layout Guard v2.0 — 20260811世代ベースライン（en/zh/adminは対象外）"""
import sys, json, pathlib
root = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else "site")
FORBIDDEN = ["\ufffd", "lorem ipsum", "undefined</", ">NaN<", "PLACEHOLDER", "INSERT_"]
REQUIRED = {
 "index.html": ["CURRENT OBSERVATION", "NEWS — お知らせ", "事実認定は", 'live.html">介入速報'],
 "dashboard/index.html": ["現在の要点", "まだ分かっていないこと", "その日に何が確定するか", "やさしく", "レートチェック監視"],
 "guide.html": ["159.483", "e6.html"],
 "research.html": ["PR-002", "SPEC-VERBAL"],
 "calendar.html": ["第20条"],
 "pr-001.html": ["159.483"], "pr-002.html": ["ILC"], "n-004.html": ["FIMA"],
 "live.html": ["live.json"],
 "episodes.html": ["E6", "半減期", "個票 v1.2"],
 "n-006.html": ["対照群", "Tom/Next", "第40条"], "company.html": ["AI MQL"],
 "contact.html": ["contact.php"], "privacy.html": ["改定履歴"],
}
for i in range(1, 7):
    REQUIRED[f"e{i}.html"] = ["前回の円買い介入からの間隔"]
JSON_REQ = {"registry.json": "entries", "news.json": "entries", "calendar.json": "entries",
            "episodes_summary.json": "episodes", "live.json": "current_episode"}
errors = []
pages = sorted(root.glob("*.html")) + [root / "dashboard/index.html"]
pages = [p for p in pages if p.exists()]
for p in pages:
    t = p.read_text(encoding="utf-8", errors="replace")
    rel = str(p.relative_to(root))
    for f in FORBIDDEN:
        if f in t: errors.append(f"{rel}: 禁止 {f!r}")
    for m in REQUIRED.get(rel, []):
        if m not in t: errors.append(f"{rel}: 必須欠落 {m!r}")
    if "<title>" not in t: errors.append(f"{rel}: title欠落")
for name, key in JSON_REQ.items():
    try:
        d = json.loads((root / "data" / name).read_text(encoding="utf-8"))
        if key not in d: errors.append(f"data/{name}: キー{key}欠落")
    except Exception as e:
        errors.append(f"data/{name}: parse失敗 {e}")
for j in sorted((root / "data").glob("*.json")):
    try: json.loads(j.read_text(encoding="utf-8"))
    except Exception as e: errors.append(f"data/{j.name}: JSON破損 {e}")
if errors:
    print("LAYOUT GUARD v2: FAIL")
    for e in errors: print("  ✗", e)
    sys.exit(1)
req = sum(len(v) for v in REQUIRED.values())
print(f"LAYOUT GUARD v2: PASS（HTML {len(pages)}枚・必須{req}項目・JSON全parse・禁止{len(FORBIDDEN)}種）")
