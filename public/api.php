<?php

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/RemoteCompatibilityRepository.php';

use App\RemoteCompatibilityRepository;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['action'])) {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
    exit;
}

$repo = new RemoteCompatibilityRepository();

if ($_GET['action'] === 'search') {
    $query = trim($_GET['q'] ?? '');
    $results = $repo->searchDevices($query);

    echo json_encode([
        'success' => true,
        'results' => $results,
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'الإجراء غير معروف']);
