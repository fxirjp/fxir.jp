<?php
declare(strict_types=1);
/* FXIR 配信管理（最小構成・当面は無料運用）
   鍵は admin/config.php に置く（.htaccess で配信拒否）。鍵は URL ?k= またはヘッダ X-FXIR-Admin で渡す。 */
require_once __DIR__ . '/sub_lib.php';
$cfgFile = __DIR__ . '/config.php';
$cfg = is_file($cfgFile) ? require $cfgFile : null;
$key = (string)($cfg['key'] ?? '');
$given = (string)($_GET['k'] ?? ($_SERVER['HTTP_X_FXIR_ADMIN'] ?? ''));
if ($key === '' || !hash_equals($key, $given)) {
    header('HTTP/1.1 403 Forbidden'); header('Content-Type: text/plain; charset=UTF-8');
    echo $key === '' ? "admin/config.php が未設置です。config.sample.php を複製して鍵を設定してください。\n"
                     : "403 Forbidden\n";
    exit;
}
$rows = sub_all();
$conf = array_filter($rows, fn($r) => ($r['status'] ?? '') === 'confirmed');
$pend = array_filter($rows, fn($r) => ($r['status'] ?? '') === 'pending');

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="fxir_subscribers_' . gmdate('Ymd') . '.csv"');
    $o = fopen('php://output', 'w');
    fputcsv($o, ['email', 'status', 'created_at', 'confirmed_at']);
    foreach ($conf as $r) fputcsv($o, [$r['email'], $r['status'], $r['created_at'] ?? '', $r['confirmed_at'] ?? '']);
    exit;
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
$k = h($given);
?><!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>配信管理 | FXIR</title>
<style>
:root{--paper:#FAFBF9;--ink:#181B1F;--ink2:#41474F;--rule:#DCE0DB;--ai:#1F4468;--ai-weak:#EDF1F4;
 --mono:ui-monospace,"SF Mono",Menlo,Consolas,monospace;--got:"Hiragino Kaku Gothic ProN","Noto Sans JP",sans-serif}
*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:var(--got);font-size:15px}
.wrap{max-width:980px;margin:0 auto;padding:28px 20px 60px}
h1{font-size:20px;letter-spacing:.04em;margin:0 0 4px}
.sub{font-size:12.5px;color:var(--ink2);margin-bottom:22px;font-family:var(--mono)}
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:22px}
.t{border:1px solid var(--rule);border-radius:3px;padding:12px 14px;background:#fff}
.t .k{font-family:var(--mono);font-size:10.5px;letter-spacing:.12em;color:var(--ink2)}
.t .v{font-size:26px;font-family:var(--mono);margin-top:2px}
table{width:100%;border-collapse:collapse;font-size:13px;background:#fff}
th,td{border-bottom:1px solid var(--rule);padding:7px 9px;text-align:left}
th{font-family:var(--mono);font-size:10.5px;letter-spacing:.08em;color:var(--ink2);font-weight:400;background:var(--ai-weak)}
td.m{font-family:var(--mono);font-size:12px}
.bar{display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap}
.bar a{font-family:var(--mono);font-size:12px;padding:6px 12px;border:1px solid var(--rule);border-radius:2px;
 background:#fff;text-decoration:none;color:var(--ink2)}
.bar a:hover{border-color:var(--ai);color:var(--ai)}
.note{border:1px solid var(--ai);background:var(--ai-weak);border-radius:3px;padding:14px 16px;margin-top:24px;font-size:13px;color:var(--ink2)}
.note b{color:var(--ink)}.note ul{margin:8px 0 0;padding-left:1.15em}.note li{margin-bottom:5px}
.st{font-family:var(--mono);font-size:10.5px;padding:1px 6px;border:1px solid var(--rule);border-radius:2px}
.st.c{border-color:var(--ai);color:var(--ai)}
</style></head><body><div class="wrap">
<h1>配信管理</h1>
<div class="sub">FXIR NEWSLETTER ADMIN ／ noindex ／ 生成 <?= h(gmdate('c')) ?></div>

<div class="tiles">
  <div class="t"><div class="k">CONFIRMED</div><div class="v"><?= count($conf) ?></div></div>
  <div class="t"><div class="k">PENDING</div><div class="v"><?= count($pend) ?></div></div>
  <div class="t"><div class="k">TOTAL</div><div class="v"><?= count($rows) ?></div></div>
</div>

<div class="bar">
  <a href="?k=<?= $k ?>&amp;export=csv">確認済みをCSVで書き出す</a>
  <a href="../newsletter.html">登録ページ</a>
  <a href="../news.html">お知らせ一覧</a>
</div>

<table>
<tr><th>メールアドレス</th><th>状態</th><th>登録</th><th>確認</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td class="m"><?= h((string)$r['email']) ?></td>
<td><span class="st <?= ($r['status'] ?? '') === 'confirmed' ? 'c' : '' ?>"><?= h((string)($r['status'] ?? '')) ?></span></td>
<td class="m"><?= h(substr((string)($r['created_at'] ?? ''), 0, 10)) ?></td>
<td class="m"><?= h(substr((string)($r['confirmed_at'] ?? ''), 0, 10)) ?></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="4" style="color:var(--ink2)">登録はまだありません</td></tr><?php endif; ?>
</table>

<div class="note">
  <b>運用上の制約（会則）</b>
  <ul>
    <li><b>第39条 Canonical Event First</b> — 配信は fxir.jp への公開が完了した後にのみ行う。メールでの先行提供はしない。</li>
    <li><b>第60条</b> — 研究成果は可能な限り検証可能な形式で公開する。配信は利便性の提供であり、内容の独占ではない。</li>
    <li><b>特定電子メール法</b> — ダブルオプトインで取得。全配信に送信者名・住所・配信停止リンクを付す。</li>
    <li><b>個人情報保護法</b> — 利用目的は「お知らせの配信」に限定。第三者提供・広告配信は行わない。退会時は記録を削除する。</li>
    <li>本画面は当面の無料運用向けの最小構成である。課金を伴う配信を開始する場合は、法務確認と SPEC-SAAS の凍結を先行させること。</li>
  </ul>
</div>
</div></body></html>
