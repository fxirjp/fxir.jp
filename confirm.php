<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/sub_lib.php';
$t = (string)($_GET['t'] ?? '');
$ok = false;
if (preg_match('/^[0-9a-f]{32}$/', $t)) {
    $r = sub_find_by_token($t);
    if ($r && $r['status'] === 'pending') {
        $r['status'] = 'confirmed'; $r['confirmed_at'] = gmdate('c');
        sub_upsert($r); $ok = true;
    } elseif ($r && $r['status'] === 'confirmed') { $ok = true; }
}
header('Location: newsletter.html?' . ($ok ? 'confirmed=1' : 'err=token')); exit;
