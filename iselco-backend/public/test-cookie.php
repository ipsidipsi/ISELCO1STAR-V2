<?php
// Force headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Set a test cookie using native PHP
setcookie("test_native_cookie", "works", [
    'expires' => time() + 3600,
    'path' => '/',
    'domain' => '.iselcouno.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);

echo "<h1>Cookie Test</h1>";
echo "<p>Timezone: " . date_default_timezone_get() . "</p>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Cookie 'test_native_cookie' has been set with SameSite=None.</p>";
echo "<p>Please check DevTools > Application > Cookies.</p>";
