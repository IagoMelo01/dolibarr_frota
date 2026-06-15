<?php

require_once __DIR__.'/frotaobject.class.php';
require_once __DIR__.'/veiculo.class.php';

class Uso extends FrotaObject
{
    public $element = 'uso';
    public $table_element = 'frota_uso';
    public $picto = 'fa-clock';

    public $fk_veiculo;
    public $fk_implement;
    public $fk_project;
    public $fk_task;
    public $fk_tipo_operacao;
    public $date_uso;
    public $horimeter_start;
    public $horimeter_end;
    public $worked_hours;
    public $area_ha;
    public $operator_id;
    public $estimated_hour_cost;
    public $estimated_total_cost;
    public $note;
    public $fields = array();

    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
        $this->fields = array_merge(self::auditFields(), array(
            'ref'=>array('type'=>'varchar(128)', 'label'=>'Ref', 'position'=>10, 'notnull'=>1, 'visible'=>1),
            'fk_veiculo'=>array('type'=>'integer', 'label'=>'FrotaVehicle', 'position'=>20, 'notnull'=>1, 'visible'=>1),
            'fk_implement'=>array('type'=>'integer', 'label'=>'FrotaImplement', 'position'=>30, 'notnull'=>0, 'visible'=>1),
            'fk_project'=>array('type'=>'integer', 'label'=>'Project', 'position'=>40, 'notnull'=>0, 'visible'=>1),
            'fk_task'=>array('type'=>'integer', 'label'=>'Task', 'position'=>50, 'notnull'=>0, 'visible'=>1),
            'fk_tipo_operacao'=>array('type'=>'integer', 'label'=>'FrotaOperationType', 'position'=>60, 'notnull'=>1, 'visible'=>1),
            'date_uso'=>array('type'=>'datetime', 'label'=>'FrotaUsageDate', 'position'=>70, 'notnull'=>1, 'visible'=>1),
            'horimeter_start'=>array('type'=>'double(24,8)', 'label'=>'FrotaHorimeterStart', 'position'=>80, 'notnull'=>1, 'visible'=>1),
            'horimeter_end'=>array('type'=>'double(24,8)', 'label'=>'FrotaHorimeterEnd', 'position'=>90, 'notnull'=>1, 'visible'=>1),
            'worked_hours'=>array('type'=>'double(24,8)', 'label'=>'FrotaWorkedHours', 'position'=>100, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'area_ha'=>array('type'=>'double(24,8)', 'label'=>'FrotaAreaHa', 'position'=>110, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'operator_id'=>array('type'=>'integer', 'label'=>'FrotaOperator', 'position'=>120, 'notnull'=>0, 'visible'=>1),
            'estimated_hour_cost'=>array('type'=>'double(24,8)', 'label'=>'FrotaEstimatedHourCost', 'position'=>130, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'estimated_total_cost'=>array('type'=>'double(24,8)', 'label'=>'FrotaEstimatedTotalCost', 'position'=>140, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'note'=>array('type'=>'text', 'label'=>'Note', 'position'=>150, 'notnull'=>0, 'visible'=>0),
        ));
    }

    public function create(User $user, $notrigger = false)
    {
        if ($this->validateUsage() < 0) {
            return -1;
        }
        $this->generateRef('USO');
        $this->calculateCosts();
        $result = parent::create($user, $notrigger);
        if ($result > 0) {
            $this->calculateCosts();
            parent::update($user, true);
            $this->updateVehicleReading($user);
        }
        return $result;
    }

    public function update(User $user, $notrigger = false)
    {
        if ($this->validateUsage() < 0) {
            return -1;
        }
        $this->calculateCosts();
        $result = parent::update($user, $notrigger);
        if ($result > 0) {
            $this->updateVehicleReading($user);
        }
        return $result;
    }

    private function calculateCosts()
    {
        $this->worked_hours = max(0, (float) price2num($this->horimeter_end, 'MS') - (float) price2num($this->horimeter_start, 'MS'));

        $sql = 'SELECT';
        $sql .= ' (SELECT COALESCE(SUM(total_amount),0) FROM '.MAIN_DB_PREFIX.'frota_abastecimento WHERE entity = '.((int) $this->entity).' AND fk_veiculo = '.((int) $this->fk_veiculo).' AND status = 1)';
        $sql .= ' + (SELECT COALESCE(SUM(total_cost),0) FROM '.MAIN_DB_PREFIX.'frota_manutencao WHERE entity = '.((int) $this->entity).' AND fk_veiculo = '.((int) $this->fk_veiculo).' AND status = 2) total_cost,';
        $sql .= ' (SELECT COALESCE(SUM(worked_hours),0) FROM '.MAIN_DB_PREFIX.'frota_uso WHERE entity = '.((int) $this->entity).' AND fk_veiculo = '.((int) $this->fk_veiculo);
        if (!empty($this->id)) {
            $sql .= ' AND rowid <> '.((int) $this->id);
        }
        $sql .= ') total_hours';
        $resql = $this->db->query($sql);
        $obj = $resql ? $this->db->fetch_object($resql) : null;
        $hours = ($obj ? (float) $obj->total_hours : 0) + $this->worked_hours;
        $cost = $obj ? (float) $obj->total_cost : 0;
        $this->estimated_hour_cost = $hours > 0 ? $cost / $hours : 0;
        $this->estimated_total_cost = $this->estimated_hour_cost * $this->worked_hours;
    }

    private function updateVehicleReading(User $user)
    {
        $vehicle = new Veiculo($this->db);
        if ($vehicle->fetch((int) $this->fk_veiculo) > 0 && (int) $vehicle->entity === (int) $this->entity) {
            $vehicle->estimated_hour_cost = $this->estimated_hour_cost;
            if ((float) $this->horimeter_end > (float) $vehicle->horimeter) {
                $vehicle->horimeter = (float) $this->horimeter_end;
            }
            $vehicle->update($user, true);
        }
    }

    private function validateUsage()
    {
        if ((float) price2num($this->horimeter_end, 'MS') < (float) price2num($this->horimeter_start, 'MS')) {
            $this->error = 'FrotaErrorInvalidHorimeterRange';
            return -1;
        }

        $vehicle = new Veiculo($this->db);
        if ($vehicle->fetch((int) $this->fk_veiculo) <= 0 || (!empty($this->entity) && (int) $vehicle->entity !== (int) $this->entity)) {
            $this->error = 'FrotaErrorVehicleNotFound';
            return -1;
        }
        $this->entity = (int) $vehicle->entity;
        return 1;
    }
}
