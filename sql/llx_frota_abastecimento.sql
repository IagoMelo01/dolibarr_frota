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


CREATE TABLE llx_frota_abastecimento(
        rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
        entity integer NOT NULL DEFAULT 1,
        ref varchar(128) NOT NULL,
        label varchar(255),
        status integer NOT NULL DEFAULT 0,
        fk_veiculo integer NOT NULL,
        fk_reservatorio integer NOT NULL,
        fk_product integer NOT NULL,
        fk_entrepot integer NOT NULL,
        qty double(12,3),
        qty_real double(12,3),
        unit_price double,
        amount double,
        km double(12,2),
        horas_op double(12,2),
        data_ab datetime NOT NULL,
        fk_safra integer,
        fk_stock_mouvement integer,
        fk_soc integer,
        fk_project integer,
        description text,
        note_public text,
        note_private text,
        last_main_doc varchar(255),
        import_key varchar(14),
        model_pdf varchar(255),
        date_creation datetime NOT NULL,
        tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        fk_user_creat integer NOT NULL,
        fk_user_modif integer
) ENGINE=innodb;
