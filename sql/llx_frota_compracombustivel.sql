-- Copyright (C) 2024 SuperAdmin
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.

CREATE TABLE llx_frota_compracombustivel (
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,

	ref varchar(128) NOT NULL,
	label varchar(255),

	-- Quantidade comprada (litros)
	qty real NOT NULL,

	-- Valor total da compra
	amount double DEFAULT NULL,

	-- Reservatório que recebe o combustível
	fk_reservatorio integer NOT NULL,

	-- Fornecedor
	fk_soc integer,

	-- Projeto
	fk_project integer,

	description text,
	note_public text,
	note_private text,

	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

	fk_user_creat integer NOT NULL,
	fk_user_modif integer,

	last_main_doc varchar(255),
	import_key varchar(14),
	model_pdf varchar(255),

	status integer NOT NULL,
	-- END MODULEBUILDER FIELDS

	-- Indexes
	KEY idx_frota_compracombustivel_reservatorio (fk_reservatorio),
	KEY idx_frota_compracombustivel_soc (fk_soc),
	KEY idx_frota_compracombustivel_project (fk_project),

	-- Foreign keys
	CONSTRAINT fk_compracombustivel_reservatorio
		FOREIGN KEY (fk_reservatorio)
		REFERENCES llx_frota_reservatorio (rowid)
		ON DELETE RESTRICT
		ON UPDATE CASCADE
) ENGINE=innodb;
