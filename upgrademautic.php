<?php
date_default_timezone_set('America/New_York'); // Change if needed

$mauticPath = __DIR__ . '/mautic';
$composer = __DIR__ . '/composer.phar';
$logsDir = $mauticPath . '/_logs';
$timestamp = date('Ymd_His');
$logFile = "$logsDir/upgrade_$timestamp.log";

// Ensure logs folder exists
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0775, true);
}

// Logging helper
function logLine($line) {
    global $logFile;
    echo $line . PHP_EOL;
    file_put_contents($logFile, "[$line]\n", FILE_APPEND);
}

logLine("🔄 Starting Mautic upgrade at $timestamp...");

// Step 1: Backup composer files
$backupDir = $mauticPath . "/_backup_$timestamp";
mkdir($backupDir, 0775, true);
copy("$mauticPath/composer.json", "$backupDir/composer.json");
copy("$mauticPath/composer.lock", "$backupDir/composer.lock");
logLine("📦 Backed up composer files to $backupDir");

// Step 2: Run Composer Update
logLine("🚀 Running composer update from $composer");
passthru("cd $mauticPath && php $composer update -W --no-dev --ignore-platform-req=ext-sockets", $exitCode);

if ($exitCode !== 0) {
    logLine("❌ Composer update failed. Exit code: $exitCode. Check $logFile for details.");
    exit(1);
}
logLine("✅ Composer update completed");

// Step 3: Clear Cache
logLine("🧹 Clearing Mautic cache...");
passthru("cd $mauticPath && php bin/console cache:clear", $cacheCode);
logLine("🔁 cache:clear exited with code: $cacheCode");

// Step 4: Run DB Migrations
logLine("📦 Running Mautic DB migration...");
passthru("cd $mauticPath && php bin/console doctrine:migrations:migrate --no-interaction", $migrateCode);
logLine("🔁 doctrine:migrate exited with code: $migrateCode");

// Step 5: Final Cleanup
logLine("✅ Upgrade completed. Check full log at: $logFile");
