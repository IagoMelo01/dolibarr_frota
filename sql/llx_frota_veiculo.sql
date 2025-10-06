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


CREATE TABLE llx_frota_veiculo(
        rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
        entity integer NOT NULL DEFAULT 1,
        ref varchar(128) NOT NULL,
        label varchar(255) NOT NULL,
        status integer NOT NULL DEFAULT 0,
        fk_soc integer,
        fk_project integer,
        description text,
        note_public text,
        note_private text,
        fabricante varchar(120),
        modelo varchar(255) NOT NULL,
        ano_fab varchar(10),
        num_identificacao varchar(64),
        cap_carga integer,
        km double(12,2),
        horas_op double(12,2),
        potencia integer,
        fk_product_fuel integer,
        fk_default_reservatorio integer,
        date_service date,
        date_last_service date,
        amount double,
        last_main_doc varchar(255),
        import_key varchar(14),
        model_pdf varchar(255),
        date_creation datetime NOT NULL,
        tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        fk_user_creat integer NOT NULL,
        fk_user_modif integer
) ENGINE=innodb;
