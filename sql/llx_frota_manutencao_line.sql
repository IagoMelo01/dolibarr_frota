CREATE TABLE llx_frota_manutencao_line (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY NOT NULL,
    entity INTEGER NOT NULL DEFAULT 1,
    fk_manutencao INTEGER NOT NULL,
    fk_product INTEGER NOT NULL,
    fk_warehouse INTEGER NOT NULL,
    fk_stock_movement INTEGER,
    qty DOUBLE(24,8) NOT NULL,
    unit_price DOUBLE(24,8) NOT NULL DEFAULT 0,
    total_amount DOUBLE(24,8) NOT NULL DEFAULT 0,
    note TEXT,
    fk_user_creat INTEGER NOT NULL,
    fk_user_modif INTEGER,
    datec DATETIME NOT NULL,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_frota_manut_line_entity (entity),
    INDEX idx_frota_manut_line_parent (fk_manutencao),
    INDEX idx_frota_manut_line_product (fk_product),
    INDEX idx_frota_manut_line_warehouse (fk_warehouse)
) ENGINE=innodb;

