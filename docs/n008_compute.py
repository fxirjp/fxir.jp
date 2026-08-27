#!/usr/bin/env python3
"""
n008_compute.py — N-008「ポジション再構築時間（PRT / Position Reconstitution Time）」の機械的算出

本スクリプトは N-008 draft-1 §三の判定規則をそのまま実装したものである。
**凍結後は改変せずに E1〜E5 へ適用する。** 改変が必要になった場合は
版を上げ、改訂理由を版履歴に記録すること（会則第19条）。

入力:
  episodes.csv : episode,first_intervention,last_intervention,price_halflife_bd
  cot.csv      : date,net            （CFTC Legacy 非商業ネット・火曜基準）
出力:
  各エピソードの P_pre / P_post / ΔP / P_1/2 / PPRH(週) / PPRH(暦日) / 戻り率 / 型

用法:
  python3 n008_compute.py episodes.csv cot.csv [--tff tff.csv]
"""
from __future__ import annotations
import csv, sys, argparse
from datetime import date, timedelta

def d(s: str) -> date:
    y, m, dd = s.strip().split('-')
    return date(int(y), int(m), int(dd))

def load_cot(path: str):
    rows = []
    for r in csv.DictReader(open(path, encoding='utf-8')):
        if not r.get('date'):
            continue
        rows.append((d(r['date']), int(str(r['net']).replace(',', ''))))
    rows.sort()
    return rows

def compute(ep, cot, next_pre=None, window=4):
    """§3.2〜3.5 の実装。ep=(name, first, last, price_hl_bd)"""
    name, first, last, price_hl = ep

    # 3.2 Pre基準: 初回介入日より前の最後のCOT
    pre = [x for x in cot if x[0] < first]
    if not pre:
        return dict(episode=name, error='Pre基準となるCOTが存在しない')
    P_pre_date, P_pre = pre[-1]

    # 3.3 介入後極値 P1: 最終介入日以降の最初の観測を含む連続W観測のうち P0 から最大乖離
    post = [x for x in cot if x[0] >= last]
    if not post:
        return dict(episode=name, error='介入後のCOTが存在しない')
    win = post[:window]
    P_post_date, P_post = max(win, key=lambda x: abs(x[1] - P_pre))

    dP = P_post - P_pre
    P_half = P_post - 0.5 * dP

    # 3.4 初到達時間（途中で逆行してもリセットしない）
    # ΔP>0（ショート縮小型）なら P(t) <= P_half、ΔP<0 なら P(t) >= P_half
    def reached(v):
        return v <= P_half if dP > 0 else v >= P_half

    # 3.5 打ち切り: 次エピソードのPre基準日、またはデータ末尾
    limit = next_pre if next_pre else None
    hl_weeks = hl_days = None
    censored = True
    obs = 0
    for dt, v in cot:
        if dt <= P_post_date:
            continue
        if limit and dt > limit:
            break
        obs += 1
        if reached(v):
            hl_weeks = obs
            hl_days = (dt - last).days
            censored = False
            break

    last_obs = [x for x in cot if x[0] > P_post_date and (not limit or x[0] <= limit)]
    latest = last_obs[-1] if last_obs else (P_post_date, P_post)
    recovery = (latest[1] - P_post) / (P_pre - P_post) * 100 if P_pre != P_post else float('nan')

    return dict(episode=name,
                P_pre_date=P_pre_date.isoformat(), P_pre=P_pre,
                P_post_date=P_post_date.isoformat(), P_post=P_post,
                dP=dP, P_half=round(P_half, 1),
                prt50_weeks=hl_weeks, prt50_days=hl_days,
                censored=censored, obs_weeks=obs,
                recovery_pct=round(recovery, 2),
                price_hl_bd=price_hl)

def classify(price_hl_days, prt50_days, censored):
    """§3.6。いずれか打ち切りならType D。Type Cを優先判定"""
    if censored or prt50_days is None or price_hl_days is None:
        return 'D (Censored)'
    if abs(price_hl_days - prt50_days) <= 7:
        return 'C (Synchronous)'
    return 'A (Price-first)' if price_hl_days < prt50_days else 'B (Position-first)'

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('episodes'); ap.add_argument('cot'); ap.add_argument('--tff')
    ap.add_argument('--window', type=int, default=4, help='P1探索窓（主定義=4／感度分析=1）')
    a = ap.parse_args()

    eps = []
    for r in csv.DictReader(open(a.episodes, encoding='utf-8')):
        eps.append((r['episode'], d(r['first_intervention']), d(r['last_intervention']),
                    int(r['price_halflife_bd']) if r.get('price_halflife_bd') else None))
    eps.sort(key=lambda x: x[1])
    cot = load_cot(a.cot)
    tff = load_cot(a.tff) if a.tff else None

    print(f"[P1探索窓 = {a.window} 観測]  ※主定義=4・感度分析=1（N-008 §3.3）")
    print(f"{'Ep':<5}{'P0':>10}{'P1':>10}{'ΔP':>10}{'50%線':>11}"
          f"{'PRT50週':>9}{'暦日':>7}{'R%':>8}{'価格HL':>8}  型")
    for i, ep in enumerate(eps):
        nxt = eps[i + 1][1] if i + 1 < len(eps) else None
        # 次エピソードのPre基準日 = 次の初回介入日より前の最後のCOT基準日
        next_pre = None
        if nxt:
            c = [x[0] for x in cot if x[0] < nxt]
            next_pre = c[-1] if c else None
        r = compute(ep, cot, next_pre, window=a.window)
        if 'error' in r:
            print(f"{r['episode']:<5}  {r['error']}"); continue
        # 価格半減期を暦日へ（営業日→暦日は概算せず、実データがない場合はNone）
        price_days = None
        if r['price_hl_bd'] is not None:
            # 最終介入日から price_hl_bd 営業日後の暦日数（土日のみ除外の概算）
            dt = ep[2]; n = 0
            while n < r['price_hl_bd']:
                dt += timedelta(days=1)
                if dt.weekday() < 5: n += 1
            price_days = (dt - ep[2]).days
        t = classify(price_days, r['prt50_days'], r['censored'])
        w = f"{r['prt50_weeks']}" if r['prt50_weeks'] else f"(>{r['obs_weeks']})"
        dd = f"{r['prt50_days']}" if r['prt50_days'] else '—'
        print(f"{r['episode']:<5}{r['P_pre']:>10,}{r['P_post']:>10,}{r['dP']:>+10,}"
              f"{r['P_half']:>11,.0f}{w:>9}{dd:>7}{r['recovery_pct']:>7.1f}%"
              f"{str(r['price_hl_bd']):>8}  {t}")
    if tff:
        print('\n[補助系列 TFF Leveraged Funds — 判定には用いない]')

if __name__ == '__main__':
    main()
