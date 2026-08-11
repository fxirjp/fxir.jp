#!/usr/bin/env python3
"""Episode Profiles v1.2 生成 — レート表はOANDA日足から決定的に算出（再現可能）"""
import csv, json, pathlib, hashlib
R = pathlib.Path(__file__).resolve().parents[1]
CSV = R / "data" / "OANDA_USDJPY_1D.csv"
if not CSV.exists():
    CSV = pathlib.Path("/mnt/project/OANDA_USDJPY_1D.csv")
rows = list(csv.DictReader(open(CSV)))
idx = {r["time"][:10]: i for i, r in enumerate(rows)}
db = json.loads((R/"data/episodes/episodes_v1.0.json").read_text(encoding="utf-8"))
meta = json.loads((R/"data/episodes/profiles_meta.json").read_text(encoding="utf-8"))

def f(x): return float(x)
def day_row(d):
    i = idx[d]; r = rows[i]; prev = f(rows[i-1]["close"])
    o,h,l,c = f(r["open"]), f(r["high"]), f(r["low"]), f(r["close"])
    return f"| {d} | {o:.3f} | {h:.3f} | {l:.3f} | {c:.3f} | {h-l:.3f} | {c-prev:+.3f} |"

def span_days(e):
    s, t = e["span"]["start"], e["span"]["end"]
    return [d for d in idx if s <= d <= t]

# --- v1.2: 前回の同方向（円買い）介入からの間隔（平日数・土日除外・祝日含む・両端不含） ---
import numpy as _np
_first = {"E1":"2022-09-22","E2":"2022-10-21","E3":"2024-04-29","E4":"2024-07-11","E5":"2026-04-30","E6":"2026-07-30"}
_prior = {"E1":"1998-06-17","E2":"2022-09-22","E3":"2022-10-24","E4":"2024-05-01","E5":"2024-07-12","E6":"2026-05-06"}
def _gap(eid):
    a = _np.datetime64(_prior[eid]) + _np.timedelta64(1, "D")
    return int(_np.busday_count(a, _np.datetime64(_first[eid])))
GAPS = {k: _gap(k) for k in _first}

out = ["# Episode Profiles v1.2 — 円買い介入 E1〜E6 個票",
 "",
 "為替介入研究所 ｜ 2026-08-10 ｜ 生成: tools/build_profiles.py（レート表はOANDA日足 SHA-256 "
 f"`{hashlib.sha256(CSV.read_bytes()).hexdigest()[:16]}…` から決定的に算出）",
 "",
 "> 実施日・日次額はE1〜E5が財務省公表で確定（FACT）、E6の日別は2026-11上旬確定。"
 "**時間帯は当局非公表であり、全て報道・市場観測（SUSPECTED上限）**。レート表は全営業日を掲載し、太字装飾は行わない（実施日の断定を避けるため）。"
 "**v1.2追加**: 各エピソードに「前回の同方向介入からの間隔」を掲載する。定義＝前回最終実施日の翌日から当該初回実施日の前日までの平日数（土日除外・祝日含む・両端不含）。実施日は財務省確定日次（FACT）に基づく機械計算であり判断を含まない。", ""]

for eid in ["E1","E2","E3","E4","E5","E6"]:
    e = next(x for x in db["episodes"] if x["id"] == eid)
    m = meta["episodes"][eid]
    hl = e["half_life_bd"]
    out += [f"## {eid}　{e['span']['start']} 〜 {e['span']['end']}", "",
      f"**WP-001変数**: Cost {e['cost_trillion_jpy']['value']}兆円"
      f"（{e['cost_trillion_jpy']['state']}）｜ Impact {e['impact_yen']['value']}円 ｜ "
      f"効率 {e['efficiency']['value']} ｜ 直前速度 {e['pre_speed']['value']} ｜ 50%回復線 {e['threshold_50pct']}円 ｜ "
      + ("半減期 未回復（右打ち切り・観測" + str(hl.get("observed_bd")) + "営業日）" if hl["censored"]
         else f"半減期 {hl['value']}営業日"), "",
      f"**前回の同方向介入からの間隔**: {GAPS[eid]:,}営業日（前回最終実施日 {_prior[eid]}・FACT）"
      + ("　※日本の円買い介入として1998-06-17（財務省歴代実施状況）以来" if eid == "E1" else "")
      + ("　※協調介入としては1998年以来（片山財務大臣談話 2026-08-03）" if eid == "E6" else ""), "",
      "**実施日と日次額**", ""]
    out += [f"- {d}: {amt}（{st}）" for d, amt, st in m["exec_days"]]
    out += ["", "**当時のレート（期間全営業日・円）**", "",
      "| 日付 | 始値 | 高値 | 安値 | 終値 | 日中値幅 | 終値前日比 |",
      "|---|---|---|---|---|---|---|"]
    out += [day_row(d) for d in span_days(e)]
    out += ["", "**時間帯（報道・市場観測）**", ""]
    out += [f"- {d} {t} — {desc}〔{st}〕" if desc else f"- {d} {t}〔{st}〕"
            for d, t, desc, st in m["times"]]
    out += ["", "**特性**", ""]
    out += [f"- {tr}" for tr in m["traits"]]
    out += [""]

out += ["---", "",
 "**再現**: `python3 tools/build_profiles.py` が本文書を再生成する。レート値の検証は日足CSVのハッシュ照合による（verify.html）。",
 "**出典**: 財務省 外国為替平衡操作の実施状況（日次・月次）／WP-001 §2-3／N-003・N-004／各時間帯は日経・Bloomberg・Reuters等の当時報道。",
 "",
 "*時間帯・特性中の報道由来情報を事実として扱わないこと。分析および解釈の誤りはすべて筆者に帰する。*"]

dst = R / "research/EP-PROFILES/profiles_v1.2.md"
dst.write_text("\n".join(out), encoding="utf-8")
print(f"wrote {dst} ({len(out)}行)")
