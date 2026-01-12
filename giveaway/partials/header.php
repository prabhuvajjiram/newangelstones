<?php
require_once __DIR__ . '/../helpers.php';
$cfg = giveaway_config();
$page_title = $page_title ?? 'Giveaway';
?><!DOCTYPE HTML>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($page_title) ?></title>

  <!-- Site theme CSS -->
  <link rel="stylesheet" href="<?= h(asset_url('css/critical.min.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('css/critical-mobile.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('css/hamburger.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('css/promotion-banner.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('css/style.min.css')) ?>">

    <link rel="stylesheet" href="<?= h(asset_url('css/inline-fixes.css')) ?>">
    <!-- Accessible dark theme for giveaway form -->
    <link rel="stylesheet" href="<?= h(asset_url('css/giveaway-accessible-dark.css')) ?>">

  <!-- Giveaway page styles (scoped) -->
  <style>
    .giveaway-wrap { max-width: 980px; margin: 120px auto 80px; padding: 0 18px; }
    .giveaway-card { background: rgba(30,30,30,.72); border: 1px solid rgba(255,255,255,.10); border-radius: 16px; padding: 22px; box-shadow: 0 10px 35px rgba(0,0,0,.35); }
    .giveaway-top { display:flex; gap:16px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; }
    .giveaway-badge { display:inline-block; padding:6px 12px; border-radius:999px; background: rgba(197,169,114,.18); border:1px solid rgba(197,169,114,.35); color:#e8d7ad; font-weight:600; font-size:13px; letter-spacing:.3px; }
    .giveaway-small { opacity:.85; font-size: 14px; line-height: 1.4; }
    .giveaway-hero { display:flex; gap:18px; flex-wrap:wrap; align-items:stretch; margin-top:18px; }
    .giveaway-img { flex: 0 0 340px; max-width: 100%; border-radius: 14px; overflow:hidden; border:1px solid rgba(255,255,255,.10); background: rgba(0,0,0,.25); }
    .giveaway-img img { width:100%; height:auto; display:block; }
    .giveaway-form { margin-top: 18px; display:grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .giveaway-form .full { grid-column: 1 / -1; }
    .giveaway-label { display:block; margin: 10px 0 6px; font-size: 14px; letter-spacing: .02em; color: rgba(255,255,255,.85); }
    .giveaway-input, .giveaway-select { width:100%; padding: 14px 14px; min-height: 48px; font-size: 16px; border-radius: 10px; border:1px solid rgba(255,255,255,.18); background: rgba(0,0,0,.18); color: #fff; }
    .giveaway-form input.giveaway-input[type="email"] { border:1px solid rgba(255,255,255,.18); background: rgba(0,0,0,.18); -webkit-appearance: none; appearance: none; }
    .giveaway-input:focus, .giveaway-select:focus { outline: 3px solid rgba(197,169,114,.45); outline-offset: 2px; border-color: rgba(197,169,114,.65); }
    .giveaway-actions { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
    .giveaway-btn { display:inline-flex; align-items:center; justify-content:center; min-height: 48px; padding: 12px 18px; border-radius: 999px; background: rgba(197,169,114,.18); border:1px solid rgba(197,169,114,.65); color:#fff; font-size: 16px; cursor:pointer; }
    .giveaway-btn:hover { filter: brightness(1.05); }
    .giveaway-muted { opacity:.75; font-size: 13px; }
    .giveaway-checks { display:flex; gap:14px; flex-wrap:wrap; }
    .giveaway-checks label { display:flex; gap:8px; align-items:center; font-size: 14px; opacity:.9; }
    .giveaway-alert { border-radius: 12px; padding: 12px 14px; border:1px solid rgba(255,255,255,.14); background: rgba(0,0,0,.18); margin-top: 12px; }
    .giveaway-alert.ok { border-color: rgba(50,205,50,.45); }
    .giveaway-alert.err { border-color: rgba(255,99,71,.45); }
    .giveaway-grid { display:grid; grid-template-columns: 1fr 1fr; gap:14px; align-items: start; }
    @media (max-width: 760px) {
      .giveaway-wrap { margin-top: 90px; }
      .giveaway-form { grid-template-columns: 1fr; }
      .giveaway-img { flex-basis: 100%; }
    }
  
    .giveaway-checkbox { width: 18px; height: 18px; accent-color: rgb(197,169,114); }

    @media (max-width: 700px){ .giveaway-grid{ grid-template-columns:1fr; } .giveaway-wrap{ margin-top: 96px; padding: 0 14px; } }

    .giveaway-error{ padding: 12px 14px; border-radius: 12px; background: rgba(180,60,60,.18); border:1px solid rgba(180,60,60,.35); color:#fff; }
</style>
</head>
<body>
  <div class="menu-overlay"></div>
  <header id="as-header">
        <button class="as-nav-toggle" aria-label="Toggle navigation menu">
            <i></i>
        </button>
    </header>

    <nav id="as-nav" role="navigation">
        <div class="nav-header">
            <a href="/#home" class="as-logo">
                <img src="/images/ag_logo.svg" width="200" height="80" alt="Angel Stones">
            </a>
        </div>
        <ul class="nav-menu">
            <li><a href="/#home">Home</a></li>
            <li><a href="/#get-in-touch">Contact</a></li>
</ul>
            <!-- Sidebar Footer -->
        <div class="as-footer">
            <p><small>&copy; 2024 <a href="https://www.theangelstones.com/">Angel Stones</a></small> All Rights Reserved</p>
        </div>
    </nav>
