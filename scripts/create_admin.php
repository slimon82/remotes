<?php

require __DIR__ . '/../src/Database.php';

use App\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This setup command can only be run from the command line.\n");
}

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php <username> <password>\n");
    exit(1);
}

$username = trim($argv[1]);
$password = $argv[2];
if ($username === '' || strlen($username) > 80 || strlen($password) < 12) {
    fwrite(STDERR, "Username must be 1-80 characters and password must contain at least 12 characters.\n");
    exit(1);
}

try {
    $db = Database::getConnection();
    $stmt = $db->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:username, :password_hash)');
    $stmt->execute([':username' => $username, ':password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Admin account created. Keep the password secret.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, "Could not create admin account. The username may already exist, or database setup is incomplete.\n");
    exit(1);
}
