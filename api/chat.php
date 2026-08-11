<?php
declare(strict_types=1);
/* FXIR Evidence Chat — サーバ側プロキシ
 * 鍵は api/_secret/claude.php からのみ読む。クライアントには一切渡さない。
 * 会則: 第8〜11条（証拠階層）／第36条（煽動的表現の禁止）／第42・43条（売買推奨の禁止）
 *       第58・59条（分からないことを分からないと言う）／第14条（AI出力は事実認定ではない）
 */
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function out(array $a, int $code = 200): void { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

/* GET はヘルスチェック。デプロイ確認用。鍵は一切返さない。 */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $cf = __DIR__ . '/_secret/claude.php';
    $kf = __DIR__ . '/_kb/kb.json';
    $ok = false; $model = null;
    if (is_file($cf)) {
        $c = require $cf;
        $k = (string)($c['api_key'] ?? '');
        $ok = ($k !== '' && strpos($k, 'REPLACE') === false);
        $model = (string)($c['model'] ?? 'claude-sonnet-5');
    }
    $n = 0;
    if (is_file($kf)) { $j = json_decode((string)file_get_contents($kf), true); $n = (int)($j['n'] ?? 0); }
    out([
        'service'    => 'fxir-evidence-chat',
        'configured' => $ok,
        'model'      => $ok ? $model : null,
        'kb_chunks'  => $n,
        'curl'       => function_exists('curl_init'),
        'php'        => PHP_VERSION,
        'hint'       => $ok ? 'ready' : 'api/_secret/claude.php を設置し、新しい鍵を入れてください',
    ]);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(['error' => 'method'], 405);

$cfgFile = __DIR__ . '/_secret/claude.php';
if (!is_file($cfgFile)) out(['error' => 'unconfigured', 'message' => 'api/_secret/claude.php が未設置です。'], 503);
$cfg = require $cfgFile;
$KEY = (string)($cfg['api_key'] ?? '');
if ($KEY === '' || strpos($KEY, 'REPLACE') !== false) out(['error' => 'unconfigured'], 503);

$raw = file_get_contents('php://input');
$in  = json_decode((string)$raw, true);
if (!is_array($in)) out(['error' => 'badrequest'], 400);

$q = trim((string)($in['q'] ?? ''));
if ($q === '' || mb_strlen($q) > 500) out(['error' => 'input'], 400);
$lang = in_array(($in['lang'] ?? 'ja'), ['ja', 'en', 'zh'], true) ? $in['lang'] : 'ja';
$hist = is_array($in['history'] ?? null) ? array_slice($in['history'], -6) : [];

/* ── レート制限: 20問/時/IP, 200問/時/全体 ── */
$rl = __DIR__ . '/_rl'; @mkdir($rl, 0775, true);
$ipH = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? ''));
foreach ([[$rl . '/chat_' . $ipH . '_' . date('YmdH'), 20], [$rl . '/chat_all_' . date('YmdH'), 200]] as [$f, $lim]) {
    $n = (int)@file_get_contents($f);
    if ($n >= $lim) out(['error' => 'ratelimit'], 429);
    @file_put_contents($f, (string)($n + 1));
}

/* ── 検索: 文字バイグラム重なりでスコアリング（日本語トークナイザ不要） ── */
function bigrams(string $s): array {
    $s = mb_strtolower(preg_replace('/\s+/u', '', $s));
    $n = mb_strlen($s); $o = [];
    for ($i = 0; $i < $n - 1; $i++) { $o[mb_substr($s, $i, 2)] = true; }
    return array_keys($o);
}
$kbFile = __DIR__ . '/_kb/kb.json';
$kb = is_file($kbFile) ? json_decode((string)file_get_contents($kbFile), true) : ['chunks' => []];
$qb = bigrams($q);
$scored = [];
foreach (($kb['chunks'] ?? []) as $c) {
    $ttl = mb_strtolower($c['title']);
    $txt = mb_strtolower($c['text']);
    $s = 0.0;
    foreach ($qb as $g) {
        $nt = substr_count($ttl, $g);          // 表題一致は強く効かせる
        $nx = substr_count($txt, $g);
        if ($nt) $s += 4.0 + min($nt, 3);
        if ($nx) $s += 1.0 + min($nx, 6) * 0.35;   // 出現回数を上限付きで加点
    }
    if ($s > 0 && $c['kind'] === 'data') $s *= 1.12;
    if ($s > 0) $scored[] = [round($s, 2), $c];
}
usort($scored, fn($a, $b) => $b[0] <=> $a[0]);
$top = array_slice($scored, 0, 8);

$ctx = '';
foreach ($top as [$s, $c]) {
    $ctx .= "\n---\n[" . $c['id'] . "] " . $c['title'] . "\nURL: " . $c['url'] . "\n" . $c['text'] . "\n";
}
if ($ctx === '') $ctx = "\n（該当する資料が見つかりませんでした）\n";

/* ── システムプロンプト: 会則をそのまま制約として与える ── */
$LANGNAME = ['ja' => '日本語', 'en' => 'English', 'zh' => '简体中文'][$lang];
$SYS = <<<TXT
あなたは為替介入研究所（FXIR / fxir.jp）のエビデンス・アシスタントです。日本の通貨当局による為替介入について、一般のトレーダーにも分かる言葉で答えます。

# 絶対に守ること

1. **売買助言をしない。** 「買うべきか」「売るべきか」「損切りはどこか」「エントリーは」といった問いには、売買判断を示してはなりません。「本研究所は売買判断ではなく、介入に関する観測事実を提供します」と述べ、代わりに確認できる事実・過去事例・不確実性を提示してください。運営は金融商品取引業者ではありません。
2. **介入の発生・時期・方向・規模を予測しない。** 「今日介入する？」には予測で答えず、確認可能なものと確認不能なものを分けて示します。
3. **証拠状態を必ず付ける。** すべての事実主張に FACT / CONFIRMED / ESTIMATE / HYPOTHESIS / UNVERIFIED のいずれかを付します。ESTIMATE を FACT として述べることは禁止です。
4. **分からないことは「確認不能」と答える。** これは正式な回答であり、失敗ではありません。推測で埋めないでください。
5. **与えられた資料にない数値を書かない。** 記憶から数値を補完してはなりません。資料になければ「本資料では確認できません」と述べます。
6. **断定的・煽動的な表現を使わない。**「介入確定」「弾切れ」「○円絶対防衛」「円崩壊」などは使用禁止です。
7. 出典には資料の URL をそのまま使います。存在しない URL を作ってはなりません。

# 回答の構造

一般トレーダー向けに、まず短い結論。次に「確認できていること」、次に「まだ分からないこと」。専門用語は最初に一言で説明します。

# 出力形式

次の JSON のみを出力してください。前後に説明やコードフェンスを付けないでください。

{
 "answer": "結論。2〜4文。平易な言葉で。",
 "confirmed": ["確認できていること。各項目の先頭に [FACT] などの状態を置く"],
 "unknown": ["まだ分からないこと。各項目の先頭に [UNVERIFIED] などの状態を置く"],
 "state": "回答全体の証拠状態。CONFIRMED / ESTIMATE / HYPOTHESIS / UNVERIFIED のいずれか",
 "sources": [{"title": "資料名", "url": "URL"}],
 "refused": false
}

各項目は簡潔にしてください。answer は200字以内、confirmed は最大4項目・各100字以内、unknown は最大3項目・各100字以内、sources は最大4件。出力全体で1200字を超えないでください。長くなる場合は重要なものだけ残します。

売買助言を求められた場合は refused を true にし、answer にその旨を書いてください。

回答は必ず {$LANGNAME} で書いてください。
TXT;

$USER = "# 参照できる資料（為替介入研究所の正本および公開データ。ここにない情報は使わないこと）\n{$ctx}\n\n# 利用者の質問\n{$q}";

$msgs = [];
foreach ($hist as $m) {
    $r = ($m['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
    $t = mb_substr((string)($m['text'] ?? ''), 0, 700);
    if ($t !== '') $msgs[] = ['role' => $r, 'content' => $t];
}
$msgs[] = ['role' => 'user', 'content' => $USER];

$payload = json_encode([
    'model' => (string)($cfg['model'] ?? 'claude-sonnet-5'),
    'max_tokens' => (int)($cfg['max_tokens'] ?? 2200),
    'system' => $SYS,
    'messages' => $msgs,
], JSON_UNESCAPED_UNICODE);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_HTTPHEADER => [
        'content-type: application/json',
        'x-api-key: ' . $KEY,
        'anthropic-version: 2023-06-01',
    ],
]);
$res = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($res === false || $code >= 400) {
    @file_put_contents(__DIR__ . '/access.log',
        gmdate('c') . " chat upstream {$code} " . substr((string)$err, 0, 120) . "\n", FILE_APPEND);
    out(['error' => 'upstream', 'status' => $code], 502);
}
$j = json_decode((string)$res, true);
$text = '';
foreach (($j['content'] ?? []) as $blk) { if (($blk['type'] ?? '') === 'text') $text .= $blk['text']; }
$text = trim($text);

/* ── JSON 抽出。max_tokens で切断された場合も可能な限り復元する ── */
function fxir_repair(string $s): ?string {
    $n = strlen($s); $st = []; $inStr = false; $esc = false; $lastGood = 0;
    for ($i = 0; $i < $n; $i++) {
        $c = $s[$i];
        if ($inStr) {
            if ($esc) { $esc = false; }
            elseif ($c === '\\') { $esc = true; }
            elseif ($c === '"') { $inStr = false; $lastGood = $i; }
            continue;
        }
        if ($c === '"') { $inStr = true; }
        elseif ($c === '{' || $c === '[') { $st[] = $c; }
        elseif ($c === '}' || $c === ']') { array_pop($st); $lastGood = $i; }
        elseif ($c === ',' || $c === ':') { $lastGood = $i; }
    }
    $out = $s;
    if ($inStr) { $out .= '"'; }
    // 末尾に中途半端な , や : が残っていれば切り落とす
    $out = preg_replace('/[,:]\s*$/', '', $out);
    $out = preg_replace('/,\s*("[^"]*")?\s*$/', '', $out);
    for ($i = count($st) - 1; $i >= 0; $i--) { $out .= ($st[$i] === '{') ? '}' : ']'; }
    return $out;
}
function fxir_parse(string $t): ?array {
    $t = trim(preg_replace('/```(?:json)?/i', '', $t));
    $s = strpos($t, '{');
    if ($s === false) return null;
    $b = substr($t, $s);
    $j = json_decode($b, true);                       if (is_array($j)) return $j;
    $e = strrpos($b, '}');
    if ($e !== false) { $j = json_decode(substr($b, 0, $e + 1), true); if (is_array($j)) return $j; }
    $r = fxir_repair($b);
    if ($r !== null) { $j = json_decode($r, true);    if (is_array($j)) return $j; }
    return null;
}
$parsed = fxir_parse($text);

/* 修復も失敗した場合、answer だけでも救出する。生JSONは絶対に画面へ出さない。 */
if (!is_array($parsed)) {
    $ans = '';
    if (preg_match('/"answer"\s*:\s*"((?:[^"\\]|\\.)*)"?/u', $text, $m)) {
        $ans = json_decode('"' . $m[1] . '"') ?? $m[1];
    }
    $parsed = [
        'answer'    => $ans !== '' ? $ans : '回答の生成が途中で終了しました。質問を短く区切って、もう一度お試しください。',
        'confirmed' => [], 'unknown' => [], 'state' => 'UNVERIFIED', 'sources' => [],
        'refused'   => false, 'truncated' => true,
    ];
}
if (!isset($parsed['answer']) || trim((string)$parsed['answer']) === '') {
    $parsed['answer'] = '回答を取得できませんでした。もう一度お試しください。';
    $parsed['state']  = 'UNVERIFIED';
}
foreach (['confirmed', 'unknown', 'sources'] as $k) { if (!isset($parsed[$k]) || !is_array($parsed[$k])) $parsed[$k] = []; }

out([
    'ok' => true,
    'result' => $parsed,
    'retrieved' => array_map(fn($x) => ['id' => $x[1]['id'], 'title' => $x[1]['title'],
                                        'url' => $x[1]['url'], 'score' => $x[0]], $top),
    'model' => (string)($cfg['model'] ?? 'claude-sonnet-5'),
    'note' => 'AI生成の回答です。事実認定は一次資料と正本に基づいて各自で検証してください（会則第14条）。',
]);
