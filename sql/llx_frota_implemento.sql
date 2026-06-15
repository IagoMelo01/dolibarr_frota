CREATE TABLE llx_frota_implemento (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY NOT NULL,
    entity INTEGER NOT NULL DEFAULT 1,
    ref VARCHAR(128) NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(128),
    brand VARCHAR(128),
    model VARCHAR(128),
    year INTEGER,
    status INTEGER NOT NULL DEFAULT 1,
    note TEXT,
    fk_user_creat INTEGER NOT NULL,
    fk_user_modif INTEGER,
    datec DATETIME NOT NULL,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX uk_frota_implemento_ref (entity, ref),
    INDEX idx_frota_implemento_entity (entity),
    INDEX idx_frota_implemento_status (status)
) ENGINE=innodb;
