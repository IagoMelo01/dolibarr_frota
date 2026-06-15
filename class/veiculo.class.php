<?php

require_once __DIR__.'/frotaobject.class.php';

class Veiculo extends FrotaObject
{
    public $element = 'veiculo';
    public $table_element = 'frota_veiculo';
    public $picto = 'fa-tractor';

    public $name;
    public $fk_veiculo_type;
    public $brand;
    public $model;
    public $year;
    public $plate;
    public $chassis;
    public $asset_number;
    public $horimeter;
    public $odometer;
    public $estimated_hour_cost;
    public $status;
    public $note;

    public $fields = array();

    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
        $this->fields = array_merge(self::auditFields(), array(
            'ref'=>array('type'=>'varchar(128)', 'label'=>'Ref', 'position'=>10, 'notnull'=>1, 'visible'=>1),
            'name'=>array('type'=>'varchar(255)', 'label'=>'Name', 'position'=>20, 'notnull'=>1, 'visible'=>1),
            'fk_veiculo_type'=>array('type'=>'integer', 'label'=>'FrotaVehicleType', 'position'=>30, 'notnull'=>0, 'visible'=>1),
            'brand'=>array('type'=>'varchar(128)', 'label'=>'FrotaBrand', 'position'=>40, 'notnull'=>0, 'visible'=>1),
            'model'=>array('type'=>'varchar(128)', 'label'=>'FrotaModel', 'position'=>50, 'notnull'=>0, 'visible'=>1),
            'year'=>array('type'=>'integer', 'label'=>'FrotaYear', 'position'=>60, 'notnull'=>0, 'visible'=>1),
            'plate'=>array('type'=>'varchar(32)', 'label'=>'FrotaPlate', 'position'=>70, 'notnull'=>0, 'visible'=>1),
            'chassis'=>array('type'=>'varchar(128)', 'label'=>'FrotaChassis', 'position'=>80, 'notnull'=>0, 'visible'=>0),
            'asset_number'=>array('type'=>'varchar(128)', 'label'=>'FrotaAssetNumber', 'position'=>90, 'notnull'=>0, 'visible'=>0),
            'horimeter'=>array('type'=>'double(24,8)', 'label'=>'FrotaHorimeter', 'position'=>100, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'odometer'=>array('type'=>'double(24,8)', 'label'=>'FrotaOdometer', 'position'=>110, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'estimated_hour_cost'=>array('type'=>'double(24,8)', 'label'=>'FrotaEstimatedHourCost', 'position'=>120, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'status'=>array('type'=>'integer', 'label'=>'Status', 'position'=>130, 'notnull'=>1, 'visible'=>1, 'default'=>1),
            'note'=>array('type'=>'text', 'label'=>'Note', 'position'=>140, 'notnull'=>0, 'visible'=>0),
        ));
        foreach ($this->fields as &$field) {
            $field['enabled'] = 1;
        }
        unset($field);
        $this->fields['ref']['showoncombobox'] = 1;
        $this->fields['name']['showoncombobox'] = 2;
    }

    public function updateReadings(User $user, $horimeter = null, $odometer = null)
    {
        $changed = false;
        if ($horimeter !== null && (float) $horimeter > (float) $this->horimeter) {
            $this->horimeter = (float) $horimeter;
            $changed = true;
        }
        if ($odometer !== null && (float) $odometer > (float) $this->odometer) {
            $this->odometer = (float) $odometer;
            $changed = true;
        }

        return $changed ? $this->update($user, true) : 0;
    }
}
