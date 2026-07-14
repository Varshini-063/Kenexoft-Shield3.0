USE shield3;

INSERT INTO managed_services (name) VALUES
    ('SOC Services'),
    ('Managed Detection & Response'),
    ('Vulnerability Management'),
    ('Incident Response'),
    ('Compliance Services'),
    ('Risk Assessment'),
    ('Cloud Security'),
    ('Endpoint Security')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO expertise (name) VALUES
    ('Network Security'),
    ('Cloud Security'),
    ('Application Security'),
    ('SOC Operations'),
    ('Threat Hunting'),
    ('Penetration Testing'),
    ('Compliance'),
    ('Risk Management'),
    ('Incident Response'),
    ('Security Architecture')
ON DUPLICATE KEY UPDATE name = VALUES(name);
