CREATE TABLE llx_frota_veiculo_historico (
    rowid INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fk_veiculo INT(11) NOT NULL, # Não referencia para não ficar circular
    fk_manutenção INT(11) DEFAULT NULL,  # Não referencia para não ficar circular
    quilometragem DECIMAL(12,2) NOT NULL DEFAULT 0,
    horimetro DECIMAL(12,2) NOT NULL DEFAULT 0,
    date_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    fk_user INT(11) DEFAULT NULL,
    observacao VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (fk_veiculo) REFERENCES llx_frota_veiculo(rowid) ON DELETE CASCADE
);
