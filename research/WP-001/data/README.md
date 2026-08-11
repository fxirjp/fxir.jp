# WP-001 データ来歴（会則第25・44〜47条）

原データは配信元の利用規約により本リポジトリでは再配布しない。取得手順とハッシュのみ記録する。

| データ | 取得手順 | SHA-256 |
|---|---|---|
| OANDA USD/JPY 日足（2002-05-06〜2026-08-07, TZ: JST） | TradingView (OANDA:USDJPY, 1D) CSVエクスポート | `1fdae032f3f95f9cb9dbcdb30f3dd80cc2163142be767bc6f278d50697e2879b` |
| OANDA USD/JPY 1分足（2026-07-20 06:04〜2026-08-08 05:59, +09:00明記） | TradingView (OANDA:USDJPY, 1) CSVエクスポート | `b58282d82375fc91acb9116a7d032586c12871924e698fe7db19ec14c89e5fb2` |
| FRED DGS2（米2年国債利回り） | https://fred.stlouisfed.org/graph/fredgraph.csv?id=DGS2 （キー不要） | 取得時に記録 |

**第8条運用注記（2026-08-08決定）**: 価格データは「OANDA feed via TradingView」としての取得を暫定許容し、来歴に経路を明記する。canonicalのOANDA API直接取得への移行をTODOとする。
