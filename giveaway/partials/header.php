<?php require_once __DIR__ . '/../helpers.php'; $cfg=giveaway_config(); ?>
<!DOCTYPE HTML>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($page_title ?? 'Giveaway') ?></title>

  <!-- Match your site theme assets -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="<?= h(asset_url('css/critical.min.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('css/critical-mobile.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('css/style.min.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('css/hamburger.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('css/inline-fixes.css')) ?>">
  <link rel="stylesheet" href="<?= h(asset_url('css/promotion-banner.css')) ?>">

  <style>
    /* Minimal giveaway-specific tweaks; everything else comes from your site CSS */
    .giveaway-wrap { max-width: 980px; margin: 0 auto; padding: 90px 16px 48px; }
    .giveaway-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 20px; }
    .giveaway-hero { display:flex; gap:18px; flex-wrap:wrap; align-items:center; }
    .giveaway-img { width: 320px; max-width: 100%; border-radius: 14px; overflow:hidden; background: rgba(255,255,255,0.06); }
    .giveaway-img img { width:100%; height:auto; display:block; }
    .giveaway-form label { margin-top: 10px; }
    .giveaway-small { opacity:.85; font-size: .95rem; }
    .giveaway-badge { display:inline-block; padding: 6px 10px; border-radius: 999px; background: rgba(255,255,255,0.10); border:1px solid rgba(255,255,255,0.14); }
  </style>
</head>
<body>

<header id="as-header">
  <button class="as-nav-toggle" aria-label="Toggle menu">
    <i></i>
  </button>
</header>

<nav id="as-nav" role="navigation">
  <!-- Simplified nav; links point back to your home sections -->
  <div class="nav-header">
    <a class="as-logo" href="<?= h(asset_url('index.html')) ?>">ANGEL STONES</a>
    <a href="#" class="as-nav-toggle as-nav-close" aria-label="Close menu"><i></i></a>
  </div>
  <ul class="nav-menu">
    <li><a href="<?= h(asset_url('index.html#home')) ?>">Home</a></li>
    <li><a href="<?= h(asset_url('index.html#products')) ?>">Products</a></li>
    <li><a href="<?= h(asset_url('index.html#contact')) ?>">Contact</a></li>
  </ul>
</nav>
<div class="menu-overlay"></div>
