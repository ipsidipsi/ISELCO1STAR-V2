<?php
// Bypass Laravel and test native PHP/Server capabilities
header('Content-Type: application/json');

// 1. Try to set a Native Cookie with SameSite=None
setcookie('native_debug_cookie_v2', 'native_value_v2', [
    'expires' => time() + 3600,
    'path' => '/',
    'domain' => '.iselcouno.com',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);

echo json_encode([
    'php_version' => phpversion(),
    'modules' => [
        'openssl' => extension_loaded('openssl'),
        'session' => extension_loaded('session'),
    ],
    'ini_settings' => [
        'session.cookie_samesite' => ini_get('session.cookie_samesite'),
        'session.cookie_secure' => ini_get('session.cookie_secure'),
        'session.cookie_domain' => ini_get('session.cookie_domain'),
    ],
    'server_vars' => [
        'HTTPS' => $_SERVER['HTTPS'] ?? 'null',
        'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? 'null',
        'HTTP_X_FORWARDED_PROTO' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'null',
    ],
    'received_cookies' => $_COOKIE,
], JSON_PRETTY_PRINT);
