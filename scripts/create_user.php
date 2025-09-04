<?php
// scripts/create_user.php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/bootstrap.php';

[$script, $username, $password] = $argv + [null, null, null];
if (!$username || !$password) {
    fwrite(STDERR, "Usage: php scripts/create_user.php username password\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:u, :h)');
    $stmt->execute([':u' => $username, ':h' => $hash]);
    echo "User created: {$username}\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}