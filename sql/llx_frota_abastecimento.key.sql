-- Copyright (C) 2024 SuperAdmin
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_frota_abastecimento ADD INDEX idx_frota_abastecimento_entity (entity);
ALTER TABLE llx_frota_abastecimento ADD INDEX idx_frota_abastecimento_ref (ref);
ALTER TABLE llx_frota_abastecimento ADD INDEX idx_frota_abastecimento_status (status);
ALTER TABLE llx_frota_abastecimento ADD INDEX idx_frota_abastecimento_fk_veiculo (fk_veiculo);
ALTER TABLE llx_frota_abastecimento ADD INDEX idx_frota_abastecimento_fk_reservatorio (fk_reservatorio);
ALTER TABLE llx_frota_abastecimento ADD INDEX idx_frota_abastecimento_fk_product (fk_product);
ALTER TABLE llx_frota_abastecimento ADD INDEX idx_frota_abastecimento_fk_entrepot (fk_entrepot);
ALTER TABLE llx_frota_abastecimento ADD INDEX idx_frota_abastecimento_fk_safra (fk_safra);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_frota_abastecimento ADD UNIQUE INDEX uk_frota_abastecimento_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_frota_abastecimento ADD CONSTRAINT llx_frota_abastecimento_fk_field FOREIGN KEY (fk_field) REFERENCES llx_frota_myotherobject(rowid);

