<?php
// FXIR API 設定 — 課金は法務確認完了までfalse固定（SPEC-SAAS v0.2 §2・禁止事項七）
define('BILLING_ENABLED', false);
define('STRIPE_WEBHOOK_SECRET', 'whsec_REPLACE_AFTER_LEGAL_OK');
// packageスラッグ → 配信ファイル（docroot外 or 直リンク拒否ディレクトリに置く）
const PACKAGES = [
  'episodes-v1' => __DIR__ . '/packages/episodes_v1.0.json',
  'cftc-jpy'    => __DIR__ . '/packages/cftc_jpy.csv',
  'h41-foreign' => __DIR__ . '/packages/h41_foreign.csv',
];
const API_RL = ['beta' => 500, 'pro' => 5000];  // 1日あたり
