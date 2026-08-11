# -*- coding: utf-8 -*-
"""WP-001 独立再計算・+α基準②③適用スクリプト（会則第17条・第24条）
入力: data/README.md 記載の手順で取得した3ファイル。決定的・乱数不使用。
"""
import pandas as pd, numpy as np

DAILY  = "OANDA_USDJPY_1D.csv"      # TradingView export (JST)
MINUTE = "OANDA_USDJPY__1.csv"      # TradingView export (+09:00)
DGS2   = "data_dgs2.csv"            # FRED fredgraph.csv?id=DGS2

EPISODES = {
    "E1": ["2022-09-22"],
    "E2": ["2022-10-21", "2022-10-24"],
    "E3": ["2024-04-29", "2024-05-01"],
    "E4": ["2024-07-11", "2024-07-12"],
    "E5": ["2026-04-30", "2026-05-04", "2026-05-06"],
    "E6": ["2026-07-30", "2026-07-31", "2026-08-03"],
}
PAPER = {"E1": 1, "E2": 5, "E3": 15, "E4": 431, "E5": 5, "E6": None}  # WP-001 §3.2

d = (pd.read_csv(DAILY, parse_dates=["time"]).rename(columns=str.lower)
       .set_index("time").sort_index()[["open", "high", "low", "close"]].dropna())
g = (pd.read_csv(DGS2, parse_dates=["observation_date"], na_values=".")
       .set_index("observation_date")["DGS2"].dropna())
rv20 = np.log(d["close"]).diff().rolling(20).std() * np.sqrt(252) * 100
p90 = rv20.rolling(252).quantile(0.90)

order = list(EPISODES)
res = {}
print("== A) 半減期 独立再計算 ==")
for i, ep in enumerate(order):
    days = EPISODES[ep]
    t0, t1 = pd.Timestamp(days[0]), pd.Timestamp(days[-1])
    censor = (pd.Timestamp(EPISODES[order[i + 1]][0]) if i + 1 < len(order)
              else d.index[-1] + pd.Timedelta(days=1))
    span = d.loc[t0:t1]
    impact = span["high"].iloc[0] - span["low"].min()   # §2.3
    thr = span["low"].min() + impact * 0.5
    win = d.loc[(d.index > t1) & (d.index < censor)]
    hit = win[win["close"] >= thr]
    attain = hit.index[0] if len(hit) else None
    hl = (win.index.get_loc(attain) + 1) if attain is not None else None
    res[ep] = dict(t1=t1, thr=thr, attain=attain)
    print(f"{ep}: Impact={impact:.2f} thr={thr:.3f} "
          f"半減期={hl if hl else f'未回復(観測{len(win)})'} [紙:{PAPER[ep] or '未回復'}] "
          f"到達={attain.date() if attain is not None else '—'}")

print("\n== B) +α基準②③（生存窓 = 最終介入日翌日〜到達日前日）==")
for ep in order:
    t1, attain = res[ep]["t1"], res[ep]["attain"]
    end = attain if attain is not None else d.index[-1] + pd.Timedelta(days=1)
    ref = g.loc[:t1].iloc[-1]
    w2 = g[(g.index > t1) & (g.index < end)]
    h2 = w2[w2 <= ref - 0.50]
    w3 = rv20[(rv20.index > t1) & (rv20.index < end)]
    h3 = w3[w3 > p90.reindex(w3.index)]
    pre = rv20.loc[:t1].iloc[-1] > p90.loc[:t1].iloc[-1]
    print(f"{ep}: ②ref={ref:.2f} → "
          f"{'発火 ' + str(h2.index[0].date()) if len(h2) else '不発' if len(w2) else '窓なし'} ｜ "
          f"③{'発火 ' + str(h3.index[0].date()) if len(h3) else '不発' if len(w3) else '窓なし'}"
          f"{'（介入時点で既にP90超）' if pre else ''}")

print("\n== C) E6 現況 ==")
t1 = res["E6"]["t1"]
print(f"②基準値 {g.loc[:t1].iloc[-1]:.2f}%（発火線 {g.loc[:t1].iloc[-1]-0.50:.2f}%）現在 {g.iloc[-1]:.2f}%")
print(f"③RV20 {rv20.iloc[-1]:.1f}% / P90 {p90.iloc[-1]:.1f}% ｜ 50%回復線 {res['E6']['thr']:.3f} / 終値 {d['close'].iloc[-1]:.3f}")

print("\n== D) §3.1 分足監査: 2026-07-30 14:15–19:14 JST ==")
m = (pd.read_csv(MINUTE, usecols=["time", "open", "high", "low", "close"])
       .assign(time=lambda x: pd.to_datetime(x["time"])).set_index("time").sort_index())
w = m.loc["2026-07-30 14:15:00+09:00":"2026-07-30 19:14:00+09:00"]
r = (w["high"] - w["low"]) * 100
print(f"1分足 n={len(w)} 平均={r.mean():.1f}pips [紙1.8] 最大={r.max():.1f}pips [紙14.3]")
