<?php

namespace App;

use PDO;

class RemoteCompatibilityRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function searchDevices(string $query): array
    {
        $q = trim($query);

        if ($q === '') {
            return [];
        }

        $sql = "
            SELECT id, brand, model, notes
            FROM devices
            WHERE brand LIKE :q OR model LIKE :q
            ORDER BY brand ASC, model ASC
            LIMIT 10
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':q' => '%' . $q . '%']);

        return $stmt->fetchAll();
    }

    public function getAllRemotes(): array
    {
        $sql = "SELECT * FROM remotes ORDER BY brand ASC, model ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getDeviceById(int $deviceId): ?array
    {
        $sql = "SELECT * FROM devices WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $deviceId]);
        $device = $stmt->fetch();

        return $device ?: null;
    }

    public function getDeviceCompatibility(int $deviceId): array
    {
        $sql = "
            SELECT dr.id, dr.device_id, dr.remote_id, dr.compatibility_type, dr.notes,
                   r.brand AS remote_brand, r.model AS remote_model, r.notes AS remote_notes
            FROM device_remote dr
            INNER JOIN remotes r ON r.id = dr.remote_id
            WHERE dr.device_id = :device_id
            ORDER BY r.brand ASC, r.model ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':device_id' => $deviceId]);

        return $stmt->fetchAll();
    }

    public function createRemote(array $data): int
    {
        $sql = "
            INSERT INTO remotes (brand, model, notes)
            VALUES (:brand, :model, :notes)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':brand' => trim($data['brand']),
            ':model' => trim($data['model']),
            ':notes' => trim($data['notes'] ?? ''),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createDevice(array $data): int
    {
        $sql = "
            INSERT INTO devices (brand, model, notes)
            VALUES (:brand, :model, :notes)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':brand' => trim($data['brand']),
            ':model' => trim($data['model']),
            ':notes' => trim($data['notes'] ?? ''),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function linkDeviceToRemote(int $deviceId, int $remoteId, string $compatibilityType, string $notes = ''): void
    {
        $sql = "
            INSERT INTO device_remote (device_id, remote_id, compatibility_type, notes)
            VALUES (:device_id, :remote_id, :compatibility_type, :notes)
            ON DUPLICATE KEY UPDATE
                compatibility_type = VALUES(compatibility_type),
                notes = VALUES(notes)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':device_id' => $deviceId,
            ':remote_id' => $remoteId,
            ':compatibility_type' => $compatibilityType,
            ':notes' => $notes,
        ]);
    }
}
