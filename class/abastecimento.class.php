<?php

require_once __DIR__.'/frotaobject.class.php';
require_once __DIR__.'/frotastockservice.class.php';
require_once __DIR__.'/veiculo.class.php';

class Abastecimento extends FrotaObject
{
    const STATUS_DRAFT = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_CANCELED = 9;

    public $element = 'abastecimento';
    public $table_element = 'frota_abastecimento';
    public $picto = 'fa-gas-pump';

    public $fk_veiculo;
    public $fk_product;
    public $fk_warehouse;
    public $fk_stock_movement;
    public $date_fueling;
    public $qty;
    public $unit_price;
    public $total_amount;
    public $horimeter;
    public $odometer;
    public $average_consumption;
    public $consumption_basis;
    public $status;
    public $date_confirmed;
    public $fk_user;
    public $fk_user_confirm;
    public $note;
    public $fields = array();

    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
        $this->fields = array_merge(self::auditFields(), array(
            'ref'=>array('type'=>'varchar(128)', 'label'=>'Ref', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'noteditable'=>1),
            'fk_veiculo'=>array('type'=>'integer:Veiculo:custom/frota/class/veiculo.class.php:0:(status:=:1)', 'label'=>'FrotaVehicle', 'enabled'=>1, 'position'=>20, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'foreignkey'=>'frota_veiculo.rowid', 'picto'=>'fa-tractor', 'css'=>'minwidth300 maxwidth500'),
            'fk_product'=>array('type'=>'integer:Product:product/class/product.class.php:0:((fk_product_type:=:0) AND (stockable_product:=:1))', 'label'=>'Product', 'enabled'=>1, 'position'=>30, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'foreignkey'=>'product.rowid', 'picto'=>'product', 'css'=>'minwidth300 maxwidth500'),
            'fk_warehouse'=>array('type'=>'integer:Entrepot:product/stock/class/entrepot.class.php:0:(statut:>:0)', 'label'=>'Warehouse', 'enabled'=>1, 'position'=>40, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'foreignkey'=>'entrepot.rowid', 'picto'=>'stock', 'css'=>'minwidth300 maxwidth500'),
            'fk_stock_movement'=>array('type'=>'integer', 'label'=>'StockMovement', 'position'=>45, 'notnull'=>0, 'visible'=>0),
            'date_fueling'=>array('type'=>'datetime', 'label'=>'FrotaFuelingDate', 'position'=>50, 'notnull'=>1, 'visible'=>1),
            'qty'=>array('type'=>'double(24,8)', 'label'=>'Qty', 'position'=>60, 'notnull'=>1, 'visible'=>1),
            'unit_price'=>array('type'=>'double(24,8)', 'label'=>'UnitPrice', 'position'=>70, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'total_amount'=>array('type'=>'double(24,8)', 'label'=>'Total', 'position'=>80, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'horimeter'=>array('type'=>'double(24,8)', 'label'=>'FrotaHorimeter', 'position'=>90, 'notnull'=>0, 'visible'=>1),
            'odometer'=>array('type'=>'double(24,8)', 'label'=>'FrotaOdometer', 'position'=>100, 'notnull'=>0, 'visible'=>1),
            'average_consumption'=>array('type'=>'double(24,8)', 'label'=>'FrotaAverageConsumption', 'position'=>110, 'notnull'=>0, 'visible'=>1),
            'consumption_basis'=>array('type'=>'varchar(16)', 'label'=>'FrotaConsumptionBasis', 'position'=>120, 'notnull'=>0, 'visible'=>1),
            'status'=>array('type'=>'integer', 'label'=>'Status', 'position'=>130, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'date_confirmed'=>array('type'=>'datetime', 'label'=>'FrotaConfirmedAt', 'position'=>140, 'notnull'=>0, 'visible'=>0),
            'fk_user'=>array('type'=>'integer', 'label'=>'FrotaOperator', 'position'=>145, 'notnull'=>0, 'visible'=>1),
            'fk_user_confirm'=>array('type'=>'integer', 'label'=>'FrotaConfirmedBy', 'position'=>150, 'notnull'=>0, 'visible'=>0),
            'note'=>array('type'=>'text', 'label'=>'Note', 'position'=>160, 'notnull'=>0, 'visible'=>0),
        ));
    }

    public function create(User $user, $notrigger = false)
    {
        $this->status = self::STATUS_DRAFT;
        $this->fk_user = empty($this->fk_user) ? $user->id : $this->fk_user;
        $this->total_amount = (float) price2num($this->qty, 'MS') * (float) price2num($this->unit_price, 'MU');

        $this->db->begin();
        $this->ref = 'ABS-TMP-'.strtoupper(substr(md5(uniqid('', true)), 0, 16));
        $result = parent::create($user, true);
        if ($result <= 0) {
            $this->db->rollback();
            return -1;
        }

        $this->ref = $this->buildReference();
        if (parent::update($user, true) < 0) {
            $this->db->rollback();
            return -1;
        }

        if (!$notrigger && $this->call_trigger(strtoupper(get_class($this)).'_CREATE', $user) < 0) {
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();
        return $result;
    }

    public function update(User $user, $notrigger = false)
    {
        if ((int) $this->status !== self::STATUS_DRAFT) {
            $this->error = 'FrotaErrorConfirmedRecordLocked';
            return -1;
        }
        $this->total_amount = (float) price2num($this->qty, 'MS') * (float) price2num($this->unit_price, 'MU');
        return parent::update($user, $notrigger);
    }

    public function cancel(User $user)
    {
        if ((int) $this->status !== self::STATUS_DRAFT || !empty($this->fk_stock_movement)) {
            $this->error = 'FrotaErrorInvalidStatusTransition';
            return -1;
        }
        $this->status = self::STATUS_CANCELED;
        return parent::update($user, true);
    }

    public function delete(User $user, $notrigger = false)
    {
        if ((int) $this->status !== self::STATUS_DRAFT || !empty($this->fk_stock_movement)) {
            $this->error = 'FrotaErrorConfirmedRecordLocked';
            return -1;
        }
        return parent::delete($user, $notrigger);
    }

    public function confirm(User $user)
    {
        if ((int) $this->status === self::STATUS_CONFIRMED) {
            return 0;
        }
        if ((int) $this->status !== self::STATUS_DRAFT || !empty($this->fk_stock_movement)) {
            $this->error = 'FrotaErrorInvalidStatusTransition';
            return -1;
        }

        $vehicle = new Veiculo($this->db);
        if ($vehicle->fetch((int) $this->fk_veiculo) <= 0 || (int) $vehicle->entity !== (int) $this->entity) {
            $this->error = 'FrotaErrorVehicleNotFound';
            return -1;
        }

        $stock = new FrotaStockService($this->db);
        if ($stock->validateOutput($this->fk_product, $this->fk_warehouse, $this->qty) < 0) {
            $this->setObjectError($stock, 'FrotaErrorStockValidation');
            return -1;
        }

        $this->db->begin();
        $movementId = $stock->createOutput(
            $user,
            'abastecimento@frota',
            $this->id,
            0,
            $this->fk_product,
            $this->fk_warehouse,
            $this->qty,
            $this->unit_price,
            'Abastecimento '.$this->ref,
            $this->date_fueling
        );
        if ($movementId <= 0) {
            $this->setObjectError($stock, 'FrotaErrorStockMovement');
            $this->db->rollback();
            return -1;
        }

        $this->calculateConsumption();
        $this->fk_stock_movement = $movementId;
        $this->total_amount = (float) price2num($this->qty, 'MS') * (float) price2num($this->unit_price, 'MU');
        $this->status = self::STATUS_CONFIRMED;
        $this->date_confirmed = $this->db->idate(dol_now());
        $this->fk_user_confirm = $user->id;
        if (parent::update($user, true) < 0 || $vehicle->updateReadings($user, $this->horimeter, $this->odometer) < 0) {
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();
        return 1;
    }

    private function calculateConsumption()
    {
        $sql = 'SELECT horimeter, odometer FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql .= ' WHERE entity = '.((int) $this->entity).' AND fk_veiculo = '.((int) $this->fk_veiculo);
        $sql .= ' AND status = '.self::STATUS_CONFIRMED.' AND rowid <> '.((int) $this->id);
        $sql .= " AND date_fueling <= '".$this->db->escape($this->date_fueling)."'";
        $sql .= ' ORDER BY date_fueling DESC, rowid DESC LIMIT 1';
        $resql = $this->db->query($sql);
        $previous = $resql ? $this->db->fetch_object($resql) : null;

        $this->average_consumption = null;
        $this->consumption_basis = null;
        if ($previous && $this->horimeter !== null && $previous->horimeter !== null) {
            $delta = (float) $this->horimeter - (float) $previous->horimeter;
            if ($delta > 0) {
                $this->average_consumption = (float) $this->qty / $delta;
                $this->consumption_basis = 'L/H';
                return;
            }
        }
        if ($previous && $this->odometer !== null && $previous->odometer !== null) {
            $delta = (float) $this->odometer - (float) $previous->odometer;
            if ($delta > 0) {
                $this->average_consumption = (float) $this->qty / $delta;
                $this->consumption_basis = 'L/KM';
            }
        }
    }

    private function buildReference()
    {
        $datePart = preg_replace('/[^0-9]/', '', substr((string) $this->date_fueling, 0, 10));
        if (strlen($datePart) !== 8) {
            $datePart = dol_print_date(dol_now(), '%Y%m%d');
        }

        return 'ABS-'.$datePart.'-'.sprintf('%06d', (int) $this->id);
    }
}
