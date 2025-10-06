<?php
/*
 * Fuel transaction business object.
 */

require_once __DIR__ . '/fleetobject.class.php';
require_once __DIR__ . '/reservatorio.class.php';
require_once __DIR__ . '/veiculo.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';

if (isModEnabled('safra')) {
    dol_include_once('/safra/class/safra.class.php');
}

/**
 * Records a fueling event for a vehicle in the fleet.
 */
class Abastecimento extends FleetObject
{
    /** @var string */
    public $element = 'abastecimento';

    /** @var string */
    public $table_element = 'frota_abastecimento';

    /** @var string */
    public $picto = 'fa-gas-pump';

    /** @var int */
    public $ismultientitymanaged = 1;

    /** @var int */
    public $isextrafieldmanaged = 1;

    public const STATUS_DRAFT = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_CANCELED = 9;

    /** @var array */
    public $fields = array(
        'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1),
        'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 5, 'notnull' => 1, 'visible' => 0, 'default' => '1', 'index' => 1),
        'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 5, 'default' => '(FUEL)', 'index' => 1, 'searchall' => 1, 'validate' => '1'),
        'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 30, 'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300'),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array(
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_VALIDATED => 'Validated',
            self::STATUS_CANCELED => 'Canceled',
        )),
        'fk_veiculo' => array('type' => 'integer:Veiculo:frota/class/veiculo.class.php:1', 'label' => 'Veiculo', 'enabled' => '1', 'position' => 60, 'notnull' => 1, 'visible' => 1, 'index' => 1),
        'fk_reservatorio' => array('type' => 'integer:Reservatorio:frota/class/reservatorio.class.php:1', 'label' => 'Reservatorio', 'enabled' => '1', 'position' => 70, 'notnull' => 1, 'visible' => 1, 'index' => 1),
        'fk_product' => array('type' => 'integer:Product:product/class/product.class.php:1::(p.fk_product_type:=:1)', 'label' => 'Product', 'enabled' => '$conf->product->enabled', 'position' => 80, 'notnull' => 1, 'visible' => 1, 'index' => 1),
        'fk_entrepot' => array('type' => 'integer:Entrepot:product/stock/class/entrepot.class.php:1', 'label' => 'Warehouse', 'enabled' => '$conf->stock->enabled', 'position' => 90, 'notnull' => 1, 'visible' => 1, 'index' => 1),
        'qty' => array('type' => 'double(12,3)', 'label' => 'QuantityPlanned', 'enabled' => '1', 'position' => 100, 'notnull' => 0, 'visible' => 1, 'isameasure' => '1'),
        'qty_real' => array('type' => 'double(12,3)', 'label' => 'QuantityReal', 'enabled' => '1', 'position' => 110, 'notnull' => 0, 'visible' => 1, 'isameasure' => '1'),
        'unit_price' => array('type' => 'price', 'label' => 'UnitPrice', 'enabled' => '1', 'position' => 120, 'notnull' => 0, 'visible' => 1),
        'amount' => array('type' => 'price', 'label' => 'Amount', 'enabled' => '1', 'position' => 130, 'notnull' => 0, 'visible' => 1, 'isameasure' => '1'),
        'km' => array('type' => 'double(12,2)', 'label' => 'CurrentMileage', 'enabled' => '1', 'position' => 140, 'notnull' => 0, 'visible' => 1),
        'horas_op' => array('type' => 'double(12,2)', 'label' => 'CurrentHours', 'enabled' => '1', 'position' => 150, 'notnull' => 0, 'visible' => 1),
        'data_ab' => array('type' => 'datetime', 'label' => 'Date', 'enabled' => '1', 'position' => 160, 'notnull' => 1, 'visible' => 1),
        'fk_safra' => array('type' => 'integer', 'label' => 'FrotaSafra', 'enabled' => 'isModEnabled("safra")', 'position' => 170, 'notnull' => -1, 'visible' => 1, 'index' => 1),
        'fk_stock_mouvement' => array('type' => 'integer', 'label' => 'StockMovement', 'enabled' => '1', 'position' => 180, 'notnull' => -1, 'visible' => -2, 'index' => 1),
        'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php:1', 'label' => 'ThirdParty', 'picto' => 'company', 'enabled' => '$conf->societe->enabled', 'position' => 300, 'notnull' => -1, 'visible' => -1, 'index' => 1),
        'fk_project' => array('type' => 'integer:Project:projet/class/project.class.php:1', 'label' => 'Project', 'picto' => 'project', 'enabled' => '$conf->project->enabled', 'position' => 310, 'notnull' => -1, 'visible' => -1, 'index' => 1),
        'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 320, 'notnull' => 0, 'visible' => 3),
        'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => '1', 'position' => 330, 'notnull' => 0, 'visible' => 0),
        'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => '1', 'position' => 340, 'notnull' => 0, 'visible' => 0),
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
    public $fk_veiculo;
    /** @var int */
    public $fk_reservatorio;
    /** @var int */
    public $fk_product;
    /** @var int */
    public $fk_entrepot;
    /** @var float */
    public $qty;
    /** @var float */
    public $qty_real;
    /** @var float */
    public $unit_price;
    /** @var float */
    public $amount;
    /** @var float */
    public $km;
    /** @var float */
    public $horas_op;
    /** @var string */
    public $data_ab;
    /** @var int */
    public $fk_safra;
    /** @var int */
    public $fk_stock_mouvement;

    /**
     * Create fueling record.
     *
     * @param User $user      Current user
     * @param bool $notrigger Disable triggers
     *
     * @return int
     */
    public function create(User $user, $notrigger = false)
    {
        if (empty($this->data_ab)) {
            $this->data_ab = dol_now();
        }

        if (empty($this->ref)) {
            $this->ref = $this->fields['ref']['default'];
        }

        return $this->createCommon($user, $notrigger);
    }

    /**
     * Validate fueling, creating stock movement.
     *
     * @param User $user      Current user
     * @param bool $notrigger Disable triggers
     *
     * @return int
     */
    public function validate(User $user, $notrigger = false)
    {
        if ($this->status != self::STATUS_DRAFT) {
            return 0;
        }

        $qty = (float) ($this->qty_real > 0 ? $this->qty_real : $this->qty);
        if ($qty <= 0) {
            $this->error = 'QuantityRequired';
            return -1;
        }

        $reservatorio = new Reservatorio($this->db);
        if ($reservatorio->fetch($this->fk_reservatorio) <= 0) {
            $this->error = 'ReservatorioNotFound';
            return -1;
        }

        if (!$reservatorio->canDispense($qty)) {
            $this->error = 'ReservatorioInsufficientStock';
            return -1;
        }

        $movement = new MouvementStock($this->db);
        $label = $this->label ?: $this->ref;
        $unitPrice = price2num($this->unit_price, 'MU') ?: 0;
        $movementDate = !empty($this->data_ab)
            ? (is_numeric($this->data_ab) ? (int) $this->data_ab : dol_stringtotime($this->data_ab))
            : dol_now();

        $resultMovement = $movement->livraison(
            $user,
            $this->fk_product,
            $this->fk_entrepot,
            $qty,
            $label,
            $unitPrice,
            $this->id,
            $this->element,
            '',
            $movementDate
        );

        if ($resultMovement <= 0) {
            $this->error = $movement->error ? $movement->error : 'ErrorCreatingStockMovement';
            $this->errors = array_merge($this->errors, $movement->errors);
            return -1;
        }

        $this->fk_stock_mouvement = $movement->id;
        $this->qty_real = $qty;
        if ($unitPrice > 0) {
            $this->amount = price2num($qty * $unitPrice, 'MT');
        }

        $this->fk_user_modif = $user->id;
        $updateResult = $this->updateCommon($user, true);
        if ($updateResult <= 0) {
            // Rollback stock movement to avoid inconsistent inventory
            $rollback = new MouvementStock($this->db);
            $rollback->reception(
                $user,
                $this->fk_product,
                $this->fk_entrepot,
                $qty,
                $label . ' (rollback)',
                $unitPrice,
                $this->id,
                $this->element,
                '',
                $movementDate
            );

            $this->error = 'ErrorUpdatingFueling';
            return -1;
        }

        $statusResult = $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'FROTA_ABASTECIMENTO_VALIDATE');
        if ($statusResult <= 0) {
            return -1;
        }

        $this->updateVehicleUsage($user);

        return $statusResult;
    }

    /**
     * Cancel fueling and revert stock movement.
     *
     * @param User $user      Current user
     * @param bool $notrigger Disable triggers
     *
     * @return int
     */
    public function cancel(User $user, $notrigger = false)
    {
        if ($this->status != self::STATUS_VALIDATED) {
            return parent::cancel($user, $notrigger);
        }

        $qty = (float) $this->qty_real;
        if ($qty <= 0) {
            $qty = (float) $this->qty;
        }

        $unitPrice = price2num($this->unit_price, 'MU') ?: 0;
        $movementDate = !empty($this->data_ab)
            ? (is_numeric($this->data_ab) ? (int) $this->data_ab : dol_stringtotime($this->data_ab))
            : dol_now();

        $movement = new MouvementStock($this->db);
        $result = $movement->reception(
            $user,
            $this->fk_product,
            $this->fk_entrepot,
            $qty,
            ($this->label ?: $this->ref) . ' (cancel)',
            $unitPrice,
            $this->id,
            $this->element,
            '',
            $movementDate
        );

        if ($result <= 0) {
            $this->error = $movement->error ? $movement->error : 'ErrorRevertingStockMovement';
            $this->errors = array_merge($this->errors, $movement->errors);
            return -1;
        }

        $this->fk_stock_mouvement = null;
        $this->fk_user_modif = $user->id;
        $this->updateCommon($user, true);

        return parent::cancel($user, $notrigger);
    }

    /**
     * Update vehicle usage metrics with the fueling data when available.
     *
     * @param User $user Current user
     */
    protected function updateVehicleUsage(User $user)
    {
        if (empty($this->fk_veiculo)) {
            return;
        }

        $vehicle = new Veiculo($this->db);
        if ($vehicle->fetch($this->fk_veiculo) <= 0) {
            return;
        }

        $hasChange = false;
        if (!empty($this->km)) {
            $vehicle->km = round(max($vehicle->km, $this->km), 2);
            $hasChange = true;
        }

        if (!empty($this->horas_op)) {
            $vehicle->horas_op = round(max($vehicle->horas_op, $this->horas_op), 2);
            $hasChange = true;
        }

        if ($hasChange) {
            $vehicle->fk_user_modif = $user->id;
            $vehicle->updateCommon($user, true);
        }
    }

    /**
     * Try fetching the associated safra object.
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
