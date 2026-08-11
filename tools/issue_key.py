#!/usr/bin/env python3
"""FXIR APIキー発行（SPEC-API v1.1 §二）
使い方: python3 tools/issue_key.py beta|pro [--revoke <prefix>]
平文はこの場で1回だけ表示される。出力のPHPエントリを site/api/keys.php に追記すること。"""
import hashlib, secrets, sys, datetime
if "--revoke" in sys.argv:
    print(f"revoke手順: keys.php該当エントリの 'status' => 'revoked' に変更（prefix={sys.argv[-1]}）")
    sys.exit(0)
plan = sys.argv[1] if len(sys.argv) > 1 else "beta"
assert plan in ("beta", "pro")
key = f"fxir_{plan}_{secrets.token_urlsafe(32)}"
kh = hashlib.sha256(key.encode()).hexdigest()
now = datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=9))).isoformat(timespec="seconds")
print("=" * 62)
print("APIキー（平文・この1回のみ表示。保存はハッシュのみ）:")
print(f"  {key}")
print("=" * 62)
print("keys.php へ追記するエントリ:")
print(f"""  '{kh}' => ['plan' => '{plan}', 'status' => 'active',
     'prefix' => '{key[:12]}', 'created_at' => '{now}'],""")
