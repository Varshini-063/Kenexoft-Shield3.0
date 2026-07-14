USE shield3;

SET @role_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'role'
);

SET @add_role_sql := IF(
    @role_column_exists = 0,
    "ALTER TABLE users ADD COLUMN role ENUM('SUBSCRIBER', 'SUPER_ADMIN') NOT NULL DEFAULT 'SUBSCRIBER' AFTER status",
    "SELECT 'users.role already exists'"
);

PREPARE add_role_statement FROM @add_role_sql;
EXECUTE add_role_statement;
DEALLOCATE PREPARE add_role_statement;

SET @role_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_users_role'
);

SET @add_role_index_sql := IF(
    @role_index_exists = 0,
    'CREATE INDEX idx_users_role ON users (role)',
    "SELECT 'idx_users_role already exists'"
);

PREPARE add_role_index_statement FROM @add_role_index_sql;
EXECUTE add_role_index_statement;
DEALLOCATE PREPARE add_role_index_statement;
