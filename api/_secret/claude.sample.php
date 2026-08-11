<?php
/* api/_secret/claude.php としてコピーし、新しく発行した鍵を入れること。
   このディレクトリは .htaccess と RewriteRule の二重でウェブ配信を拒否している。
   鍵をリポジトリ・zip・クライアントJSに置いてはならない。 */
return [
  'api_key' => 'sk-ant-REPLACE_WITH_A_FRESHLY_ROTATED_KEY',
  'model'   => 'claude-sonnet-5',
  'max_tokens' => 900,
];
