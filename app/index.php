<?php
// Angel Granites Smartlink
// Place this file at /app/index.php (e.g., https://theangelstones.com/app/)
// Detects platform and redirects to the right store. Falls back to a tiny landing page on desktop.
// Any query string (?src=mautic&campaign=123) will be appended to the outgoing store URL for basic attribution.

$APP_STORE = 'https://apps.apple.com/us/app/angel-granites/id6748974666';
$PLAY_STORE = 'https://play.google.com/store/apps/details?id=com.angelgranites.app';

function append_query($url) {
    // Sanitize query string to allow only safe characters
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    if (!$qs) return $url;
    // Remove any suspicious characters (allow alphanum, -, _, =, &, %, ., /, ?)
    $qs = preg_replace('/[^a-zA-Z0-9\-_=\&%\.\/\?]/', '', $qs);
    $sep = (parse_url($url, PHP_URL_QUERY) ? '&' : '?');
    return $url . $sep . $qs;
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_ios = preg_match('/iPhone|iPad|iPod/i', $ua);
$is_android = preg_match('/Android/i', $ua);

// QA overrides: ?force=ios | ?force=android | ?force=landing
$force = $_GET['force'] ?? '';
if ($force === 'ios') { $is_ios = true; $is_android = false; }
if ($force === 'android') { $is_android = true; $is_ios = false; }
if ($force === 'landing') { $is_ios = $is_android = false; }

if ($is_ios) {
    $app_url = htmlspecialchars(append_query($APP_STORE));
    $play_url = htmlspecialchars(append_query($PLAY_STORE));
    $custom_scheme = 'angelgranites://';
    // Log analytics (basic file log, can be replaced with external service)
    @file_put_contents(__DIR__ . '/smartlink.log', date('c') . "\tiOS\t" . $_SERVER['REMOTE_ADDR'] . "\t" . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\t" . ($_SERVER['QUERY_STRING'] ?? '') . "\n", FILE_APPEND);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Get the Angel Granites App</title>';
    echo '<body style="background:#f4f4f4;">';
    echo '<main aria-label="App Smartlink" style="min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">';
    echo '<h1 id="main-heading" style="font-size:1.5rem;color:#222;margin-top:2em;">Opening Angel Granites App…</h1>';
    echo '<div role="status" aria-live="polite" style="margin:1em 0;">If nothing happens, <a href="' . $custom_scheme . '" style="color:#007bff;">tap here to open the app</a> or <a href="' . $app_url . '" style="color:#007bff;">get it on the App Store</a>.</div>';
    echo '<div style="margin:2em 0;">';
    echo '<a href="' . $app_url . '" target="_blank" rel="noopener noreferrer" aria-label="Download on the App Store" style="display: inline-flex; align-items: center; height: 40px;">';
    echo '<img alt="Download on the App Store" src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" style="width: 110px; height: 40px; object-fit: contain;" />';
    echo '</a>';
    echo '<span style="width: 10px;"></span>';
    echo '<a href="' . $play_url . '" target="_blank" rel="noopener noreferrer" aria-label="Get it on Google Play" style="display: inline-flex; align-items: center; height: 40px;">';
    echo '<img alt="Get it on Google Play" src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png" style="width: 110px; height: 40px; object-fit: contain;" />';
    echo '</a>';
    echo '</div>';
    echo '<div role="status" aria-live="polite" style="margin:1em 0;">Please wait…</div>';
    echo '</main>';
    echo '<script>';
    echo 'var now = Date.now();';
    echo 'var timeout = setTimeout(function() {';
    echo '  window.location.href = "' . $app_url . '";';
    echo '}, 1500);';
    echo 'window.location = "' . $custom_scheme . '";';
    echo 'setTimeout(function() {';
    echo '  if (Date.now() - now < 1700) {';
    echo '    window.location.href = "' . $app_url . '";';
    echo '  }';
    echo '}, 1400);';
    echo '</script>';
    echo '</body></html>';
    exit;
} elseif ($is_android) {
    $app_url = htmlspecialchars(append_query($APP_STORE));
    $play_url = htmlspecialchars(append_query($PLAY_STORE));
    $custom_scheme = 'intent://#Intent;scheme=angelgranites;package=com.angelgranites.app;end';
    // Log analytics (basic file log, can be replaced with external service)
    @file_put_contents(__DIR__ . '/smartlink.log', date('c') . "\tAndroid\t" . $_SERVER['REMOTE_ADDR'] . "\t" . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\t" . ($_SERVER['QUERY_STRING'] ?? '') . "\n", FILE_APPEND);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Get the Angel Granites App</title>';
    echo '<body style="background:#f4f4f4;">';
    echo '<main aria-label="App Smartlink" style="min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;">';
    echo '<h1 id="main-heading" style="font-size:1.5rem;color:#222;margin-top:2em;">Opening Angel Granites App…</h1>';
    echo '<div role="status" aria-live="polite" style="margin:1em 0;">If nothing happens, <a href="' . $custom_scheme . '" style="color:#007bff;">tap here to open the app</a> or <a href="' . $play_url . '" style="color:#007bff;">get it on Google Play</a>.</div>';
    echo '<div style="margin:2em 0;">';
    echo '<a href="' . $app_url . '" target="_blank" rel="noopener noreferrer" aria-label="Download on the App Store" style="display: inline-flex; align-items: center; height: 40px;">';
    echo '<img alt="Download on the App Store" src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" style="width: 110px; height: 40px; object-fit: contain;" />';
    echo '</a>';
    echo '<span style="width: 10px;"></span>';
    echo '<a href="' . $play_url . '" target="_blank" rel="noopener noreferrer" aria-label="Get it on Google Play" style="display: inline-flex; align-items: center; height: 40px;">';
    echo '<img alt="Get it on Google Play" src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png" style="width: 110px; height: 40px; object-fit: contain;" />';
    echo '</a>';
    echo '</div>';
    echo '<div role="status" aria-live="polite" style="margin:1em 0;">Please wait…</div>';
    echo '</main>';
    echo '<script>';
    echo 'var now = Date.now();';
    echo 'var timeout = setTimeout(function() {';
    echo '  window.location.href = "' . $play_url . '";';
    echo '}, 1500);';
    echo 'window.location = "' . $custom_scheme . '";';
    echo 'setTimeout(function() {';
    echo '  if (Date.now() - now < 1700) {';
    echo '    window.location.href = "' . $play_url . '";';
    echo '  }';
    echo '}, 1400);';
    echo '</script>';
    echo '</body></html>';
    exit;
} else {
  // Desktop/unknown: redirect to main website
  header('Location: https://www.angelgranites.com', true, 302);
  exit;
}
