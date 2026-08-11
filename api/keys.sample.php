<?php
// 実鍵は tools/issue_key.py で発行し、このファイルを keys.php にリネームして追記する。
// 旧形式（'hash' => 'plan'）も後方互換で読める。
return [
  // 'sha256hex...' => ['plan' => 'beta', 'status' => 'active',
  //                    'prefix' => 'fxir_beta_Ab', 'created_at' => '2026-08-08T12:00:00+09:00'],
  // 'sha256hex...' => ['plan' => 'commercial', 'status' => 'active',
  //                    'prefix' => 'fxir_com_Xy', 'created_at' => '2026-…',
  //                    'contract' => ['term_start' => '…', 'term_end' => '…']],
  // plan は beta / pro / commercial / commercial_plus。保証は commercial 系のみ（SPEC-COM v0.1）
];
