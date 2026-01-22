<?php
// Force reset of PHP Opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<h1>Opcache CLEARED Successfully!</h1>";
} else {
    echo "<h1>Opcache is NOT enabled or function unavailable.</h1>";
}
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";
