<?php
// Angel Granites Smartlink
// Place this file at /app/index.php (e.g., https://theangelstones.com/app/)
// Detects platform and redirects to the right store. Falls back to a tiny landing page on desktop.
// Any query string (?src=mautic&campaign=123) will be appended to the outgoing store URL for basic attribution.

$APP_STORE = 'https://apps.apple.com/us/app/angel-granites/id6748974666';
$PLAY_STORE = 'https://play.google.com/store/apps/details?id=com.angelgranites.app';

function append_query($url) {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    if (!$qs) return $url;
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
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Get the Angel Granites App</title>';
    echo '<body style="background:#f4f4f4;">';
    echo '<!-- App Store Badges -->';
    echo '<div style="position: fixed; bottom: 15px; right: 60px; z-index: 1000; display: flex; align-items: center; background: rgba(42, 42, 42, 0.9); padding: 12px 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1);">';
    echo '<a href="' . $app_url . '" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; height: 40px;">';
    echo '<img alt="Download on the App Store" src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" style="width: 110px; height: 40px; object-fit: contain;" />';
    echo '</a>';
    echo '<div style="width: 10px;"></div>';
    echo '<a href="' . $play_url . '" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; height: 40px;">';
    echo '<img alt="Get it on Google Play" src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png" style="width: 110px; height: 40px; object-fit: contain;" />';
    echo '</a>';
    echo '</div>';
    echo '<script>setTimeout(function(){window.location.href="' . $app_url . '";},500);</script>';
    echo '</body></html>';
    exit;
} elseif ($is_android) {
    $app_url = htmlspecialchars(append_query($APP_STORE));
    $play_url = htmlspecialchars(append_query($PLAY_STORE));
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Get the Angel Granites App</title>';
    echo '<body style="background:#f4f4f4;">';
    echo '<!-- App Store Badges -->';
    echo '<div style="position: fixed; bottom: 15px; right: 60px; z-index: 1000; display: flex; align-items: center; background: rgba(42, 42, 42, 0.9); padding: 12px 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1);">';
    echo '<a href="' . $app_url . '" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; height: 40px;">';
    echo '<img alt="Download on the App Store" src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" style="width: 110px; height: 40px; object-fit: contain;" />';
    echo '</a>';
    echo '<div style="width: 10px;"></div>';
    echo '<a href="' . $play_url . '" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; height: 40px;">';
    echo '<img alt="Get it on Google Play" src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png" style="width: 110px; height: 40px; object-fit: contain;" />';
    echo '</a>';
    echo '</div>';
    echo '<script>setTimeout(function(){window.location.href="' . $play_url . '";},500);</script>';
    echo '</body></html>';
    exit;
} else {
  // Desktop/unknown: redirect to main website
  header('Location: https://www.angelgranites.com', true, 302);
  exit;
}
