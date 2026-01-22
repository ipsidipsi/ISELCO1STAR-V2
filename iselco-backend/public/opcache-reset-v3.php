<?php
// opcache-reset-v3.php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<h1>OPCACHE CLEARED (V3)</h1>";
} else {
    echo "<h1>Opcache Not Enabled</h1>";
}
