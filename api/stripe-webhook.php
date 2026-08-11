<?php
require __DIR__ . '/config.php';
if (!BILLING_ENABLED) { http_response_code(503); exit('billing disabled'); }
$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
// 最小署名検証（v1スキームのHMAC照合）
$ok = false;
foreach (explode(',', $sig) as $part) {
  [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
  if ($k === 't') $t = $v;
  if ($k === 'v1' && isset($t)
      && hash_equals(hash_hmac('sha256', "$t.$payload", STRIPE_WEBHOOK_SECRET), $v)) $ok = true;
}
if (!$ok) { http_response_code(400); exit('bad signature'); }
file_put_contents(__DIR__.'/stripe-events.log', gmdate('c')."\t".$payload."\n", FILE_APPEND|LOCK_EX);
http_response_code(200); echo 'ok';
