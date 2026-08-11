<?php
/* FXIR Data API v1 — 検証済みバッチデータ（リアルタイム検知ではない）
   認証: X-FXIR-KEY ヘッダ または ?key=。βは info@fxir.jp で鍵発行（無料）。
   Canonical Event First: 本APIは fxir.jp/data/ の同一ファイルを配信し、先行提供しない。 */
declare(strict_types=1);
require dirname(__DIR__) . '/config.php';
header('X-FXIR-Version: v1');
header('X-FXIR-Canonical: https://fxir.jp/');
header('Access-Control-Allow-Origin: *');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405); header('Content-Type: application/json');
    exit(json_encode(['error' => 'GET only']));
}
$path = trim($_SERVER['PATH_INFO'] ?? '', '/');
$D = dirname(__DIR__, 2) . '/data';           // site/data
$ROUTES = [
    'live'          => "$D/live.json",
    'mof/watch'     => "$D/mof_watch.json",
    'episodes'      => "$D/episodes_summary.json",
    'episodes/full' => "$D/episodes/episodes_v1.0.json",
    'events'        => "$D/events.json",
    'ratecheck'     => "$D/ratecheck.json",
    'calendar'      => "$D/calendar.json",
    'registry'      => "$D/registry.json",
    'corrections'   => "$D/corrections.json",
    'status'        => "$D/status.json",
    'cftc/latest'   => "$D/cftc_latest.json",
    'cftc/history'  => "$D/history/cftc_jpy.csv",
    'h41/latest'    => "$D/h41_latest.json",
    'h41/history'   => "$D/history/h41_foreign.csv",
];
if (preg_match('#^research/([A-Za-z0-9-]+)/claims$#', $path, $m)) {
    $path = 'research/claims';
    $ROUTES['research/claims'] = "$D/verify/{$m[1]}-claims.json";
}
$SCHEMA = ['episodes'=>'fxir.episodes.v1','episodes/full'=>'fxir.episodes_full.v1',
  'events'=>'fxir.events.v1','ratecheck'=>'fxir.ratecheck.v1','calendar'=>'fxir.calendar.v1',
  'registry'=>'fxir.registry.v1','corrections'=>'fxir.corrections.v1','status'=>'fxir.status.v1',
  'cftc/latest'=>'fxir.cftc.v1','h41/latest'=>'fxir.h41.v1','research/claims'=>'fxir.claims.v1'];
if ($path === 'ping') {
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['ok' => true, 'service' => 'fxir-data-api', 'version' => 'v1',
        'time' => gmdate('c'), 'endpoints' => array_keys($ROUTES),
        'note' => '検証済みバッチデータAPI。リアルタイム検知は提供しない（P3・別系統）',
        'auth' => 'X-FXIR-KEY header（?key=はβ限定の非推奨互換。正式版で廃止）. β鍵: info@fxir.jp（無料）',
        'schema_version' => '1.1',
        'research_claims' => '/api/v1/research/{WP-001|N-003}/claims'],
        JSON_UNESCAPED_UNICODE));
}
$key = $_SERVER['HTTP_X_FXIR_KEY'] ?? '';
if ($key === '' && isset($_GET['key'])) { $key = $_GET['key']; header('Deprecation: query-key'); header('X-FXIR-Warn: query-string keys are deprecated; use X-FXIR-KEY header'); }
header('Content-Type: application/json; charset=utf-8');
if ($key === '') {                                   // 資格情報の欠落 = 401（SPEC-API v1.1.2 §六）
    http_response_code(401);
    header('WWW-Authenticate: FXIR-KEY realm="fxir-data-api"');
    exit(json_encode(['error' => 'missing key',
        'how' => 'X-FXIR-KEY ヘッダで送信。β鍵は info@fxir.jp（無料）'], JSON_UNESCAPED_UNICODE));
}
$keys = is_file(dirname(__DIR__) . '/keys.php') ? require dirname(__DIR__) . '/keys.php' : null;
if ($keys === null) {                                // 鍵を提示されたが台帳が未設置（運用者側の一時状態のみ503）
    http_response_code(503);
    exit(json_encode(['error' => 'key store not provisioned (beta setup in progress)'],
        JSON_UNESCAPED_UNICODE));
}
$kh  = hash('sha256', $key);
$rec = $keys[$kh] ?? null;
if (is_string($rec)) { $rec = ['plan' => $rec, 'status' => 'active']; }
if ($rec === null) {
    http_response_code(401);
    header('WWW-Authenticate: FXIR-KEY realm="fxir-data-api"');
    exit(json_encode(['error' => 'invalid key']));
}
if (($rec['status'] ?? 'active') !== 'active') {     // 失効 = 権限なし = 403
    http_response_code(403);
    exit(json_encode(['error' => 'key revoked']));
}
$plan = $rec['plan'];
$limits = defined('API_RL') ? API_RL
    : ['beta' => 500, 'pro' => 5000, 'commercial' => 5000, 'commercial_plus' => 50000];
$lim = $limits[$plan] ?? 500;
$rl  = dirname(__DIR__) . '/_rl';
if (!is_dir($rl)) { mkdir($rl, 0755, true); }
$cf = "$rl/" . substr($kh, 0, 12) . '-' . gmdate('Ymd');
$n  = is_file($cf) ? (int)file_get_contents($cf) : 0;
$reset = (intdiv(time(), 86400) + 1) * 86400;   // 次回リセット（UTC 00:00, epoch秒）
header('X-RateLimit-Reset: ' . $reset);
if ($n >= $lim) {
    http_response_code(429);
    header('Retry-After: ' . ($reset - time()));
    header('X-RateLimit-Limit: ' . $lim);
    header('X-RateLimit-Remaining: 0');
    exit(json_encode(['error' => 'rate limit', 'limit_per_day' => $lim, 'reset_utc_epoch' => $reset]));
}
file_put_contents($cf, (string)($n + 1), LOCK_EX);
$WARRANTY = in_array($plan, ['commercial', 'commercial_plus'], true) ? 'contracted' : 'none';
header('X-FXIR-Plan: ' . $plan);
header('X-FXIR-Warranty: ' . $WARRANTY);   // SPEC-COM v0.1 §5: 無保証であることを応答自身が申告する
header('X-RateLimit-Limit: ' . $lim);
header('X-RateLimit-Remaining: ' . ($lim - $n - 1));
$f = $ROUTES[$path] ?? null;
if (!$f || !is_file($f)) {
    http_response_code(404);
    exit(json_encode(['error' => 'unknown endpoint', 'see' => '/api/v1/ping']));
}
$sha = hash_file('sha256', $f);
header('X-Content-SHA256: ' . $sha);
header('X-FXIR-Content-SHA256: ' . $sha);
header('X-FXIR-Schema-Version: 1.1');
header('ETag: "' . $sha . '"');
header('Cache-Control: public, max-age=60');
file_put_contents(dirname(__DIR__) . '/access.log',
    gmdate('c') . "\t" . substr($kh, 0, 8) . "\tv1/$path\n", FILE_APPEND | LOCK_EX);
if (str_ends_with($f, '.csv')) {
    header('Content-Type: text/csv; charset=utf-8');
    readfile($f); exit;
}
$payload = json_decode(file_get_contents($f), true);
$asof = $payload['generated_at'] ?? ($payload['as_of'] ?? null);
if ($asof) { header('X-FXIR-As-Of: ' . $asof); }
$mf = dirname(__DIR__, 2) . '/dashboard/dashboard-manifest.json';
$commit = is_file($mf) ? (json_decode(file_get_contents($mf), true)['source_commit'] ?? null) : null;
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['meta' => [
    'schema' => $SCHEMA[$path] ?? ('fxir.' . str_replace('/', '.', $path) . '.v1'),
    'generated_at' => $asof,
    'source_commit' => $commit,
    'sha256' => $sha,
    'canonical_url' => 'https://fxir.jp/data/' . substr($f, strlen($D) + 1),
    'plan' => $plan,
    'warranty' => $WARRANTY,
    'terms' => 'https://fxir.jp/commercial.html'],
  'data' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
