<?php
$logFile = 'storage/logs/laravel.log';
if (!file_exists($logFile)) {
    echo "Log file not found.";
    exit;
}

$contents = file_get_contents($logFile);
// output last 5000 chars
echo substr($contents, -5000);
