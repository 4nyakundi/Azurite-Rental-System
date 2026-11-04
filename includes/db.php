<?php
$configFile = __DIR__ . '/config.local.php';
$config = is_file($configFile) ? require $configFile : [];
try {
    $pdo = new PDO(
        'mysql:host=' . (getenv('DB_HOST') ?: ($config['host'] ?? 'localhost')) .
        ';dbname=' . (getenv('DB_NAME') ?: ($config['name'] ?? 'azurite_rental')) . ';charset=utf8mb4',
        getenv('DB_USER') ?: ($config['user'] ?? 'root'),
        getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($config['password'] ?? ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $exception) {
    error_log('Azurite database connection failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('Azurite is temporarily unavailable. Please check the database configuration.');
}
