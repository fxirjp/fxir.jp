# The Reaction Function of FX Intervention Has Two Layers

## — Separating the trigger condition from the execution condition, and evaluating intervention by the half-life of its effect

**Tokachi Uemura** (筆名 / pen name)

Reference translation of v1.3 (2026-08-08). Translated 2026-08-15.

> **This is a reference translation.** The canonical text is Japanese: `wp-001.md` (v1.3), SHA-256 `139025fd6e9584e4d6990e3df6c2080b7c8dcb0d4c564b81a5c5a3e57a17bd2f`. Where this translation and the Japanese canonical text differ, **the Japanese text governs**. Cite the canonical file and its hash, not this page. Numbers, tables and evidence-state labels here are transcribed from the canonical text without alteration.

---

## Abstract

For yen-buying interventions by the Japanese monetary authorities, using Ministry of Finance (MOF) disclosures since 2022 together with daily price data, we measure intervention not by its instantaneous price impact but by the **half-life of its effect**.

There are six intervention episodes (n=6). Half-lives range from 1 to 431 business days, with no clear relationship to the amount deployed. An episode of ¥5.53 trillion produced 431 business days; one of ¥11.73 trillion produced 5.

Having pre-defined a set of post-intervention macro changes (hereafter "+α") and then applied them retrospectively, **the simple hypothesis "if +α arrives, the effect persists" is rejected by counterexample.** For the April–May 2026 episode, a Bank of Japan rate hike did arrive 29 business days later — but the effect had already died 24 business days before it. What distinguishes episodes is not whether +α occurred, but whether +α arrived **within the surviving lifetime** of the effect.

Furthermore, the timing of execution does not coincide with violent price movement. In the five hours before the 30 July 2026 intervention, the average one-minute range was 1.8 pips — the calmest window in the sample. This suggests the **trigger condition** (whether to act) and the **execution condition** (when to act) must be treated as separate reaction functions.

All of this is descriptive observation at n=6, not statistical inference.

**Keywords**: foreign exchange intervention, effect half-life, reaction function, USD/JPY, foreign exchange market operations

---

## 1. The problem

### 1.1 The inconsistency that motivated this paper

At 23:00 JST on 6 August 2026, USD/JPY broke above 158. The following day's high was 158.575. The pair stayed above its 200-day moving average (approximately 158.08) for 22 consecutive hours. No intervention occurred.

The same authorities had intervened on 4 May 2026 near 157.3, buying ¥780.2 billion of yen.

**Authorities who acted at 157 did not act at 158.5.** No model that treats the price level as the primary variable of the reaction function can explain this.

### 1.2 Three existing explanations to be tested

| Explanation | Content |
|---|---|
| A | They are defending a specific price level (e.g. the 200-day moving average) |
| B | They react to disorderly price movement (the authorities' official account) |
| C | Intervention can be identified from the shape of the price series |

### 1.3 Claims of this paper

1. Explanations A, B and C are each inconsistent with the observations
2. Intervention should be evaluated by the **half-life of its effect**, not by price impact
3. The half-life is not explained by the amount deployed
4. The trigger condition and the execution condition should be treated as separate reaction functions

---

## 2. Data and method

### 2.1 Data

| Type | Source | Period |
|---|---|---|
| Intervention amounts (by execution date) | MOF, "Foreign Exchange Intervention Operations" | Sep 2022 – Jun 2026 |
| Intervention amounts (estimated) | Press estimates derived from BOJ current-account "fiscal factors" | Jul – Aug 2026 |
| USD/JPY daily | OANDA | May 2002 – Aug 2026 |
| USD/JPY intraday | OANDA | Jul – Aug 2026 |
| Policy rate | Bank of Japan | — |

Intervention amounts from 30 July 2026 onward are not final until the monthly disclosure of 28 August 2026. This paper uses press estimates and flags every instance where it does so.

### 2.2 Definition of an episode

There are 13 intervention dates since 2022, but many are consecutive:

```
2022/10/21 → 10/24
2024/04/29 → 05/01
2024/07/11 → 07/12
2026/04/30 → 05/04 → 05/06
2026/07/30 → 07/31 → 08/03
```

One cannot separate "the first day's effect persisted" from "the effect was maintained by follow-up rounds." Measured day by day, five cases have an observation window of only 0–2 business days before the next intervention.

We therefore treat consecutive clusters as a single **episode**. Episode boundaries reflect the author's judgment; no objective criterion exists.

### 2.3 Variable definitions

| Variable | Definition |
|---|---|
| **Cost** | Total intervention amount during the episode (¥ trillion) |
| **Impact** | High of the first intervention day − lowest low during the episode (¥) |
| **Efficiency** | Cost ÷ Impact (¥ trillion required to move one yen) |
| **Prior velocity** | Mean absolute value of the **four differences** computed from the five closes of the five business days preceding the first intervention day (¥/day) |
| **Half-life** | Business days from the day after the last intervention day until the **close** first recovers to "lowest low + Impact × 0.5" |

Measurement and adjudication follow METHOD-v1.0 (made retrospectively explicit in v1.3).

The half-life is defined on closes because an intraday-high definition picks up momentary retracements and can score the same event in opposite directions. On 30 July 2026, an intraday-high definition would record 50% recovery within 13 hours, yet the next business day's close was below the intervention day's low.

The observation window is right-censored at the first intervention day of the next episode, or at the end of data (7 August 2026).

---

## 3. Results

### 3.1 Rejecting the existing explanations

**Explanation A (level defence).** On 6–7 August 2026 the pair held above the 200-day moving average for 22 hours with no intervention. Rejected.

**Explanation B (response to disorder).** In the five hours before the 30 July 2026 intervention, hourly ranges were 6.8–37.0 pips; the average one-minute range was 1.8 pips, maximum 14.3 pips — the calmest window in the sample. (Calmness measures depend on the window chosen; an alternative implementation used at audit produced values equivalent to 6.8 / 9.2 pips. Window dependence noted, per R1.) At minimum, the description "they reacted to violent price movement" does not fit this case.

**Explanation C (identification from price shape).**

| Date | Intraday range | Low | Intervention |
|---|---|---|---|
| 2026/04/30 | ¥5.170 | 155.556 | ¥6,278.7bn |
| **2026/05/01** | **¥1.842** | **155.488** | **zero** |
| 2026/05/04 | ¥1.603 | 155.702 | ¥780.2bn |
| 2026/05/05 | ¥0.845 | 157.076 | zero |
| 2026/05/06 | ¥2.908 | 155.032 | ¥4,675.9bn |

1 May, with no intervention, had a wider range and a lower low than 4 May, on which ¥780.2 billion was spent. The low of 30 April, on which ¥6,278.7 billion was spent, was taken out by the market unaided on the next business day.

**Interventions below the trillion-yen scale are indistinguishable from ordinary flow.** Rejected.

### 3.2 Episode-level measurements

| Ep | Period | Business days | Cost | Impact | Efficiency | Prior velocity | Half-life |
|---|---|---|---|---|---|---|---|
| E1 | 2022/09/22 | 1 | ¥2.84tn | ¥5.55 | 0.51 | 0.42 | 1 day |
| E2 | 2022/10/21–24 | 2 | ¥6.35tn | ¥6.41 | 0.99 | 0.35 | 5 days |
| E3 | 2024/04/29–05/01 | 3 | ¥9.79tn | ¥7.23 | 1.35 | 0.88 | 15 days |
| **E4** | **2024/07/11–12** | 2 | **¥5.53tn** | ¥4.40 | 1.26 | 0.34 | **431 days** |
| E5 | 2026/04/30–05/06 | 5 | ¥11.73tn | ¥5.69 | 2.06 | 0.38 | 5 days |
| E6 | 2026/07/30–08/03 | 3 | ¥12.70tn* | ¥8.51 | 1.49 | 0.17 | not recovered (4 days observed) |

Cost, Impact and Efficiency are rounded to two decimals for display; the 50% recovery line is computed from the **unrounded** Impact (METHOD-v1.0 §7; added in v1.3, per R1).
\* Estimated. Final on the monthly disclosure of 28 August 2026.

E6 had been observed for only four business days and was not comparable with the other episodes (windows of 20–464 business days).

> **Translator's note (2026-08-15, display layer only).** E6 has since resolved: the close of 2026-08-13 (159.518) exceeded the pre-registered 50% recovery line of 159.483, giving a half-life of **8 business days** (confirmed against the canonical daily series; 2026-08-12 closed at 159.426, below the line). The canonical v1.3 text above is frozen as of its 2026-08-07 data cut-off and is **not** revised retroactively (Art. 19). The confirmed value will be incorporated in v1.4 after the intervention amount is finalised on 28 August 2026. See the live monitor for the adjudication record and the underlying data.

### 3.3 Correlations

For the five episodes with an observed recovery (E1–E5), Spearman rank correlations:

| Variable | ρ | p |
|---|---|---|
| Prior velocity | −0.359 | 0.553 |
| Cost | +0.205 | 0.741 |
| Efficiency | +0.462 | 0.434 |
| Impact | −0.051 | 0.935 |

**None is significant.** At n=5, no correlation can be claimed.

### 3.4 Analyses retracted before publication

An early draft of this research took the 13 individual intervention dates as the sample and reported a negative correlation between prior velocity and half-life (ρ = −0.555). **This is retracted.**

**Error 1 — improper treatment of right-censoring.** Three observations from 30 July 2026 onward, with at most six business days elapsed, were entered as "over 120 days." This manufactured observations that did not exist.

**Error 2 — lack of independence.** Consecutive intervention days were treated as independent observations. The "prior velocity" of the second day of a back-to-back operation is contaminated by the previous day's intervention.

Re-measured at episode level, the result is §3.3, and the regularity originally reported disappears. The early draft's claim that "more efficient interventions are shorter-lived" likewise loses statistical support.

---

## 4. Pre-registering and testing "+α"

### 4.1 The outlier

In the distribution of §3.2, what stands out is not a correlation but an **outlier**:

```
E1   ¥2.84tn  →    1 day
E2   ¥6.35tn  →    5 days
E3   ¥9.79tn  →   15 days
E4   ¥5.53tn  →  431 days   ←
E5  ¥11.73tn  →    5 days
```

E4's Cost is the second smallest. E5 deployed more than twice as much and lasted one eighty-sixth as long. **Size alone cannot explain the gap between E4 and E5.**

### 4.2 What happened around E4

| Date | Event |
|---|---|
| 2024/07/11–12 | Intervention (¥5.53tn total) |
| 2024/07/31 | Bank of Japan raised the policy rate to 0.25% |
| 2024/08/02 | US payrolls missed; the Sahm rule triggered |
| 2024/08/05 | Nikkei 225 fell 4,451 points (a larger point drop than Black Monday 1987) |

USD/JPY fell from 161.95 to 141.70 over five weeks — roughly 20 yen. The intervention did not itself create those 20 yen; the starting point was the policy change and macro data three weeks later.

### 4.3 The pre-definition

Classifying only long-lived interventions as "those had +α" after the fact would be unfalsifiable. The definition is therefore fixed before the next observation occurs.

> **Definition of "+α" (fixed at publication of this paper)**
>
> Taking the last intervention day as day zero, an episode is classified as "accompanied by +α" if any of the following occurs **before the half-life is reached**:
>
> **① The Bank of Japan raises its policy rate by 25bp or more**
> **② The US 2-year Treasury yield falls 50bp or more from its close on the last intervention day**
> **③ The 20-day realised volatility of USD/JPY exceeds the 90th percentile of the trailing year**

The ordering condition — "before the half-life is reached" — is the essential part. A policy change that occurs after the effect has died does not explain that intervention's persistence.

### 4.4 Result: the simple +α hypothesis is rejected

Applying criterion ① retrospectively:

| Ep | Last intervention day | BOJ hike | Elapsed | Half-life reached | Verdict |
|---|---|---|---|---|---|
| **E4** | 2024/07/12 | 2024/07/31 (to 0.25%) | **13 business days** | 431 business days | **+α present (ordering holds)** |
| **E5** | 2026/05/06 | 2026/06/16 (to 1.00%) | **29 business days** | **2026/05/13 (5 business days)** | **+α absent (ordering fails)** |

**E5 did receive a BOJ rate hike.** But the half-life was reached on 13 May 2026 — the effect had died 24 business days before the hike. Therefore:

> **The proposition "if +α arrives, the intervention effect persists" is falsified by E5.**

What separates E4 from E5 is not the presence of +α but whether +α arrived **within the surviving lifetime** of the effect: 13 business days for E4, 29 for E5, against an E5 effect that lasted 5.

In E4, moreover, the rate hike coincided with deteriorating US payrolls and an unwind of carry trades — a compound environmental change, not a single policy event.

### 4.5 What could be claimed at v1.0

At v1.0, the US 2-year yield and realised-volatility data required for criteria ② and ③ had not been obtained, and the classification of E1–E3 rested on criterion ① alone (the follow-up appears in §4.6). Under that constraint, v1.0 could claim only this:

> **E4's long persistence was likely aided by subsequent macro changes, including the BOJ rate hike 13 business days after the intervention. And the amount deployed alone cannot explain the gap between E4 and E5 (431 business days versus 5).**

That is a hypothesis, not a proof.

### 4.6 Follow-up on criteria ② and ③ (added in v1.1)

After v1.0, US 2-year yields (FRED DGS2) and 20-day realised volatility (daily closes, annualised) were obtained and criteria ②③ applied to E1–E5. The operational details (survival window, baseline definitions) were fixed before adjudication in PR-001.

**Operational details (identical to PR-001; written into the text in v1.2).** The survival window runs from the business day after the last intervention day to the **day before** the half-life is reached (an event on the day of arrival does not count as having occurred while the effect was alive). Business days exclude weekends; Japanese public holidays are included because the price series exists on those days.

| Ep | Half-life | ① BOJ hike | ② DGS2 −50bp | ③ RV20 > P90 | Verdict |
|---|---|---|---|---|---|
| E1 | 1 business day | indeterminate (no window) | no window | no window | **indeterminate** |
| E2 | 5 business days | no | no (window min 4.30 / line 4.00) | no | +α absent |
| E3 | 15 business days | no | no (window min 4.73 / line 4.46) | **fired 2024-05-02** * | **+α present (③ only)** |
| E4 | 431 business days | fired +13 business days | **fired 2024-08-02 (3.88)** | **fired 2024-08-05** | **+α present (①②③)** |
| E5 | 5 business days | ordering fails | no (window min 3.90 / line 3.37) | no | +α absent |

\* For E3, RV20 already exceeded the trailing-year P90 on the last intervention day. Large interventions mechanically raise 20-day realised volatility, so criterion ③ can fire immediately after an intervention — mistaking the consequence of intervention for a tailwind to it. Recorded as a known limitation of criterion ③.

This refines §4.4: E3, with only a single and confounded ③, died in 15 business days, while E4 — with ①②③ concentrated inside three weeks — persisted 431. **What distinguishes episodes is likely not "whether +α occurred" but "whether a compound +α concentrated within the surviving lifetime."** This is a HYPOTHESIS; E6 is the test.

---

## 5. The two-layer model

### 5.1 Failure of the single-layer model

The early draft formalised the reaction function as:

```
yen weakness × velocity above threshold  →  intervene
yen weakness × slow                      →  do nothing
```

This cannot explain 30 July 2026: that episode's prior velocity, 0.17, is the smallest in the sample. "They fired because velocity crossed a threshold" is not available.

### 5.2 Separating trigger from execution

What fits the observations is a two-layer structure:

> **Layer 1 (trigger): decides whether to intervene**
>
> **Layer 2 (execution): decides when to intervene**

| Layer | Presumed monitored variables | Time scale |
|---|---|---|
| Layer 1: trigger | cumulative move, speculative positioning, pass-through to prices, political cost, state of international agreement | weeks–months |
| Layer 2: execution | liquidity, dollar tone, data releases, depth of book, momentary velocity | minutes–hours |

This makes the following sequence coherent: (1) yen weakness accumulated slowly over months, satisfying Layer 1; (2) the authorities had already decided to act; (3) they did not act while prices were moving violently; (4) they chose a calm moment as the execution window (19:15 JST, 30 July 2026; average one-minute range 1.8 pips over the prior five hours).

### 5.3 Compatibility with the official account

Importantly, the two-layer model **does not contradict** the authorities' official account ("responding to excessive volatility"):

```
accumulated excessive movement  →  forms the decision to act (Layer 1)
execution in calm conditions    →  maximises efficiency (Layer 2)
```

The early draft asserted that the official account was a facade and the true logic was its opposite. **That too is retracted.**

"Prior five-business-day price velocity," as measured here, is not the same concept as the authorities' "disorderly." Disorder may also involve depth of book, bid-ask spreads, one-sided order concentration, intraday gaps, implied volatility, fixing-related flows and speculative positioning.

What can be claimed at present is only this:

> **At least as measured by prior five-business-day price velocity, the authorities do not act exclusively at moments of maximum volatility. Having decided to intervene, they may select calm market conditions as the execution window.**

This rests on a single case and requires further observation to generalise.

---

## 6. Discussion

### 6.1 Redefining what intervention does

The contrast between E4 and E5 shows the limits of understanding intervention as a policy that permanently changes the price.

In E4, intervention shook positioning, and within three weeks monetary policy and macro data moved in the same direction. The intervention did not create the 20-yen decline; it is better read as having **prepared a state in which that decline could occur**.

> **Intervention can buy time. But unless the environment — monetary policy, US rates, positioning — follows within that time, persistence is not assured.**

Intervention is thus best positioned as a policy that **temporarily displaces positioning and price formation until fundamentals catch up**.

### 6.2 Institutional simultaneity

Two things happened on 30 July 2026. In the morning, the Cabinet approved the FY2027 budget-request guidelines, creating a "Strong and Prosperous Japan" investment envelope for which requests are to be made "without a request ceiling, including item-only requests, in the amounts required," with multi-year funding and separate management in special accounts under consideration for economic-security priorities. At 19:15 and 22:38 the same day, yen-buying intervention was executed.

Taken together — no request ceiling, item-only requests, separate special-account management — the actual scale may not be discernible from outside even after the aggregate request is published at the end of August. The observational problem is less the increase in scale than the loss of transparency.

That said, the same guidelines also state an intention to move away from reliance on supplementary budgets and to fund standing measures in the initial budget, which is both an entrenchment and an improvement in visibility. And a "request" is not a "decision"; amounts may be cut in review.

The simultaneity is a fact; it does not establish causation.

### 6.3 A reservation about intent

This paper does not claim that the government wants a weak yen. Two explanations generate the same observations:

| ① Deliberate managed devaluation | ② Equilibrium without intent |
|---|---|
| The government prefers a weak yen | No one prefers it |
| Intervention is a speed-control policy | Intervention is the only remaining tool |
| Yen weakness is the objective | Yen weakness is a by-product |

There is material for ②. A ten-trillion-yen intervention is a real cost; one would not incur it by preference. Asking the United States to coordinate carries diplomatic cost. Anti-inflation measures and tax cuts are themselves responses to the costs of a weak yen. And the unrealised gains of the foreign exchange special account are realised only by conducting yen-buying intervention.

A more careful statement:

> **Every instrument for restraining yen weakness is politically or fiscally expensive. Fiscal tightening collides with campaign commitments; rate hikes worsen the fiscal burden and the central bank's P&L; structural reform takes years. Intervention is comparatively cheap, so intervention is what gets used.**

With or without intent, the observed price path is the same.

---

## 7. Limitations

### 7.1 Statistical

- **n=6.** Not a sample from which statistical significance can be claimed. §4 is description, not inference
- **E6 is right-censored.** Four business days of observation is not comparable with the other windows (20–464 business days)
- **The correlation analysis is n=5.** Every p-value exceeds 0.4

### 7.2 Arbitrariness of definitions

- The **50% threshold**, the **close-based criterion** and the **episode boundaries** would each change the results if changed
- **"Prior velocity"** (mean absolute change of the prior five business days' closes) is conceptually different from the authorities' "disorderly"
- **Episode boundaries have no objective criterion.** How many business days constitute a separate episode is the author's judgment (a general rule is to be formalised in METHOD v1.1)

### 7.3 Unverified / not final

- ~~Criteria ② and ③ untested~~ → **tested in v1.1 (§4.6)**. However, E1's survival window is the empty set, so all criteria are indeterminate, and criterion ③ retains the confounding noted in §4.6*. For episodes with half-lives on the order of one business day, the survival window is structurally empty and the +α criteria cannot be applied in principle
- **Intervention amounts from 30 July 2026 are estimates.** Final on the monthly disclosure of 28 August 2026
- **The US side's intervention amount is not final.** Reported at roughly ¥1 trillion in euro-selling / yen-buying, but not confirmed until the New York Fed's quarterly report (late October)
- **The Layer 2 hypothesis rests on a single case**

### 7.4 Confounding

Calm periods may simply be periods of weak trend. Correlation is not causation.

---

## 8. Verification plan

### 8.1 E6 as a natural experiment

E6 (30 July – 3 August 2026) was in progress at the time of writing.

| How E6 develops | Implication for the hypothesis |
|---|---|
| BOJ hikes in September 2026 with the half-life not yet reached | A second E4-type case; supports the ordering-conditional +α hypothesis |
| BOJ does not move in September, yet the half-life reaches tens of business days | **The +α hypothesis is badly damaged** |
| BOJ does not move and the half-life is short | Consistent with the hypothesis (though little new information) |
| The half-life is reached after the BOJ moves | An E5-type case; reinforces the ordering condition |

**Note.** Resolving E6 does not increase the sample. E6 is already inside n=6. n=7 arrives only with the next independent episode. What we wait for next is therefore not n=7 but **the resolution of E6**.

### 8.2 Falsification conditions

| Observation | Verdict |
|---|---|
| Intervention executed in a slow yen-weakening phase while prices are calm | Supports the Layer 2 hypothesis |
| Intervention repeatedly executed at moments of maximum volatility | **Rejects the Layer 2 hypothesis** |
| An episode without +α exceeds 100 business days | **Rejects the observation in §4** |
| A specific price level is defended repeatedly | Explanation A (level defence) revives |

### 8.3 What to measure

When the next intervention occurs, the quantity to measure is not the amount deployed:

1. **Whether the BOJ, the Fed or US macro data ratify the intervention's direction within 30 business days of the last intervention day** (application of the +α criteria)
2. **Business days to 50% recovery from the intervention low** (the measured half-life)
3. **How the correlation between the US 2-year yield and USD/JPY changes around the intervention**

The third measures whether intervention temporarily detached price formation from the rate differential. The point at which that detachment ends is the substantive end of the effect.

### 8.4 Scheduled disclosures

| Timing | Publication | What it settles |
|---|---|---|
| Weekly (Thu) | Fed H.4.1 | Movement in FIMA repo balances |
| Weekly (Fri) | CFTC Commitments of Traders | Rebuilding of yen net shorts |
| Mid-Aug 2026 | BOJ "Summary of Opinions" | Distribution of views on the Policy Board |
| End-Aug 2026 | FY2027 aggregate budget requests | Size of the investment envelope; extent of item-only requests |
| **28 Aug 2026** | **MOF monthly intervention amount** | **E6's Cost becomes final** |
| Early Sep 2026 | MOF international reserves | Decline in foreign currency deposits |
| **Sep 2026** | **BOJ Monetary Policy Meeting** | **Whether E6 is granted a +α** |
| Late Oct 2026 | New York Fed quarterly report | The US side's intervention amount |
| Early Nov 2026 | MOF quarterly daily breakdown | Day-by-day figures for Jul–Sep |

---

## Appendix A: Intervention amounts by date

| Date | Amount | Source |
|---|---|---|
| 2022/09/22 | ¥2,838.2bn | MOF |
| 2022/10/21 | ¥5,620.2bn | MOF |
| 2022/10/24 | ¥729.6bn | MOF |
| 2024/04/29 | ¥5,918.5bn | MOF |
| 2024/05/01 | ¥3,870.0bn | MOF |
| 2024/07/11 | ¥3,167.8bn | MOF |
| 2024/07/12 | ¥2,367.0bn | MOF |
| 2026/04/30 | ¥6,278.7bn | MOF (published 7 Aug 2026) |
| 2026/05/04 | ¥780.2bn | MOF (same) |
| 2026/05/06 | ¥4,675.9bn | MOF (same) |
| 2026/07/30 | approx. ¥6.5tn | press estimate |
| 2026/07/31 | approx. ¥5.2tn | press estimate |
| 2026/08/03 | approx. ¥1.0tn | press estimate |

The ¥6,278.7 billion of 30 April 2026 exceeds the ¥5,918.5 billion of 29 April 2024 and is the largest single-day yen-buying intervention on record.

---

## Appendix B: Day-level analysis (reference)

Half-lives computed by intervention date rather than by episode, right-censored at the first day of the next episode.

| Date | Cost | Impact | Efficiency | Prior velocity | Half-life |
|---|---|---|---|---|---|
| 2022/09/22 | 2.84 | 5.55 | 0.51 | 0.42 | 1 day |
| 2022/10/21 | 5.62 | 5.75 | 0.98 | 0.35 | not recovered (0 days observed) |
| 2022/10/24 | 0.73 | 4.18 | 0.17 | 0.91 | 1 day |
| 2024/04/29 | 5.92 | 5.71 | 1.04 | 0.88 | 1 day |
| 2024/05/01 | 3.87 | 4.99 | 0.78 | 1.61 | 5 days |
| 2024/07/11 | 3.17 | 4.33 | 0.73 | 0.34 | not recovered (0 days observed) |
| 2024/07/12 | 2.37 | 2.09 | 1.13 | 0.93 | 2 days |
| 2026/04/30 | 6.28 | 5.17 | 1.21 | 0.38 | not recovered (1 day observed) |
| 2026/05/04 | 0.78 | 1.60 | 0.49 | 1.33 | 1 day |
| 2026/05/06 | 4.68 | 2.91 | 1.61 | 1.27 | 1 day |
| 2026/07/30 | 6.50* | 5.79 | 1.12 | 0.17 | not recovered (0 days observed) |
| 2026/07/31 | 5.20* | 3.61 | 1.44 | 1.14 | not recovered (0 days observed) |
| 2026/08/03 | 1.00* | 2.66 | 0.38 | 1.63 | 1 day |

\* Estimated.

In 5 of 13 cases the observation window is 0–1 business days and the half-life cannot be measured. This is why §2.2 adopts the episode as the unit.

---

## Sources

- MOF, "Foreign Exchange Intervention Operations"
- MOF, Statement by Finance Minister Katayama (3 August 2026)
- MOF, FY2027 Budget Request Guidelines (Cabinet approval, 30 July 2026)
- Board of Governors of the Federal Reserve System, Statistical Release H.4.1
- Commodity Futures Trading Commission, Commitments of Traders
- Bank of Japan, Monetary Policy Meeting materials
- OANDA (price data)

---

*This paper is an analysis of public disclosures and market data. It does not recommend the purchase or sale of any financial instrument. All errors of analysis and interpretation are the author's own.*

**Revision history of the canonical text** (versions up to v1.1 have no recorded time-of-day; v1.2 onward are recorded in ISO 8601 with +09:00)

- Early draft (2026-08-08, unpublished): Sections 2 and 3 fully revised before publication (right-censoring error; failure of sample independence). Because Article 31 of the institute's rules applies "retraction" to published output, the public series begins at v1.0
- v1.0 (2026-08-08): published version
- **v1.1 (2026-08-08)**: (1) corrected the daily-data period in §2.1 to the actual data extent (from May 2002), recorded as the trigger for introducing Auditor v0.2; (2) added §4.6 with the follow-up on criteria ②③; (3) updated §7.3; (4) made §4.5 explicitly point-in-time. v1.0 preserved as `wp-001_v1.0.md`
- **v1.2 (2026-08-08T15:40+09:00)**: response to external audit AUDIT-2026-08-08. (1) survival-window and business-day definitions written into §4.6 and E1's +α verdict unified as "indeterminate" (H-1, L-2); (2) column name in §3.2 corrected to "business days in period" (M-1); (3) status of the early draft reorganised as "revised before publication" (C-3); (4) half-life units in §4.6 unified to business days (L-1). v1.1 preserved as `wp-001_v1.1.md`
- **v1.3 (2026-08-08T22:37+09:00)**: response to remaining items from audit R1. (1) prior velocity in §2.3 given a unique definition (five closes → four differences); (2) window-dependence note added to the calmness measure in §3.1; (3) note on display rounding of Impact and unrounded threshold computation added to §3.2; (4) compliance with METHOD-v1.0 made explicit; (5) note on formalising grouping added to §7.2. v1.2 preserved as `wp-001_v1.2.md` (deployed as `paper_v1.2.md`)

**Translation note.** This English reference translation was produced on 2026-08-15 from canonical v1.3. It adds one clearly-marked translator's note (§3.2) recording the subsequent resolution of E6; no other content has been added, removed or reordered. Translation errors, if any, do not affect the canonical text.
