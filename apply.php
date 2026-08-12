<?php
declare(strict_types=1);
/* FXIR 利用申請の受付。contact.php と同一の作法（ハニーポット・3秒判定・回数制限）。
   商用は SPEC-COM v0.1 §0 により提供未開始のため、本フォームは申込みの受付ではなく相談の受付とする。 */
if (function_exists('mb_language')) { mb_language('ja'); mb_internal_encoding('UTF-8'); }
function s_(string $v, int $n): string { return function_exists('mb_substr') ? mb_substr($v, 0, $n) : substr($v, 0, $n); }
function back(string $q): void { header('Location: live.html?' . $q . '#apply'); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') back('err=method');
if (trim($_POST['website'] ?? '') !== '') back('sent=1');              // ハニーポット
$ts = (int)($_POST['ts'] ?? 0);
if ($ts > 0 && time() - $ts < 3) back('err=fast');
if (empty($_POST['agree'])) back('err=agree');

$KIND = [
  'free'     => '無料β鍵（個人・研究・報道）',
  'quote'    => '引用・転載の相談',
  'commercial' => '商用利用の相談（提供未開始・法務確認前）',
];
$kind = (string)($_POST['kind'] ?? '');
if (!isset($KIND[$kind])) back('err=input');

$name  = trim(s_((string)($_POST['name'] ?? ''), 80));
$org   = trim(s_((string)($_POST['org'] ?? ''), 120));
$email = trim(s_((string)($_POST['email'] ?? ''), 120));
$use   = trim(s_((string)($_POST['use'] ?? ''), 2000));
$vol   = trim(s_((string)($_POST['volume'] ?? ''), 60));

if ($name === '' || $use === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) back('err=input');
if (preg_match('/[\r\n]/', $name . $email . $org . $vol)) back('err=input');   // ヘッダインジェクション防止

$dir = __DIR__ . '/api/_rl'; @mkdir($dir, 0775, true);                 // 5通/時/IP
$f = $dir . '/a_' . hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? '')) . '_' . date('YmdH');
$n = (int)@file_get_contents($f);
if ($n >= 5) back('err=limit');
@file_put_contents($f, (string)($n + 1));

$subject = '[FXIR] 利用申請: ' . $KIND[$kind] . ' / ' . $name;
$body = "区分: {$KIND[$kind]}\n"
      . "氏名: {$name}\n"
      . "所属: " . ($org !== '' ? $org : '（未記入）') . "\n"
      . "メール: {$email}\n"
      . "想定リクエスト数: " . ($vol !== '' ? $vol : '（未記入）') . "\n"
      . "----\n{$use}\n----\n"
      . 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n"
      . 'UA: ' . s_((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 200) . "\n"
      . '時刻: ' . date('c') . "\n";
$headers = "From: FXIR Web <noreply@fxir.jp>\r\nReply-To: {$email}\r\n";

if (php_sapi_name() === 'cli-server') {
    file_put_contents('/tmp/fxir_mail_test.log', $subject . "\n" . $body . "===\n", FILE_APPEND);
    back('sent=1');
}
if (function_exists('mb_send_mail')) {
    back(mb_send_mail('info@fxir.jp', $subject, $body, $headers) ? 'sent=1' : 'err=send');
}
$encSub = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
back(mail('info@fxir.jp', $encSub, $body, $headers) ? 'sent=1' : 'err=send');
