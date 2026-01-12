<?php
// giveaway/config.php
return [
  'timezone' => 'America/New_York',
  'app_secret' => 'ab83892bfc8b55f2ff66c7d2603596a0fd1e292bee55cf2e35f6eeb7b9e7ec86',

  'db' => [
    'host' => 'localhost',
    'name' => 'angelstones_local',
    'user' => 'root',
    'pass' => '', // Set your MySQL root password if required
    'charset' => 'utf8mb4',
  ],

  // Event slug used by default (QR can add ?event=midatlantic-2026)
  'default_event_slug' => 'midatlantic-2026',

  // Admin login for /giveaway/admin
  'admin' => [
    'username' => 'admin',
    // Generate a bcrypt hash (PHP): password_hash('YourStrongPassword', PASSWORD_BCRYPT)
    'password_hash' => '$2y$12$NzyQ3hyI6VCNn3kjUj1Mw.9Xithl3dEGTj7eUKTnzmkS40N62OIxK',
  ],

  // Basic rate limiting (per IP)
  'rate_limit' => [
    'max_per_minute' => 8,
    'max_per_hour' => 60,
  ],

  // Asset base path (adjust if your site lives in a subfolder)
  'site' => [
    'asset_base' => '/',   // e.g. '/' or '/subdir/'
  ],
];
