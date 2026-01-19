CREATE TABLE IF NOT EXISTS llx_frota_veiculo (
    -- BEGIN MODULEBUILDER FIELDS
    rowid INT AUTO_INCREMENT PRIMARY KEY NOT NULL, 
    ref VARCHAR(128) NOT NULL, 
    label VARCHAR(255), 
    amount DOUBLE, 
    fk_soc INT, 
    fk_project INT, 
    description TEXT, 
    note_public TEXT, 
    note_private TEXT, 
    date_creation DATETIME NOT NULL, 
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL, 
    fk_user_creat INT NOT NULL, 
    fk_user_modif INT, 
    last_main_doc VARCHAR(255), 
    import_key VARCHAR(14), 
    model_pdf VARCHAR(255), 
    status INT NOT NULL, 
    fabricante INT NOT NULL, 
    modelo VARCHAR(255) NOT NULL, 
    ano_fab VARCHAR(10), 
    num_identificacao VARCHAR(10), 
    cap_carga INT, 
    quilometragem_inicial DECIMAL(12,2) DEFAULT 0.00, 
    horimetro_inicial DECIMAL(12,2) DEFAULT 0.00, 
    documento VARCHAR(255), 
    potencia INT,
    fk_categoria INT DEFAULT NULL,
    
    CONSTRAINT fk_frota_veiculo_categoria FOREIGN KEY (fk_categoria) 
        REFERENCES llx_frota_categoria_veiculo(rowid) 
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_frota_veiculo_categoria ON llx_frota_veiculo(fk_categoria);
