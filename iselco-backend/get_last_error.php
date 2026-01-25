<?php
$logFile = 'storage/logs/laravel.log';
if (!file_exists($logFile)) {
    echo "Log file not found.";
    exit;
}

$handle = fopen($logFile, 'r');
if (!$handle) exit;

$pos = -1;
$buffer = '';
$lines = [];
$found = false;

// Read backwards
fseek($handle, 0, SEEK_END);
while (ftell($handle) > 0) {
    fseek($handle, -1, SEEK_CUR);
    $char = fgetc($handle);
    fseek($handle, -1, SEEK_CUR);
    
    if ($char === "\n") {
        $lines[] = $buffer;
        if (strpos($buffer, 'local.ERROR') !== false) {
             $found = true;
             // We found the error header, read a bit more for context (stack trace)
             // But since we are reading backwards, the 'context' lines are already in $lines (reversed)
             // or rather, we processed them before? No, we read backwards.
             // The lines AFTER the error in the file are processed BEFORE the error line in this loop.
             // So $lines contains the stack trace (in reverse order).
             break;
        }
        $buffer = '';
    } else {
        $buffer = $char . $buffer;
    }
    
    // Limit to 20kb search
    if (ftell($handle) < filesize($logFile) - 20000) break;
}

if ($found) {
    // Reverse lines back to normal order
    $lines = array_reverse($lines);
    // Print last 30 lines (Error + stack trace)
    echo implode("\n", array_slice($lines, 0, 50));
} else {
    echo "No recent error found in the last 20kb.";
}
fclose($handle);
