CREATE TABLE llx_frota_veiculo_manutencao_preventiva (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    fk_veiculo INT NOT NULL,
    tipo_manutencao VARCHAR(255) NOT NULL,
    intervalo_km INT,
    intervalo_horas INT,
    intervalo_dias INT,
    ultima_manutencao_km DECIMAL(12,2),
    ultima_manutencao_horas DECIMAL(12,2),
    ultima_manutencao_data DATE,
    proxima_manutencao_km DECIMAL(12,2),
    proxima_manutencao_horas DECIMAL(12,2),
    proxima_manutencao_data DATE,
    FOREIGN KEY (fk_veiculo) REFERENCES llx_frota_veiculo(rowid)
) ENGINE=InnoDB;