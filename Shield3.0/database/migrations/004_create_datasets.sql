USE shield3;

CREATE TABLE IF NOT EXISTS datasets (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    dataset_key VARCHAR(100) NOT NULL UNIQUE,
    dataset_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_datasets_status (status),
    INDEX idx_datasets_key (dataset_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_datasets (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    dataset_id BIGINT NOT NULL,
    assigned_by BIGINT NULL,
    assigned_at DATETIME NOT NULL,
    CONSTRAINT fk_user_datasets_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_user_datasets_dataset
        FOREIGN KEY (dataset_id) REFERENCES datasets(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_user_datasets_assigned_by
        FOREIGN KEY (assigned_by) REFERENCES users(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_user_datasets_user_dataset (user_id, dataset_id),
    INDEX idx_user_datasets_user (user_id),
    INDEX idx_user_datasets_dataset (dataset_id),
    INDEX idx_user_datasets_assigned_by (assigned_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
