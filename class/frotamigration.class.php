<?php

class FrotaMigration
{
    private $db;
    public $error = '';

    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    public function run()
    {
        $table = MAIN_DB_PREFIX.'frota_manutencao';
        $fields = array(
            'fk_implement'=>array('type'=>'int'),
            'date_planned'=>array('type'=>'datetime'),
            'due_horimeter'=>array('type'=>'double', 'value'=>'24,8'),
            'due_odometer'=>array('type'=>'double', 'value'=>'24,8'),
            'is_periodic'=>array('type'=>'tinyint', 'null'=>'NOT NULL', 'default'=>'0'),
            'period_days'=>array('type'=>'int'),
            'period_hours'=>array('type'=>'double', 'value'=>'24,8'),
            'period_km'=>array('type'=>'double', 'value'=>'24,8'),
            'fk_origin_maintenance'=>array('type'=>'int'),
            'fk_next_maintenance'=>array('type'=>'int'),
        );

        foreach ($fields as $name => $definition) {
            if (!$this->fieldExists($table, $name) && $this->db->DDLAddField($table, $name, $definition) < 0) {
                $this->error = $this->db->lasterror();
                return -1;
            }
        }
        if ($this->db->DDLUpdateField($table, 'date_start', array('type'=>'datetime', 'null'=>'null')) < 0) {
            $this->error = $this->db->lasterror();
            return -1;
        }
        if ($this->db->DDLUpdateField($table, 'fk_veiculo', array('type'=>'int', 'null'=>'null')) < 0) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        $indexes = array(
            'idx_frota_manutencao_implement'=>array('fk_implement', false),
            'idx_frota_manutencao_planned'=>array('date_planned', false),
            'idx_frota_manutencao_due_horimeter'=>array('due_horimeter', false),
            'idx_frota_manutencao_due_odometer'=>array('due_odometer', false),
            'idx_frota_manutencao_periodic'=>array('is_periodic', false),
            'idx_frota_manutencao_next'=>array('fk_next_maintenance', false),
            'uk_frota_manutencao_origin'=>array('entity, fk_origin_maintenance', true),
        );
        foreach ($indexes as $name => $definition) {
            if (!$this->indexExists($table, $name)) {
                $sql = 'CREATE '.($definition[1] ? 'UNIQUE ' : '').'INDEX '.$name.' ON '.$table.' ('.$definition[0].')';
                if (!$this->db->query($sql)) {
                    $this->error = $this->db->lasterror();
                    return -1;
                }
            }
        }
        return 1;
    }

    private function fieldExists($table, $field)
    {
        $resql = $this->db->DDLDescTable($table, $field);
        return $resql && $this->db->num_rows($resql) > 0;
    }

    private function indexExists($table, $index)
    {
        if ($this->db->type === 'pgsql') {
            $sql = "SELECT 1 FROM pg_indexes WHERE tablename = '".$this->db->escape($table)."' AND indexname = '".$this->db->escape($index)."'";
        } else {
            $sql = 'SHOW INDEX FROM '.$table." WHERE Key_name = '".$this->db->escape($index)."'";
        }
        $resql = $this->db->query($sql);
        return $resql && $this->db->num_rows($resql) > 0;
    }
}
