<?php
declare(strict_types=1);
if (function_exists('mb_language')) { mb_language('ja'); mb_internal_encoding('UTF-8'); }
function s_(string $v, int $n): string { return function_exists('mb_substr') ? mb_substr($v, 0, $n) : substr($v, 0, $n); }
function back(string $q): void { header('Location: contact.html?' . $q); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') back('err=method');
if (trim($_POST['website'] ?? '') !== '') back('sent=1');            // ハニーポット: 成功を装って捨てる
$ts = (int)($_POST['ts'] ?? 0);
if ($ts > 0 && time() - $ts < 3) back('err=fast');                    // 3秒未満の送信はbot扱い
$name  = trim(s_((string)($_POST['name'] ?? ''), 80));
$email = trim(s_((string)($_POST['email'] ?? ''), 120));
$msg   = trim(s_((string)($_POST['message'] ?? ''), 4000));
if ($name === '' || $msg === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) back('err=input');
if (preg_match('/[\r\n]/', $name . $email)) back('err=input');        // ヘッダインジェクション防止
$dir = __DIR__ . '/api/_rl'; @mkdir($dir, 0775, true);                // 5通/時/IP
$f = $dir . '/c_' . hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? '')) . '_' . date('YmdH');
$n = (int)@file_get_contents($f);
if ($n >= 5) back('err=limit');
@file_put_contents($f, (string)($n + 1));
$subject = '[FXIR] お問い合わせ: ' . $name;
$body = "名前: {$name}\nメール: {$email}\n----\n{$msg}\n----\n"
      . 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n"
      . 'UA: ' . s_((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 200) . "\n"
      . '時刻: ' . date('c') . "\n";
$headers = "From: FXIR Web <noreply@fxir.jp>\r\nReply-To: {$email}\r\n";
if (php_sapi_name() === 'cli-server') {                               // ローカル試験時は送信せず記録
    file_put_contents('/tmp/fxir_mail_test.log', $subject . "\n" . $body . "===\n", FILE_APPEND);
    back('sent=1');
}
if (function_exists('mb_send_mail')) {
    back(mb_send_mail('info@fxir.jp', $subject, $body, $headers) ? 'sent=1' : 'err=send');
}
$encSub = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
back(mail('info@fxir.jp', $encSub, $body, $headers) ? 'sent=1' : 'err=send');
