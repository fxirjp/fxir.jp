<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/sub_lib.php';
$t = (string)($_GET['t'] ?? '');
$ok = false;
if (preg_match('/^[0-9a-f]{32}$/', $t)) { $ok = sub_delete_by_token($t); }
header('Location: newsletter.html?' . ($ok ? 'unsub=1' : 'err=token')); exit;
