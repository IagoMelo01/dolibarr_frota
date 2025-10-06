<?php
/*
 * Fuel reservoir business object for the Frota module.
 */

require_once __DIR__ . '/fleetobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';

/**
 * Represents a storage tank or reservoir of fuel inside the fleet module.
 */
class Reservatorio extends FleetObject
{
    /** @var string */
    public $element = 'reservatorio';

    /** @var string */
    public $table_element = 'frota_reservatorio';

    /** @var string */
    public $picto = 'fa-gas-pump';

    /** @var int */
    public $ismultientitymanaged = 1;

    /** @var int */
    public $isextrafieldmanaged = 1;

    public const STATUS_DRAFT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 2;
    public const STATUS_CANCELED = 9;

    /** @var array */
    public $fields = array(
        'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1),
        'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 5, 'notnull' => 1, 'visible' => 0, 'default' => '1', 'index' => 1),
        'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 5, 'default' => '(RES)', 'index' => 1, 'searchall' => 1, 'validate' => '1'),
        'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'validate' => '1'),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => '1', 'position' => 35, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array(
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Enabled',
            self::STATUS_INACTIVE => 'Disabled',
            self::STATUS_CANCELED => 'Canceled',
        )),
        'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php:1', 'label' => 'ThirdParty', 'picto' => 'company', 'enabled' => '$conf->societe->enabled', 'position' => 60, 'notnull' => -1, 'visible' => -1, 'index' => 1),
        'fk_project' => array('type' => 'integer:Project:projet/class/project.class.php:1', 'label' => 'Project', 'picto' => 'project', 'enabled' => '$conf->project->enabled', 'position' => 70, 'notnull' => -1, 'visible' => -1, 'index' => 1),
        'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 3),
        'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => '1', 'position' => 81, 'notnull' => 0, 'visible' => 0),
        'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => '1', 'position' => 82, 'notnull' => 0, 'visible' => 0),
        'fk_product' => array('type' => 'integer:Product:product/class/product.class.php:1::(p.fk_product_type:=:1)', 'label' => 'Product', 'enabled' => '$conf->product->enabled', 'position' => 100, 'notnull' => 1, 'visible' => 1, 'index' => 1),
        'fk_entrepot' => array('type' => 'integer:Entrepot:product/stock/class/entrepot.class.php:1', 'label' => 'Warehouse', 'enabled' => '$conf->stock->enabled', 'position' => 110, 'notnull' => 1, 'visible' => 1, 'index' => 1),
        'capacidade' => array('type' => 'double(12,2)', 'label' => 'Capacity', 'enabled' => '1', 'position' => 120, 'notnull' => 1, 'visible' => 1, 'isameasure' => '1'),
        'nivel' => array('type' => 'double(12,2)', 'label' => 'FrotaMeasuredLevel', 'enabled' => '1', 'position' => 130, 'notnull' => 0, 'visible' => -1, 'isameasure' => '1'),
        'nivel_seguranca' => array('type' => 'double(12,2)', 'label' => 'FrotaSafetyStock', 'enabled' => '1', 'position' => 140, 'notnull' => 0, 'visible' => 1, 'isameasure' => '1'),
        'tipo' => array('type' => 'varchar(32)', 'label' => 'FuelType', 'enabled' => '1', 'position' => 150, 'notnull' => 0, 'visible' => 1),
        'last_sync_stock' => array('type' => 'datetime', 'label' => 'FrotaReservatorioLastSync', 'enabled' => '1', 'position' => 160, 'notnull' => 0, 'visible' => -1),
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
    /** @var int */
    public $fk_product;
    /** @var int */
    public $fk_entrepot;
    /** @var float */
    public $capacidade;
    /** @var float */
    public $nivel;
    /** @var float */
    public $nivel_seguranca;
    /** @var string */
    public $tipo;
    /** @var string */
    public $last_sync_stock;

    /**
     * Return the Dolibarr product bound to the reservoir.
     *
     * @return Product|null
     */
    public function getProduct()
    {
        if (empty($this->fk_product)) {
            return null;
        }

        $product = new Product($this->db);
        if ($product->fetch($this->fk_product) > 0) {
            return $product;
        }

        return null;
    }

    /**
     * Fetch current stock stored in the Dolibarr stock tables.
     *
     * @return float|null Current quantity or null if unknown
     */
    public function getCurrentStock()
    {
        if (empty($this->fk_product) || empty($this->fk_entrepot)) {
            return null;
        }

        $sql = 'SELECT SUM(ps.reel) as qty FROM ' . $this->db->prefix() . 'product_stock ps';
        $sql .= ' WHERE ps.fk_product = ' . ((int) $this->fk_product);
        $sql .= ' AND ps.fk_entrepot = ' . ((int) $this->fk_entrepot);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = $this->db->lasterror();
            return null;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        if (!empty($obj->qty)) {
            return (float) $obj->qty;
        }

        return 0.0;
    }

    /**
     * Ensure that the requested quantity can be withdrawn from the reservoir.
     *
     * @param float $qty Quantity requested
     *
     * @return bool
     */
    public function canDispense($qty)
    {
        $stock = $this->getCurrentStock();
        if ($stock === null) {
            return false;
        }

        return ($stock - (float) $qty) >= max(0, (float) $this->nivel_seguranca);
    }

    /**
     * Synchronise the measured level field with the Dolibarr stock values.
     *
     * @param User $user Current user
     *
     * @return int
     */
    public function syncLevelWithStock(User $user)
    {
        $stock = $this->getCurrentStock();
        if ($stock === null) {
            return -1;
        }

        $this->nivel = round($stock, 2);
        $this->last_sync_stock = dol_now();

        return $this->updateCommon($user, true);
    }
}
