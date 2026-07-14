USE shield3;

INSERT INTO users (
    persona, first_name, last_name, email, mobile, password_hash, gstin, status, role, created_at, updated_at
) VALUES (
    'COMPANY',
    'Super',
    'Admin',
    'admin@shield.local',
    '+910000000000',
    '$2y$10$eIR1qXnJrCRlZkTD38qxOufPtlhNKyMmb3TCZDu8EOtK84MwaQ79i',
    NULL,
    'ACTIVE',
    'SUPER_ADMIN',
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    password_hash = VALUES(password_hash),
    status = VALUES(status),
    role = VALUES(role),
    updated_at = NOW();
