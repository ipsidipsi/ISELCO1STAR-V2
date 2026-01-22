<?php
// opcache-reset-v2.php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<h1>OPCACHE CLEARED (V2)</h1>";
} else {
    echo "<h1>Opcache Not Enabled</h1>";
}
