<?php

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/RemoteCompatibilityRepository.php';

use App\RemoteCompatibilityRepository;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || ($_GET['action'] ?? '') !== 'search') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $repo = new RemoteCompatibilityRepository();
    $results = $repo->searchDevices(trim($_GET['q'] ?? ''));
    echo json_encode(['success' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'تعذر إجراء البحث الآن'], JSON_UNESCAPED_UNICODE);
}
