<?php
declare(strict_types=1);
/* FXIR メール配信 登録受付（ダブルオプトイン）
   会則第39条: 配信はfxir.jpへのCanonical公開が成立した後にのみ発火する。
   本スクリプトは登録のみを扱い、配信は行わない。 */
if (function_exists('mb_language')) { mb_language('ja'); mb_internal_encoding('UTF-8'); }
require_once __DIR__ . '/admin/sub_lib.php';

function back(string $q): void { header('Location: newsletter.html?' . $q); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') back('err=method');
if (trim((string)($_POST['website'] ?? '')) !== '') back('sent=1');        // ハニーポット
$ts = (int)($_POST['ts'] ?? 0);
if ($ts > 0 && time() - $ts < 3) back('err=fast');                          // 3秒未満はbot扱い
if (empty($_POST['agree'])) back('err=agree');                              // 同意必須

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) back('err=input');
if (preg_match('/[\r\n]/', $email)) back('err=input');                      // ヘッダインジェクション防止

$ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? ''));
$rl = __DIR__ . '/api/_rl'; @mkdir($rl, 0775, true);                        // 3通/時/IP
$f = $rl . '/s_' . $ipHash . '_' . date('YmdH');
if ((int)@file_get_contents($f) >= 3) back('err=limit');
@file_put_contents($f, (string)((int)@file_get_contents($f) + 1));

$rec = sub_find($email);
if ($rec && $rec['status'] === 'confirmed') back('sent=1');                 // 既登録は成功を装う（存在の漏洩防止）

$token = bin2hex(random_bytes(16));
sub_upsert([
  'email'        => $email,
  'token'        => $token,
  'status'       => 'pending',
  'created_at'   => gmdate('c'),
  'confirmed_at' => '',
  'ip_hash'      => substr($ipHash, 0, 16),
]);

$url  = 'https://fxir.jp/confirm.php?t=' . $token;
$body = "為替介入研究所（FXIR）のお知らせ配信にご登録いただきありがとうございます。\n"
      . "下記URLを開くと登録が完了します。24時間以内にお願いします。\n\n{$url}\n\n"
      . "このメールに心当たりがない場合は、何もせず破棄してください。登録は完了しません。\n\n"
      . "――――\n運営：AI MQL合同会社 為替介入研究所\ninfo@fxir.jp ／ https://fxir.jp/\n"
      . "配信停止はいつでも各メール末尾のリンクから行えます。\n";
@mail($email, '【FXIR】メール配信の登録確認', $body,
      "From: FXIR <info@fxir.jp>\r\nReply-To: info@fxir.jp\r\nContent-Type: text/plain; charset=UTF-8\r\n");
back('sent=1');
