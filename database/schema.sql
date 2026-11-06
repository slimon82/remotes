CREATE DATABASE IF NOT EXISTS remotes_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE remotes_db;

DROP TABLE IF EXISTS device_remote;
DROP TABLE IF EXISTS remotes;
DROP TABLE IF EXISTS devices;

CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(120) NOT NULL,
    model VARCHAR(180) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_devices_brand_model (brand, model)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE remotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(120) NOT NULL,
    model VARCHAR(180) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_remotes_brand_model (brand, model)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE device_remote (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    remote_id INT NOT NULL,
    compatibility_type ENUM('مناسب بالكامل', 'يحتاج برمجة', 'توافق جزئي') NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_device_remote (device_id, remote_id),
    CONSTRAINT fk_device_remote_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    CONSTRAINT fk_device_remote_remote FOREIGN KEY (remote_id) REFERENCES remotes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO devices (brand, model, notes) VALUES
('Samsung', 'UA55AU8000', 'شاشة Samsung 55 بوصة 4K'),
('LG', '32LM6300', 'شاشة LG 32 بوصة Full HD'),
('Sony', 'KD-55X80K', 'شاشة Sony 55 بوصة'),
('Hisense', '55E6N', 'شاشة Hisense 55 بوصة Smart TV');

INSERT INTO remotes (brand, model, notes) VALUES
('Samsung', 'BN59-01312A', 'ريموت أصلي لشاشات Samsung'),
('LG', 'AKB73715601', 'ريموت أصلي لشاشات LG'),
('Sony', 'RM-ED036', 'ريموت مخصص لبعض موديلات Sony'),
('Hisense', 'R51A', 'ريموت أصلي لشاشات Hisense');

INSERT INTO device_remote (device_id, remote_id, compatibility_type, notes) VALUES
(1, 1, 'مناسب بالكامل', 'توافق كامل مع شاشات Samsung'),
(1, 4, 'توافق جزئي', 'التحكم الأساسي يعمل، بعض الأزرار لا تستجيب'),
(2, 2, 'مناسب بالكامل', 'توافق كامل مع LG'),
(3, 3, 'يحتاج برمجة', 'يحتاج تهيئة أولية للريموت'),
(4, 4, 'مناسب بالكامل', 'توافق كامل مع Hisense');
