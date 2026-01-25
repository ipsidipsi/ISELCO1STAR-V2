<?php
try {
    // Load .env manually to be sure (simple parse)
    $env = file_get_contents(__DIR__ . '/.env');
    preg_match('/DB_HOST=(.*)/', $env, $hostMatches);
    $host = trim($hostMatches[1] ?? '127.0.0.1');
    
    preg_match('/DB_DATABASE=(.*)/', $env, $dbMatches);
    $db = trim($dbMatches[1] ?? 'iselco_star');
    
    preg_match('/DB_USERNAME=(.*)/', $env, $userMatches);
    $user = trim($userMatches[1] ?? 'root');
    
    preg_match('/DB_PASSWORD=(.*)/', $env, $passMatches);
    $pass = trim($passMatches[1] ?? '');
    
    echo "Attempting connection to $host -> $db with user '$user'...\n";
    
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "SUCCESS: Database connection working!\n";
    
    // Check users table count just to be sure
    $stmt = $pdo->query("SELECT count(*) FROM users");
    echo "Users count: " . $stmt->fetchColumn() . "\n";
    
} catch (PDOException $e) {
    echo "ERROR: Connection failed: " . $e->getMessage() . "\n";
}
