#!/bin/sh
# Acceptance v2 — 20260811世代。使い方: sh tools/acceptance_test.sh
D="$(cd "$(dirname "$0")/../site" && pwd)"
( cd "$D" && php -S 127.0.0.1:8123 >/tmp/acc.log 2>&1 & echo $! > /tmp/acc.pid )
B=http://127.0.0.1:8123
n=0
until curl -s -o /dev/null --max-time 1 "$B/index.html"; do
  n=$((n+1)); [ $n -ge 30 ] && { echo "FAIL server起動せず"; exit 1; }; sleep 0.2
done
fail=0
for f in $(cd "$D" && ls *.html) dashboard/index.html; do
  c=$(curl -s -o /dev/null --max-time 5 -w "%{http_code}" "$B/$f")
  [ "$c" = "200" ] || { echo "FAIL 200 $f ($c)"; fail=1; }
done
( cd "$D/docs" && sed 's/  #.*$//' HASHES.txt | grep -v '^#' | grep -v '^ *$' | sha256sum -c --quiet ) || { echo "FAIL docs台帳"; fail=1; }
for j in "$D"/data/*.json; do
  python3 -m json.tool "$j" >/dev/null 2>&1 || { echo "FAIL json $j"; fail=1; }
done
c=$(curl -s -o /dev/null -w "%{http_code}" "$B/contact.php")
[ "$c" = "302" ] || { echo "FAIL contact.php GET ($c)"; fail=1; }
kill "$(cat /tmp/acc.pid)" 2>/dev/null
if [ "$fail" = "0" ]; then echo "ACCEPTANCE v2: PASS（全HTML 200・docs台帳全行・JSON全parse・contact防御）"; else echo "ACCEPTANCE v2: FAIL"; exit 1; fi
