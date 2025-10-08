-- ============================================================================
-- SQL script to create and populate table llx_frota_categoria_veiculo
-- Executed automatically by Dolibarr module initialization
-- Safe for re-execution (uses IF NOT EXISTS and duplicate protection)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: llx_frota_categoria_veiculo
-- Purpose: Stores vehicle categories for fleet management
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `llx_frota_categoria_veiculo` (
    `rowid` INT(11) NOT NULL AUTO_INCREMENT,
    `label` VARCHAR(255) NOT NULL,
    `code` VARCHAR(64) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `entity` INT(11) NOT NULL DEFAULT 1,
    `datec` DATETIME DEFAULT CURRENT_TIMESTAMP,          -- Creation date
    `tms` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Last modification
    `fk_user_creat` INT(11) DEFAULT NULL,                -- User who created
    `fk_user_modif` INT(11) DEFAULT NULL,                -- User who last modified
    PRIMARY KEY (`rowid`),
    UNIQUE KEY `uk_frota_categoria_code` (`code`),
    KEY `idx_frota_categoria_entity` (`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Default categories
-- The ON DUPLICATE KEY UPDATE ensures that descriptions are refreshed if re-run
-- ----------------------------------------------------------------------------
INSERT INTO `llx_frota_categoria_veiculo` (`label`, `code`, `description`, `entity`)
VALUES
    ('Carro de Passeio', 'carro_passeio', 'Veículo de uso urbano e rodoviário para transporte de pessoas.', 1),
    ('Caminhonete', 'caminhonete', 'Veículo leve com caçamba ou cabine dupla, usado para transporte e serviços.', 1),
    ('Caminhão', 'caminhao', 'Veículo pesado para transporte de cargas.', 1),
    ('Trator Agrícola', 'trator_agricola', 'Veículo agrícola utilizado para tração e operações no campo.', 1),
    ('Implemento Agrícola', 'implemento_agricola', 'Implementos acopláveis a tratores (grade, arado, plantadeira, etc).', 1),
    ('Ônibus', 'onibus', 'Veículo destinado ao transporte coletivo de passageiros.', 1),
    ('Moto', 'moto', 'Motocicleta de uso urbano ou rural.', 1),
    ('Reboque/Trailer', 'reboque', 'Reboque ou trailer acoplável a outro veículo.', 1),
    ('Van/Utilitário', 'van_utilitario', 'Veículo utilitário para transporte de cargas leves e pessoas.', 1),
    ('Veículo Pesado Especializado', 'veiculo_pesado_especial', 'Equipamentos e veículos pesados especializados (implementos, máquinas).', 1),
    ('Agrícola - Colheitadeira', 'colheitadeira', 'Colheitadeira agrícola.', 1),
    ('Agrícola - Pulverizador', 'pulverizador', 'Pulverizador agrícola.', 1),
    ('Utilitário Leve', 'utilitario_leve', 'Veículo utilitário leve (pickup compacta, furgão pequeno).', 1),
    ('Elétrico/Híbrido', 'eletrico_hibrido', 'Veículos com propulsão elétrica ou híbrida.', 1),
    ('Outro', 'outro', 'Categoria genérica para veículos sem categoria específica.', 1)
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`);

-- ----------------------------------------------------------------------------
-- End of file
-- ============================================================================
