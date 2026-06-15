CREATE TABLE llx_frota_tipo_operacao (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY NOT NULL,
    entity INTEGER NOT NULL DEFAULT 1,
    code VARCHAR(64) NOT NULL,
    label VARCHAR(128) NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    fk_user_creat INTEGER NOT NULL,
    fk_user_modif INTEGER,
    datec DATETIME NOT NULL,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX uk_frota_tipo_operacao_code (entity, code),
    INDEX idx_frota_tipo_operacao_entity (entity)
) ENGINE=innodb;

