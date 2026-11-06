<?php

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/RemoteCompatibilityRepository.php';

use App\RemoteCompatibilityRepository;

$repo = new RemoteCompatibilityRepository();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'save_remote') {
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($brand === '' || $model === '') {
        header('Location: index.php?error=remote_invalid');
        exit;
    }

    $repo->createRemote([
        'brand' => $brand,
        'model' => $model,
        'notes' => $notes,
    ]);

    header('Location: index.php?success=remote_saved');
    exit;
}

if ($action === 'save_device') {
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $compatibilityType = trim($_POST['compatibility_type'] ?? 'مناسب بالكامل');
    $selectedRemoteIds = $_POST['remote_ids'] ?? [];

    if ($brand === '' || $model === '' || empty($selectedRemoteIds)) {
        header('Location: index.php?error=device_invalid');
        exit;
    }

    $deviceId = $repo->createDevice([
        'brand' => $brand,
        'model' => $model,
        'notes' => $notes,
    ]);

    foreach ($selectedRemoteIds as $remoteId) {
        $repo->linkDeviceToRemote((int) $deviceId, (int) $remoteId, $compatibilityType, $notes);
    }

    header('Location: index.php?success=device_saved');
    exit;
}

header('Location: index.php');
