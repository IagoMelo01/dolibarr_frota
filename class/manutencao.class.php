<?php
/*
 * Maintenance order business object.
 */

require_once __DIR__ . '/fleetobject.class.php';
require_once __DIR__ . '/veiculo.class.php';
require_once __DIR__ . '/implemento.class.php';

if (isModEnabled('safra')) {
    dol_include_once('/safra/class/safra.class.php');
}

/**
 * Maintenance record for a vehicle or implement.
 */
class Manutencao extends FleetObject
{
    /** @var string */
    public $element = 'manutencao';

    /** @var string */
    public $table_element = 'frota_manutencao';

    /** @var string */
    public $picto = 'fa-tools';

    /** @var int */
    public $ismultientitymanaged = 1;

    /** @var int */
    public $isextrafieldmanaged = 1;

    public const STATUS_DRAFT = 0;
    public const STATUS_PLANNED = 1;
    public const STATUS_IN_PROGRESS = 2;
    public const STATUS_DONE = 3;
    public const STATUS_CANCELED = 9;

    /** @var array */
    public $fields = array(
        'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1),
        'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 5, 'notnull' => 1, 'visible' => 0, 'default' => '1', 'index' => 1),
        'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 5, 'default' => '(MAIN)', 'index' => 1, 'searchall' => 1, 'validate' => '1'),
        'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300'),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array(
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PLANNED => 'Planned',
            self::STATUS_IN_PROGRESS => 'InProgress',
            self::STATUS_DONE => 'Done',
            self::STATUS_CANCELED => 'Canceled',
        )),
        'tipo' => array('type' => 'varchar(64)', 'label' => 'MaintenanceType', 'enabled' => '1', 'position' => 60, 'notnull' => 1, 'visible' => 1, 'arrayofkeyval' => array(
            'preventiva' => 'FrotaMaintenancePreventive',
            'corretiva' => 'FrotaMaintenanceCorrective',
            'inspecao' => 'FrotaMaintenanceInspection',
            'outro' => 'Other',
        )),
        'fk_veiculo' => array('type' => 'integer:Veiculo:frota/class/veiculo.class.php:1', 'label' => 'Veiculo', 'enabled' => '1', 'position' => 70, 'notnull' => -1, 'visible' => 1, 'index' => 1),
        'fk_implemento' => array('type' => 'integer:Implemento:frota/class/implemento.class.php:1', 'label' => 'Implemento', 'enabled' => '1', 'position' => 80, 'notnull' => -1, 'visible' => 1, 'index' => 1),
        'fk_safra' => array('type' => 'integer', 'label' => 'FrotaSafra', 'enabled' => 'isModEnabled("safra")', 'position' => 85, 'notnull' => -1, 'visible' => 1, 'index' => 1),
        'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php:1', 'label' => 'ThirdParty', 'picto' => 'company', 'enabled' => '$conf->societe->enabled', 'position' => 90, 'notnull' => -1, 'visible' => -1, 'index' => 1),
        'fk_project' => array('type' => 'integer:Project:projet/class/project.class.php:1', 'label' => 'Project', 'picto' => 'project', 'enabled' => '$conf->project->enabled', 'position' => 100, 'notnull' => -1, 'visible' => -1, 'index' => 1),
        'amount' => array('type' => 'price', 'label' => 'EstimatedCost', 'enabled' => '1', 'position' => 110, 'notnull' => 0, 'visible' => 1),
        'km_previsto' => array('type' => 'double(12,2)', 'label' => 'MileagePlanned', 'enabled' => '1', 'position' => 120, 'notnull' => 0, 'visible' => -1),
        'horas_previstas' => array('type' => 'double(12,2)', 'label' => 'HoursPlanned', 'enabled' => '1', 'position' => 130, 'notnull' => 0, 'visible' => -1),
        'km_executado' => array('type' => 'double(12,2)', 'label' => 'MileageDone', 'enabled' => '1', 'position' => 140, 'notnull' => 0, 'visible' => 1),
        'horas_executadas' => array('type' => 'double(12,2)', 'label' => 'HoursDone', 'enabled' => '1', 'position' => 150, 'notnull' => 0, 'visible' => 1),
        'data_prevista' => array('type' => 'date', 'label' => 'DatePlanned', 'enabled' => '1', 'position' => 160, 'notnull' => 0, 'visible' => 1),
        'data_concluida' => array('type' => 'date', 'label' => 'DateDone', 'enabled' => '1', 'position' => 170, 'notnull' => 0, 'visible' => 1),
        'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 200, 'notnull' => 0, 'visible' => 3),
        'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => '1', 'position' => 210, 'notnull' => 0, 'visible' => 0),
        'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => '1', 'position' => 220, 'notnull' => 0, 'visible' => 0),
        'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => '1', 'position' => 600, 'notnull' => 0, 'visible' => 0),
        'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'position' => 1000, 'notnull' => -1, 'visible' => -2),
        'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => '1', 'position' => 1010, 'notnull' => -1, 'visible' => 0),
        'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 1020, 'notnull' => 1, 'visible' => -2),
        'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 1030, 'notnull' => 0, 'visible' => -2),
        'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 1040, 'notnull' => 1, 'visible' => -2),
        'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 1050, 'notnull' => -1, 'visible' => -2),
    );

    /** @var int */
    public $rowid;
    /** @var int */
    public $entity;
    /** @var string */
    public $ref;
    /** @var string */
    public $label;
    /** @var int */
    public $status = self::STATUS_DRAFT;
    /** @var string */
    public $tipo;
    /** @var int */
    public $fk_veiculo;
    /** @var int */
    public $fk_implemento;
    /** @var int */
    public $fk_safra;
    /** @var float */
    public $amount;
    /** @var float */
    public $km_previsto;
    /** @var float */
    public $horas_previstas;
    /** @var float */
    public $km_executado;
    /** @var float */
    public $horas_executadas;
    /** @var string */
    public $data_prevista;
    /** @var string */
    public $data_concluida;

    /**
     * Move to planned status.
     *
     * @param User $user
     * @param bool $notrigger
     *
     * @return int
     */
    public function validate(User $user, $notrigger = false)
    {
        if ($this->status != self::STATUS_DRAFT) {
            return 0;
        }

        return $this->setStatusCommon($user, self::STATUS_PLANNED, $notrigger, 'FROTA_MANUTENCAO_PLAN');
    }

    /**
     * Mark maintenance as in progress.
     *
     * @param User $user
     * @param bool $notrigger
     *
     * @return int
     */
    public function start(User $user, $notrigger = false)
    {
        if (!in_array($this->status, array(self::STATUS_PLANNED, self::STATUS_DRAFT))) {
            return 0;
        }

        return $this->setStatusCommon($user, self::STATUS_IN_PROGRESS, $notrigger, 'FROTA_MANUTENCAO_START');
    }

    /**
     * Close maintenance order.
     *
     * @param User       $user
     * @param string|int $completionDate
     * @param float|null $finalCost
     * @param bool       $notrigger
     *
     * @return int
     */
    public function close(User $user, $completionDate = '', $finalCost = null, $notrigger = false)
    {
        if ($this->status == self::STATUS_DONE) {
            return 0;
        }

        if (!empty($completionDate)) {
            $this->data_concluida = $completionDate;
        } else {
            $this->data_concluida = dol_now();
        }

        if ($finalCost !== null) {
            $this->amount = price2num($finalCost, 'MT');
        }

        $this->fk_user_modif = $user->id;
        $this->updateCommon($user, true);

        $this->updateUsageAfterCompletion($user);

        return $this->setStatusCommon($user, self::STATUS_DONE, $notrigger, 'FROTA_MANUTENCAO_DONE');
    }

    /**
     * Cancel maintenance order.
     *
     * @param User $user
     * @param bool $notrigger
     *
     * @return int
     */
    public function cancel(User $user, $notrigger = false)
    {
        return parent::cancel($user, $notrigger);
    }

    /**
     * Update vehicle/implement usage metrics based on execution data.
     *
     * @param User $user
     */
    protected function updateUsageAfterCompletion(User $user)
    {
        if (!empty($this->fk_veiculo)) {
            $vehicle = new Veiculo($this->db);
            if ($vehicle->fetch($this->fk_veiculo) > 0) {
                $hasChange = false;
                if (!empty($this->km_executado)) {
                    $vehicle->km = round(max($vehicle->km, $this->km_executado), 2);
                    $hasChange = true;
                }
                if (!empty($this->horas_executadas)) {
                    $vehicle->horas_op = round(max($vehicle->horas_op, $this->horas_executadas), 2);
                    $hasChange = true;
                }

                if ($hasChange) {
                    $vehicle->fk_user_modif = $user->id;
                    $vehicle->updateCommon($user, true);
                }
            }
        }

        if (!empty($this->fk_implemento)) {
            $implement = new Implemento($this->db);
            if (method_exists($implement, 'fetch') && $implement->fetch($this->fk_implemento) > 0) {
                if (!empty($this->horas_executadas) && property_exists($implement, 'horas_op')) {
                    $implement->horas_op = round(max($implement->horas_op, $this->horas_executadas), 2);
                    $implement->fk_user_modif = $user->id;
                    $implement->updateCommon($user, true);
                }
            }
        }
    }

    /**
     * Fetch safra reference if module is enabled.
     *
     * @return object|null
     */
    public function fetchSafra()
    {
        if (empty($this->fk_safra) || !isModEnabled('safra')) {
            return null;
        }

        if (!class_exists('Safra')) {
            return null;
        }

        $safra = new Safra($this->db);
        if (method_exists($safra, 'fetch') && $safra->fetch($this->fk_safra) > 0) {
            return $safra;
        }

        return null;
    }
}
