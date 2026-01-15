CREATE TABLE llx_frota_veiculo_historico (
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    fk_veiculo integer NOT NULL, -- Não referencia para não ficar circular
    fk_manutencao integer DEFAULT NULL,  -- Não referencia para não ficar circular
    quilometragem DECIMAL(12,2) NOT NULL DEFAULT 0,
    horimetro DECIMAL(12,2) NOT NULL DEFAULT 0,
    date_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    fk_user integer DEFAULT NULL,
    observacao VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_frota_veiculo_historico_veiculo FOREIGN KEY (fk_veiculo) REFERENCES llx_frota_veiculo(rowid) ON DELETE CASCADE
) ENGINE=innodb;
