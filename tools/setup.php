<?php
// CLI-only setup. Refuses to replace an existing database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$name = $argv[1] ?? 'azurite_rental';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) exit("Use a database name containing letters, digits and underscores.\n");
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';charset=utf8mb4', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$stmt=$pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
$stmt->execute([$name]);
if ($stmt->fetchColumn()) exit("Database already exists. Use database/upgrade.sql for an existing installation, or choose a new name.\n");
$schema=str_replace('azurite_rental', $name, file_get_contents(__DIR__ . '/../database/database.sql'));
$pdo->exec($schema);
echo 'Demo database created: ' . $name . PHP_EOL;
