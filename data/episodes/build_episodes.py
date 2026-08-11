# -*- coding: utf-8 -*-
"""介入Episode DB (2022-2026) 生成＋WP-001全指標監査（会則第17・24条）
出力: episodes_v1.0.json / episodes_v1.0.csv
"""
import pandas as pd, numpy as np, json, hashlib, math, pathlib, csv, datetime

DAILY = "/mnt/project/OANDA_USDJPY_1D.csv"
DGS2  = "/home/claude/fxir/data_dgs2.csv"
OUT   = pathlib.Path("/home/claude/repo/fxir.jp/data/episodes")
OUT.mkdir(parents=True, exist_ok=True)

d = (pd.read_csv(DAILY, parse_dates=["time"]).rename(columns=str.lower)
       .set_index("time").sort_index()[["open","high","low","close"]].dropna())
g = (pd.read_csv(DGS2, parse_dates=["observation_date"], na_values=".")
       .set_index("observation_date")["DGS2"].dropna())
rv20 = np.log(d["close"]).diff().rolling(20).std()*np.sqrt(252)*100
p90  = rv20.rolling(252).quantile(0.90)

AMOUNTS = {  # 億円, state, source
 "2022-09-22": (28382,"FACT","財務省"), "2022-10-21": (56202,"FACT","財務省"),
 "2022-10-24": (7296,"FACT","財務省"),  "2024-04-29": (59185,"FACT","財務省"),
 "2024-05-01": (38700,"FACT","財務省"), "2024-07-11": (31678,"FACT","財務省"),
 "2024-07-12": (23670,"FACT","財務省"), "2026-04-30": (62787,"FACT","財務省 2026-08-07公表"),
 "2026-05-04": (7802,"FACT","財務省 同上"), "2026-05-06": (46759,"FACT","財務省 同上"),
 "2026-07-30": (65000,"ESTIMATE","報道推計（日銀当座預金 財政等要因）"),
 "2026-07-31": (52000,"ESTIMATE","報道推計（同上）"),
 "2026-08-03": (10000,"ESTIMATE","報道推計（同上）"),
}
EPS = {"E1":["2022-09-22"], "E2":["2022-10-21","2022-10-24"],
       "E3":["2024-04-29","2024-05-01"], "E4":["2024-07-11","2024-07-12"],
       "E5":["2026-04-30","2026-05-04","2026-05-06"],
       "E6":["2026-07-30","2026-07-31","2026-08-03"]}
BOJ_HIKES = [("2024-07-31","0.25%へ"), ("2026-06-16","1.00%へ")]  # 25bp以上のみ
PAPER = {"E1":(5.55,0.51,0.42,1),"E2":(6.41,0.99,0.35,5),"E3":(7.23,1.35,0.88,15),
         "E4":(4.40,1.26,0.34,431),"E5":(5.69,2.06,0.38,5),"E6":(8.51,1.49,0.17,None)}

def spearman(x, y):
    """average-rank Spearman ρ と t近似p（df=n-2=3 閉形式; scipyがあれば併用検算）"""
    xr, yr = pd.Series(x).rank().values, pd.Series(y).rank().values
    rho = np.corrcoef(xr, yr)[0,1]
    n = len(x); t = rho*math.sqrt((n-2)/(1-rho**2)) if abs(rho)<1 else float("inf")
    # df=3 のt分布CDF閉形式
    def tcdf3(t):
        return 0.5 + (1/math.pi)*(t/(math.sqrt(3)*(1+t*t/3)) + math.atan(t/math.sqrt(3)))
    p = 2*(1-tcdf3(abs(t)))
    return rho, p

order = list(EPS); episodes = []
print("== 監査: WP-001 §3.2 全指標 ==")
for i, ep in enumerate(order):
    days = EPS[ep]
    t0, t1 = pd.Timestamp(days[0]), pd.Timestamp(days[-1])
    censor = pd.Timestamp(EPS[order[i+1]][0]) if i+1 < len(order) else d.index[-1]+pd.Timedelta(days=1)
    span = d.loc[t0:t1]
    impact = round(float(span["high"].iloc[0]-span["low"].min()), 2)
    low_min = float(span["low"].min())
    thr = round(low_min + impact*0.5, 3)
    cost = round(sum(AMOUNTS[x][0] for x in days)/10000, 2)  # 兆円
    eff = round(cost/impact, 2)
    pre = d.loc[d.index < t0]["close"].iloc[-5:]  # 前5営業日の終値5本 → 日次変化4本（監査で確定した実装定義）
    pre_speed = round(float(pre.diff().abs().dropna().mean()), 2)
    win = d.loc[(d.index > t1) & (d.index < censor)]
    hit = win[win["close"] >= thr]
    attain = hit.index[0] if len(hit) else None
    hl = int(win.index.get_loc(attain)+1) if attain is not None else None
    end = attain if attain is not None else d.index[-1]+pd.Timedelta(days=1)
    # +α ①②③
    c1 = next(({"fired":True,"date":dt,"detail":lbl,
                "elapsed_bd":int(len(d.loc[(d.index> t1)&(d.index<=pd.Timestamp(dt))]))}
               for dt,lbl in BOJ_HIKES if t1 < pd.Timestamp(dt) < end), {"fired":False})
    ref = float(g.loc[:t1].iloc[-1]); w2 = g[(g.index>t1)&(g.index<end)]
    h2 = w2[w2 <= ref-0.50]
    c2 = ({"fired":True,"date":str(h2.index[0].date()),"value":float(h2.iloc[0]),"ref":ref}
          if len(h2) else {"fired":False,"ref":ref,
                           "window_min":(float(w2.min()) if len(w2) else None)})
    w3 = rv20[(rv20.index>t1)&(rv20.index<end)]
    h3 = w3[w3 > p90.reindex(w3.index)]
    confounded = bool(rv20.loc[:t1].iloc[-1] > p90.loc[:t1].iloc[-1])
    c3 = ({"fired":True,"date":str(h3.index[0].date()),"rv20":round(float(h3.iloc[0]),1),
           "confounded_by_intervention":confounded}
          if len(h3) else {"fired":False,"confounded_by_intervention":confounded})
    n_fired = sum(x.get("fired",False) for x in (c1,c2,c3))
    window_empty = len(d[(d.index > t1) & (d.index < end)]) == 0
    cls = ("in_progress" if attain is None and ep=="E6" else
           "indeterminate" if window_empty else
           "no_alpha" if n_fired==0 else
           f"alpha_{'triple' if n_fired==3 else 'double' if n_fired==2 else 'single'}")
    pi, pe, ps, ph = PAPER[ep]
    print(f"{ep}: Impact {impact}({pi}) 効率 {eff}({pe}) 直前速度 {pre_speed}({ps}) "
          f"半減期 {hl if hl else f'未回復{len(win)}'}({ph or '未回復'})")
    episodes.append({
        "id": ep,
        "days": [{"date":x,"amount_oku_jpy":AMOUNTS[x][0],"state":AMOUNTS[x][1],
                  "source":AMOUNTS[x][2]} for x in days],
        "span": {"start":days[0],"end":days[-1]},
        "cost_trillion_jpy": {"value":cost,
            "state":"ESTIMATE" if ep=="E6" else "FACT",
            "note":"2026-08-28 財務省月次公表で確定予定" if ep=="E6" else None},
        "impact_yen": {"value":impact,"state":"FACT","method":"WP-001 §2.3"},
        "efficiency": {"value":eff,"state":"ESTIMATE" if ep=="E6" else "FACT"},
        "pre_speed": {"value":pre_speed,"state":"FACT","method":"WP-001 §2.3（実装定義: 前5営業日の終値5本・日次変化4本の平均絶対値）"},
        "threshold_50pct": thr,
        "half_life_bd": {"value":hl,"censored":attain is None,
            "observed_bd":int(len(win)) if attain is None else None,
            "attain_date":str(attain.date()) if attain is not None else None,
            "state":"FACT"},
        "alpha": {"c1_boj_hike_25bp":c1,"c2_dgs2_minus50bp":c2,
                  "c3_rv20_p90":c3,"classification":cls,
                  "method":"PR-001 §2 運用細則"},
    })

print("\n== 監査: WP-001 §3.3 Spearman（E1-E5, 対 半減期） ==")
sub = episodes[:5]
hlv = [e["half_life_bd"]["value"] for e in sub]
paper_rho = {"直前速度":(-0.359,0.553),"Cost":(0.205,0.741),"効率":(0.462,0.434),"Impact":(-0.051,0.935)}
audit_rho = {}
for name, vals in [("直前速度",[e["pre_speed"]["value"] for e in sub]),
                   ("Cost",[e["cost_trillion_jpy"]["value"] for e in sub]),
                   ("効率",[e["efficiency"]["value"] for e in sub]),
                   ("Impact",[e["impact_yen"]["value"] for e in sub])]:
    rho, p = spearman(vals, hlv)
    pr, pp = paper_rho[name]
    audit_rho[name] = {"rho":round(rho,3),"p":round(p,3),"paper_rho":pr,"paper_p":pp}
    print(f"{name}: ρ={rho:+.3f} p={p:.3f}  (紙 ρ={pr:+.3f} p={pp})")

db = {
    "title": "為替介入エピソードDB 2022-2026",
    "version": "1.0",
    "generated": datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=9))).isoformat(timespec="seconds"),
    "method_ref": ["WP-001 §2.2-2.3", "PR-001 §2"],
    "price_series": {"source":"OANDA USD/JPY 1D (TradingView経由)","tz":"JST",
        "coverage":"2002-05-06〜"+str(d.index[-1].date()),
        "sha256": hashlib.sha256(open(DAILY,"rb").read()).hexdigest()},
    "rates_series": {"source":"FRED DGS2","fetched":"2026-08-08 JST"},
    "spearman_audit_e1_e5": audit_rho,
    "episodes": episodes,
}
jp = OUT/"episodes_v1.0.json"
jp.write_text(json.dumps(db, ensure_ascii=False, indent=1), encoding="utf-8")
with open(OUT/"episodes_v1.0.csv","w",newline="",encoding="utf-8") as f:
    w = csv.writer(f)
    w.writerow(["id","start","end","n_days","cost_trillion","cost_state","impact_yen",
                "efficiency","pre_speed","threshold","half_life_bd","censored",
                "attain_date","alpha_class","c1","c2","c3"])
    for e in episodes:
        w.writerow([e["id"],e["span"]["start"],e["span"]["end"],len(e["days"]),
            e["cost_trillion_jpy"]["value"],e["cost_trillion_jpy"]["state"],
            e["impact_yen"]["value"],e["efficiency"]["value"],e["pre_speed"]["value"],
            e["threshold_50pct"],e["half_life_bd"]["value"],e["half_life_bd"]["censored"],
            e["half_life_bd"]["attain_date"],e["alpha"]["classification"],
            e["alpha"]["c1_boj_hike_25bp"]["fired"],e["alpha"]["c2_dgs2_minus50bp"]["fired"],
            e["alpha"]["c3_rv20_p90"]["fired"]])
print("\nwrote:", jp, "and .csv")
