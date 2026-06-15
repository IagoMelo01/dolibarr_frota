<?php

require_once __DIR__.'/frotaobject.class.php';

class Implemento extends FrotaObject
{
    public $element = 'implemento';
    public $table_element = 'frota_implemento';
    public $picto = 'fa-cogs';

    public $name;
    public $type;
    public $brand;
    public $model;
    public $year;
    public $status;
    public $note;
    public $fields = array();

    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
        $this->fields = array_merge(self::auditFields(), array(
            'ref'=>array('type'=>'varchar(128)', 'label'=>'Ref', 'position'=>10, 'notnull'=>1, 'visible'=>1),
            'name'=>array('type'=>'varchar(255)', 'label'=>'Name', 'position'=>20, 'notnull'=>1, 'visible'=>1),
            'type'=>array('type'=>'varchar(128)', 'label'=>'Type', 'position'=>30, 'notnull'=>0, 'visible'=>1),
            'brand'=>array('type'=>'varchar(128)', 'label'=>'FrotaBrand', 'position'=>40, 'notnull'=>0, 'visible'=>1),
            'model'=>array('type'=>'varchar(128)', 'label'=>'FrotaModel', 'position'=>50, 'notnull'=>0, 'visible'=>1),
            'year'=>array('type'=>'integer', 'label'=>'FrotaYear', 'position'=>60, 'notnull'=>0, 'visible'=>1),
            'status'=>array('type'=>'integer', 'label'=>'Status', 'position'=>70, 'notnull'=>1, 'visible'=>1, 'default'=>1),
            'note'=>array('type'=>'text', 'label'=>'Note', 'position'=>80, 'notnull'=>0, 'visible'=>0),
        ));
        foreach ($this->fields as &$field) {
            $field['enabled'] = 1;
        }
        unset($field);
        $this->fields['ref']['showoncombobox'] = 1;
        $this->fields['name']['showoncombobox'] = 2;
    }
}
