<?php
declare(strict_types=1);
/* 購読者ストア。JSONL・追記/再書出し。api/_sub は .htaccess で直接アクセス拒否。 */
function sub_path(): string {
    $d = dirname(__DIR__) . '/api/_sub';
    if (!is_dir($d)) { @mkdir($d, 0770, true); }
    return $d . '/subscribers.jsonl';
}
function sub_all(): array {
    $p = sub_path(); if (!is_file($p)) return [];
    $out = [];
    foreach (file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        $r = json_decode($l, true);
        if (is_array($r) && isset($r['email'])) $out[$r['email']] = $r;
    }
    return $out;
}
function sub_write(array $rows): void {
    $p = sub_path(); $tmp = $p . '.tmp';
    $fh = fopen($tmp, 'w'); if (!$fh) return;
    foreach ($rows as $r) fwrite($fh, json_encode($r, JSON_UNESCAPED_UNICODE) . "\n");
    fclose($fh); @chmod($tmp, 0660); @rename($tmp, $p);
}
function sub_upsert(array $rec): void {
    $rows = sub_all(); $rows[$rec['email']] = $rec; sub_write($rows);
}
function sub_find(string $email): ?array { $r = sub_all(); return $r[$email] ?? null; }
function sub_find_by_token(string $t): ?array {
    foreach (sub_all() as $r) if (hash_equals((string)($r['token'] ?? ''), $t)) return $r;
    return null;
}
function sub_delete_by_token(string $t): bool {
    $rows = sub_all(); $hit = false;
    foreach ($rows as $k => $r) if (hash_equals((string)($r['token'] ?? ''), $t)) { unset($rows[$k]); $hit = true; }
    if ($hit) sub_write($rows);
    return $hit;
}
