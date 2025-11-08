<?php
/* Copyright (C) 2024 SuperAdmin <superadmin@superadmin.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/veiculo_manutencao_preventiva.class.php
 * \ingroup     frota
 * \brief       This file is a CRUD class file for VeiculoManutencaoPreventiva (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for VeiculoManutencaoPreventiva
 */
class VeiculoManutencaoPreventiva extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'frota';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'veiculo_manutencao_preventiva';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'frota_veiculo_manutencao_preventiva';

	/**
	 * @var int  	Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
		'fk_veiculo' => array('type'=>'integer:Veiculo:frota/class/veiculo.class.php:1', 'label'=>'Veículo associado', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1,),
		'tipo_manutencao' => array('type'=>'varchar(255)', 'label'=>'Tipo de manutenção', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1,),
		'intervalo_km' => array('type'=>'integer', 'label'=>'Intervalo (km)', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1,),
		'intervalo_horas' => array('type'=>'integer', 'label'=>'Intervalo (horas)', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1,),
		'intervalo_dias' => array('type'=>'integer', 'label'=>'Intervalo (dias)', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1,),
		'ultima_manutencao_km' => array('type'=>'decimal(12,2)', 'label'=>'Última manutenção (km)', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1,),
		'ultima_manutencao_horas' => array('type'=>'decimal(12,2)', 'label'=>'Última manutenção (horas)', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1,),
		'ultima_manutencao_data' => array('type'=>'date', 'label'=>'Última manutenção (data)', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1,),
		'proxima_manutencao_km' => array('type'=>'decimal(12,2)', 'label'=>'Próxima manutenção (km)', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1,),
		'proxima_manutencao_horas' => array('type'=>'decimal(12,2)', 'label'=>'Próxima manutenção (horas)', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1,),
		'proxima_manutencao_data' => array('type'=>'date', 'label'=>'Próxima manutenção (data)', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1,),
	);

	public $rowid;
	public $fk_veiculo;
	public $tipo_manutencao;
	public $intervalo_km;
	public $intervalo_horas;
	public $intervalo_dias;
	public $ultima_manutencao_km;
	public $ultima_manutencao_horas;
	public $ultima_manutencao_data;
	public $proxima_manutencao_km;
	public $proxima_manutencao_horas;
	public $proxima_manutencao_data;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('mystringfield'=>'value', 'myintfield'=>4, 'customsql'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of objects if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= "t.rowid";
		foreach ($this->fields as $key => $val) {
			if ($key != 'rowid') {
				$sql .= ", t.".$key;
			}
		}
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				$key = 't.'.$key;
				$columnName = preg_replace('/^t\./', '', $key);
				if ($key === 'customsql') {
					$sqlwhere[] = $value;
					continue;
				} elseif (isset($this->fields[$columnName])) {
					$type = $this->fields[$columnName]['type'];
					if (preg_match('/^integer/', $type)) {
						if (is_int($value)) {
							$sqlwhere[] = $key . " = " . intval($value);
						} elseif (is_array($value)) {
							if (empty($value)) {
								continue;
							}
							$sqlwhere[] = $key . ' IN (' . $this->db->sanitize(implode(',', array_map('intval', $value))) . ')';
						}
						continue;
					} elseif (in_array($type, array('date', 'datetime', 'timestamp'))) {
						$sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
						continue;
					}
				}

				if (is_array($value) && count($value)) {
					$value = implode(',', array_map(function ($v) {
						return "'" . $this->db->sanitize($this->db->escape($v)) . "'";
					}, $value));
					$sqlwhere[] = $key . ' IN (' . $this->db->sanitize($value, true) . ')';
				} elseif (is_scalar($value)) {
					if (strpos($value, '%') === false) {
						$sqlwhere[] = $key . " = '" . $this->db->sanitize($this->db->escape($value)) . "'";
					} else {
						$sqlwhere[] = $key . " LIKE '%" . $this->db->escape($this->db->escapeforlike($value)) . "%'";
					}
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= " AND (".implode(" ".$filtermode." ", $sqlwhere).")";
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);
				$records[] = $record;
				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}
}
