<?php
require __DIR__ . '/config.php';
header('X-FXIR-Canonical: https://fxir.jp/');
if (!BILLING_ENABLED) { http_response_code(503);
  exit(json_encode(['error'=>'API not yet open. Research is free at fxir.jp. Paid delivery starts after legal review.'])); }
$key = $_GET['key'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? '');
$pkg = $_GET['package'] ?? '';
$keys = is_file(__DIR__.'/keys.php') ? require __DIR__.'/keys.php' : [];
$plan = $keys[hash('sha256', $key)] ?? null;
if (!$plan) { http_response_code(401); exit(json_encode(['error'=>'invalid key'])); }
$map = PACKAGES; $f = $map[$pkg] ?? null;
if (!$f || !is_file($f)) {
  http_response_code(404); exit(json_encode(['error'=>'unknown package'])); }
// 監査ログ（第45条: 取得日時。キーはハッシュ先頭8桁のみ）
file_put_contents(__DIR__.'/access.log',
  gmdate('c')."\t".substr(hash('sha256',$key),0,8)."\t$pkg\n", FILE_APPEND|LOCK_EX);
header('Content-Type: '.(str_ends_with($f,'.json')?'application/json':'text/csv').'; charset=utf-8');
header('X-Content-SHA256: '.hash_file('sha256',$f));
readfile($f);
