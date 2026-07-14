USE shield3;

INSERT INTO datasets (
    dataset_key,
    dataset_name,
    description,
    status,
    created_at,
    updated_at
) VALUES
    ('dns', 'DNS', 'Domain name system telemetry and query activity.', 'ACTIVE', NOW(), NOW()),
    ('firewall', 'Firewall', 'Firewall allow, deny, and threat event logs.', 'ACTIVE', NOW(), NOW()),
    ('email_traffic', 'Email Traffic', 'Inbound and outbound email security traffic.', 'ACTIVE', NOW(), NOW()),
    ('endpoint_security', 'Endpoint Security', 'Endpoint protection alerts and device posture data.', 'ACTIVE', NOW(), NOW()),
    ('cloud_security', 'Cloud Security', 'Cloud workload, account, and policy security events.', 'ACTIVE', NOW(), NOW()),
    ('vpn_logs', 'VPN Logs', 'Remote access VPN authentication and session logs.', 'ACTIVE', NOW(), NOW()),
    ('identity_logs', 'Identity Logs', 'Identity provider authentication and authorization events.', 'ACTIVE', NOW(), NOW()),
    ('proxy_logs', 'Proxy Logs', 'Web proxy request, filtering, and access logs.', 'ACTIVE', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    dataset_name = VALUES(dataset_name),
    description = VALUES(description),
    status = VALUES(status),
    updated_at = NOW();
