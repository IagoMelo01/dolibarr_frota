<?php
/*
 * Fleet vehicle business object.
 */

require_once __DIR__ . '/fleetobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class representing an agricultural fleet vehicle.
 */
class Veiculo extends FleetObject
{
    /** @var string */
    public $element = 'veiculo';

    /** @var string */
    public $table_element = 'frota_veiculo';

    /** @var string */
    public $picto = 'fa-tractor';

    /** @var int */
    public $ismultientitymanaged = 1;

    /** @var int */
    public $isextrafieldmanaged = 1;

    public const STATUS_DRAFT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_OUT_OF_SERVICE = 2;
    public const STATUS_CANCELED = 9;

    /** @var array */
    public $fields = array(
        'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'css' => 'left'),
        'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 5, 'notnull' => 1, 'visible' => 0, 'default' => '1'),
        'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 5, 'default' => '(VEH)', 'index' => 1, 'searchall' => 1, 'validate' => '1'),
        'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'validate' => '1'),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array(
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'FrotaVehicleActive',
            self::STATUS_OUT_OF_SERVICE => 'FrotaVehicleOutOfService',
            self::STATUS_CANCELED => 'Canceled',
        )),
        'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php:1', 'label' => 'ThirdParty', 'picto' => 'company', 'enabled' => '$conf->societe->enabled', 'position' => 60, 'notnull' => -1, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500'),
        'fk_project' => array('type' => 'integer:Project:projet/class/project.class.php:1', 'label' => 'Project', 'picto' => 'project', 'enabled' => '$conf->project->enabled', 'position' => 70, 'notnull' => -1, 'visible' => -1, 'index' => 1),
        'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 3),
        'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => '1', 'position' => 81, 'notnull' => 0, 'visible' => 0),
        'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => '1', 'position' => 82, 'notnull' => 0, 'visible' => 0),
        'fabricante' => array('type' => 'varchar(120)', 'label' => 'Manufacturer', 'enabled' => '1', 'position' => 100, 'notnull' => 0, 'visible' => 1, 'searchall' => 1),
        'modelo' => array('type' => 'varchar(255)', 'label' => 'Model', 'enabled' => '1', 'position' => 110, 'notnull' => 1, 'visible' => 1, 'searchall' => 1),
        'ano_fab' => array('type' => 'varchar(10)', 'label' => 'Year', 'enabled' => '1', 'position' => 120, 'notnull' => 0, 'visible' => 1),
        'num_identificacao' => array('type' => 'varchar(64)', 'label' => 'SerialOrPlate', 'enabled' => '1', 'position' => 130, 'notnull' => 0, 'visible' => 4, 'searchall' => 1),
        'potencia' => array('type' => 'integer', 'label' => 'HorsePower', 'enabled' => '1', 'position' => 140, 'notnull' => 0, 'visible' => 1),
        'cap_carga' => array('type' => 'integer', 'label' => 'LoadCapacityKg', 'enabled' => '1', 'position' => 150, 'notnull' => 0, 'visible' => -1),
        'km' => array('type' => 'double(12,2)', 'label' => 'CurrentMileage', 'enabled' => '1', 'position' => 160, 'notnull' => 0, 'visible' => 1, 'isameasure' => '1'),
        'horas_op' => array('type' => 'double(12,2)', 'label' => 'CurrentHours', 'enabled' => '1', 'position' => 170, 'notnull' => 0, 'visible' => 1, 'isameasure' => '1'),
        'fk_product_fuel' => array('type' => 'integer:Product:product/class/product.class.php:1::(p.fk_product_type:=:1)', 'label' => 'FrotaDefaultFuelProduct', 'enabled' => '$conf->product->enabled', 'position' => 200, 'notnull' => -1, 'visible' => 1, 'index' => 1),
        'fk_default_reservatorio' => array('type' => 'integer:Reservatorio:frota/class/reservatorio.class.php:1', 'label' => 'FrotaDefaultReservatorio', 'enabled' => '1', 'position' => 210, 'notnull' => -1, 'visible' => 1),
        'date_service' => array('type' => 'date', 'label' => 'FrotaDateInService', 'enabled' => '1', 'position' => 220, 'notnull' => 0, 'visible' => -1),
        'date_last_service' => array('type' => 'date', 'label' => 'FrotaDateLastService', 'enabled' => '1', 'position' => 230, 'notnull' => 0, 'visible' => -1),
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
    /** @var int|null */
    public $fk_soc;
    /** @var int|null */
    public $fk_project;
    /** @var string */
    public $description;
    /** @var string */
    public $note_public;
    /** @var string */
    public $note_private;
    /** @var string */
    public $fabricante;
    /** @var string */
    public $modelo;
    /** @var string */
    public $ano_fab;
    /** @var string */
    public $num_identificacao;
    /** @var int */
    public $potencia;
    /** @var int */
    public $cap_carga;
    /** @var float */
    public $km;
    /** @var float */
    public $horas_op;
    /** @var int|null */
    public $fk_product_fuel;
    /** @var int|null */
    public $fk_default_reservatorio;
    /** @var int */
    public $fk_user_creat;
    /** @var int */
    public $fk_user_modif;
    /** @var string */
    public $date_service;
    /** @var string */
    public $date_last_service;

    /**
     * Register a manual update of usage metrics.
     *
     * @param float|null $kmDelta    Additional kilometers
     * @param float|null $hoursDelta Additional hours of operation
     * @param User       $user       Current user
     *
     * @return int
     */
    public function incrementUsage($kmDelta, $hoursDelta, User $user)
    {
        if ($kmDelta !== null) {
            $this->km = round((float) $this->km + (float) $kmDelta, 2);
        }

        if ($hoursDelta !== null) {
            $this->horas_op = round((float) $this->horas_op + (float) $hoursDelta, 2);
        }

        return $this->updateCommon($user, true);
    }

    /**
     * Update the usage meters with absolute values.
     *
     * @param float|null $kmAbsolute    New absolute kilometer reading
     * @param float|null $hourAbsolute  New absolute hour reading
     * @param User       $user          Current user
     *
     * @return int
     */
    public function overwriteUsage($kmAbsolute, $hourAbsolute, User $user)
    {
        if ($kmAbsolute !== null) {
            $this->km = round(max(0, (float) $kmAbsolute), 2);
        }

        if ($hourAbsolute !== null) {
            $this->horas_op = round(max(0, (float) $hourAbsolute), 2);
        }

        return $this->updateCommon($user, true);
    }

    /**
     * Convenience check to know if the vehicle has a preferred fuel product configured.
     *
     * @return bool
     */
    public function hasDefaultFuelProduct()
    {
        return !empty($this->fk_product_fuel);
    }

    /**
     * Fetch the related Dolibarr product for the default fuel.
     *
     * @return Product|null
     */
    public function getDefaultFuelProduct()
    {
        if (!$this->hasDefaultFuelProduct()) {
            return null;
        }

        $product = new Product($this->db);
        if ($product->fetch($this->fk_product_fuel) > 0) {
            return $product;
        }

        return null;
    }

    /**
     * Test if vehicle is available for operations.
     *
     * @return bool
     */
    public function isOperational()
    {
        return in_array($this->status, array(self::STATUS_DRAFT, self::STATUS_ACTIVE));
    }
}
