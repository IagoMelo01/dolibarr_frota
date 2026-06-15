<?php

require_once __DIR__.'/frotaobject.class.php';
require_once __DIR__.'/frotastockservice.class.php';
require_once __DIR__.'/veiculo.class.php';
require_once __DIR__.'/implemento.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

class Manutencao extends FrotaObject
{
    const STATUS_DRAFT = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_CANCELED = 9;

    public $element = 'manutencao';
    public $table_element = 'frota_manutencao';
    public $picto = 'fa-tools';

    public $fk_veiculo;
    public $fk_implement;
    public $type;
    public $status;
    public $date_planned;
    public $date_start;
    public $date_end;
    public $due_horimeter;
    public $due_odometer;
    public $horimeter;
    public $odometer;
    public $is_periodic;
    public $period_days;
    public $period_hours;
    public $period_km;
    public $fk_origin_maintenance;
    public $fk_next_maintenance;
    public $description;
    public $labor_cost;
    public $parts_cost;
    public $total_cost;
    public $fk_user_assigned;
    public $lines = array();
    public $fields = array();

    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
        $this->fields = array_merge(self::auditFields(), array(
            'ref'=>array('type'=>'varchar(128)', 'label'=>'Ref', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'noteditable'=>1),
            'fk_veiculo'=>array('type'=>'integer:Veiculo:custom/frota/class/veiculo.class.php:0:(status:=:1)', 'label'=>'FrotaVehicle', 'enabled'=>1, 'position'=>20, 'notnull'=>0, 'visible'=>1, 'index'=>1, 'foreignkey'=>'frota_veiculo.rowid', 'picto'=>'fa-tractor', 'css'=>'minwidth300 maxwidth500'),
            'fk_implement'=>array('type'=>'integer:Implemento:custom/frota/class/implemento.class.php:0:(status:=:1)', 'label'=>'FrotaImplement', 'enabled'=>1, 'position'=>21, 'notnull'=>0, 'visible'=>1, 'index'=>1, 'foreignkey'=>'frota_implemento.rowid', 'picto'=>'fa-cogs', 'css'=>'minwidth300 maxwidth500'),
            'type'=>array('type'=>'varchar(16)', 'label'=>'Type', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'default'=>'preventiva'),
            'status'=>array('type'=>'integer', 'label'=>'Status', 'position'=>40, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'date_planned'=>array('type'=>'datetime', 'label'=>'FrotaPlannedDate', 'position'=>45, 'notnull'=>0, 'visible'=>1),
            'date_start'=>array('type'=>'datetime', 'label'=>'DateStart', 'position'=>50, 'notnull'=>0, 'visible'=>1),
            'date_end'=>array('type'=>'datetime', 'label'=>'DateEnd', 'position'=>60, 'notnull'=>0, 'visible'=>1),
            'due_horimeter'=>array('type'=>'double(24,8)', 'label'=>'FrotaMaintenanceDueHorimeter', 'position'=>65, 'notnull'=>0, 'visible'=>1),
            'due_odometer'=>array('type'=>'double(24,8)', 'label'=>'FrotaMaintenanceDueOdometer', 'position'=>66, 'notnull'=>0, 'visible'=>1),
            'horimeter'=>array('type'=>'double(24,8)', 'label'=>'FrotaHorimeter', 'position'=>70, 'notnull'=>0, 'visible'=>1),
            'odometer'=>array('type'=>'double(24,8)', 'label'=>'FrotaOdometer', 'position'=>80, 'notnull'=>0, 'visible'=>1),
            'is_periodic'=>array('type'=>'boolean', 'label'=>'FrotaPeriodicMaintenance', 'position'=>85, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'period_days'=>array('type'=>'integer', 'label'=>'FrotaPeriodDays', 'position'=>86, 'notnull'=>0, 'visible'=>1),
            'period_hours'=>array('type'=>'double(24,8)', 'label'=>'FrotaPeriodHours', 'position'=>87, 'notnull'=>0, 'visible'=>1),
            'period_km'=>array('type'=>'double(24,8)', 'label'=>'FrotaPeriodKm', 'position'=>88, 'notnull'=>0, 'visible'=>1),
            'fk_origin_maintenance'=>array('type'=>'integer:Manutencao:custom/frota/class/manutencao.class.php', 'label'=>'FrotaPreviousMaintenance', 'position'=>89, 'notnull'=>0, 'visible'=>0, 'index'=>1),
            'fk_next_maintenance'=>array('type'=>'integer:Manutencao:custom/frota/class/manutencao.class.php', 'label'=>'FrotaNextMaintenance', 'position'=>90, 'notnull'=>0, 'visible'=>0, 'index'=>1),
            'description'=>array('type'=>'text', 'label'=>'Description', 'position'=>100, 'notnull'=>1, 'visible'=>1),
            'labor_cost'=>array('type'=>'double(24,8)', 'label'=>'FrotaLaborCost', 'position'=>110, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'parts_cost'=>array('type'=>'double(24,8)', 'label'=>'FrotaPartsCost', 'position'=>120, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'total_cost'=>array('type'=>'double(24,8)', 'label'=>'FrotaTotalCost', 'position'=>130, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'fk_user_assigned'=>array('type'=>'integer:User:user/class/user.class.php:0:(statut:=:1)', 'label'=>'FrotaAssignedUser', 'enabled'=>1, 'position'=>140, 'notnull'=>0, 'visible'=>1, 'picto'=>'user', 'css'=>'minwidth300 maxwidth500'),
        ));
    }

    public function create(User $user, $notrigger = false)
    {
        if ($this->validateMaintenanceAsset() < 0 || $this->normalizeSchedule() < 0) {
            return -1;
        }
        $this->status = self::STATUS_DRAFT;
        $this->parts_cost = 0;
        $this->total_cost = (float) price2num($this->labor_cost, 'MU');

        $this->db->begin();
        $this->ref = 'MAN-TMP-'.strtoupper(substr(md5(uniqid('', true)), 0, 16));
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
        if (in_array((int) $this->status, array(self::STATUS_COMPLETED, self::STATUS_CANCELED), true)) {
            $this->error = 'FrotaErrorCompletedRecordLocked';
            return -1;
        }
        if ($this->validateMaintenanceAsset() < 0 || $this->normalizeSchedule() < 0) {
            return -1;
        }
        $this->refreshTotals();
        return parent::update($user, $notrigger);
    }

    public function start(User $user)
    {
        if ((int) $this->status !== self::STATUS_DRAFT) {
            $this->error = 'FrotaErrorInvalidStatusTransition';
            return -1;
        }
        $this->status = self::STATUS_IN_PROGRESS;
        $this->date_start = empty($this->date_start) ? $this->db->idate(dol_now()) : $this->date_start;
        return parent::update($user, true);
    }

    public function fetchLines()
    {
        $this->lines = array();
        $line = new ManutencaoLine($this->db);
        $sql = 'SELECT '.$line->getFieldList('l').' FROM '.MAIN_DB_PREFIX.$line->table_element.' l';
        $sql .= ' WHERE l.fk_manutencao = '.((int) $this->id).' AND l.entity = '.((int) $this->entity).' ORDER BY l.rowid';
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return -1;
        }
        while ($obj = $this->db->fetch_object($resql)) {
            $entry = new ManutencaoLine($this->db);
            $entry->setVarsFromFetchObj($obj);
            $this->lines[] = $entry;
        }
        return count($this->lines);
    }

    public function refreshTotals()
    {
        $sql = 'SELECT COALESCE(SUM(total_amount), 0) total FROM '.MAIN_DB_PREFIX.'frota_manutencao_line';
        $sql .= ' WHERE entity = '.((int) $this->entity).' AND fk_manutencao = '.((int) $this->id);
        $resql = $this->db->query($sql);
        $obj = $resql ? $this->db->fetch_object($resql) : null;
        $this->parts_cost = $obj ? (float) $obj->total : 0;
        $this->total_cost = (float) price2num($this->labor_cost, 'MU') + $this->parts_cost;
        return 1;
    }

    public function complete(User $user)
    {
        if ((int) $this->status === self::STATUS_COMPLETED) {
            return 0;
        }
        if (!in_array((int) $this->status, array(self::STATUS_DRAFT, self::STATUS_IN_PROGRESS), true)) {
            $this->error = 'FrotaErrorInvalidStatusTransition';
            return -1;
        }

        $asset = $this->getMaintenanceAsset();
        if (!$asset) {
            return -1;
        }
        if ($this->fetchLines() < 0) {
            return -1;
        }
        if ($this->normalizeSchedule() < 0) {
            return -1;
        }

        $stock = new FrotaStockService($this->db);
        foreach ($this->lines as $line) {
            if (!empty($line->fk_stock_movement)) {
                $this->error = 'FrotaErrorMaintenanceLineAlreadyConsumed';
                return -1;
            }
            if ($stock->validateOutput($line->fk_product, $line->fk_warehouse, $line->qty) < 0) {
                $this->setObjectError($stock, 'FrotaErrorStockValidation');
                return -1;
            }
        }

        $this->db->begin();
        $this->date_end = empty($this->date_end) ? $this->db->idate(dol_now()) : $this->date_end;
        $this->date_start = empty($this->date_start) ? (!empty($this->date_planned) ? $this->date_planned : $this->date_end) : $this->date_start;
        foreach ($this->lines as $line) {
            $movementId = $stock->createOutput(
                $user,
                'manutencao@frota',
                $this->id,
                $line->id,
                $line->fk_product,
                $line->fk_warehouse,
                $line->qty,
                $line->unit_price,
                'Manutencao '.$this->ref.' item '.$line->id,
                $this->date_end
            );
            if ($movementId <= 0) {
                $this->setObjectError($stock, 'FrotaErrorStockMovement');
                $this->db->rollback();
                return -1;
            }
            $line->fk_stock_movement = $movementId;
            if ($line->setStockMovement($user, $movementId) < 0) {
                $this->db->rollback();
                return -1;
            }
        }

        $this->refreshTotals();
        $this->status = self::STATUS_COMPLETED;
        if ($asset instanceof Veiculo && $asset->updateReadings($user, $this->horimeter, $this->odometer) < 0) {
            $this->db->rollback();
            return -1;
        }
        if ($this->createNextMaintenance($user, $asset) < 0 || parent::update($user, true) < 0) {
            $this->db->rollback();
            return -1;
        }
        $this->db->commit();
        return 1;
    }

    public function cancel(User $user)
    {
        if (!in_array((int) $this->status, array(self::STATUS_DRAFT, self::STATUS_IN_PROGRESS), true)) {
            $this->error = 'FrotaErrorInvalidStatusTransition';
            return -1;
        }
        $this->status = self::STATUS_CANCELED;
        return parent::update($user, true);
    }

    public function delete(User $user, $notrigger = false)
    {
        if (in_array((int) $this->status, array(self::STATUS_COMPLETED, self::STATUS_CANCELED), true)) {
            $this->error = 'FrotaErrorCompletedRecordLocked';
            return -1;
        }
        if (!empty($this->fk_origin_maintenance)) {
            $this->error = 'FrotaErrorGeneratedMaintenanceDeletion';
            return -1;
        }
        $this->fetchLines();
        foreach ($this->lines as $line) {
            if (!empty($line->fk_stock_movement)) {
                $this->error = 'FrotaErrorMaintenanceLineAlreadyConsumed';
                return -1;
            }
            $line->delete($user, true);
        }
        return parent::delete($user, $notrigger);
    }

    private function normalizeSchedule()
    {
        $this->is_periodic = empty($this->is_periodic) ? 0 : 1;
        $this->period_days = (int) $this->period_days > 0 ? (int) $this->period_days : null;
        $this->period_hours = (float) $this->period_hours > 0 ? (float) price2num($this->period_hours, 'MS') : null;
        $this->period_km = (float) $this->period_km > 0 ? (float) price2num($this->period_km, 'MS') : null;
        $this->due_horimeter = (float) $this->due_horimeter > 0 ? (float) price2num($this->due_horimeter, 'MS') : null;
        $this->due_odometer = (float) $this->due_odometer > 0 ? (float) price2num($this->due_odometer, 'MS') : null;
        $this->horimeter = (float) $this->horimeter > 0 ? (float) price2num($this->horimeter, 'MS') : null;
        $this->odometer = (float) $this->odometer > 0 ? (float) price2num($this->odometer, 'MS') : null;
        if (!empty($this->fk_implement) && (!empty($this->period_hours) || !empty($this->period_km) || !empty($this->due_horimeter) || !empty($this->due_odometer) || !empty($this->horimeter) || !empty($this->odometer))) {
            $this->error = 'FrotaErrorImplementMaintenanceMeterPeriod';
            return -1;
        }
        if (!$this->is_periodic) {
            $this->period_days = null;
            $this->period_hours = null;
            $this->period_km = null;
            return 1;
        }
        if ($this->type !== 'preventiva') {
            $this->error = 'FrotaErrorPeriodicMaintenanceMustBePreventive';
            return -1;
        }
        if (empty($this->period_days) && empty($this->period_hours) && empty($this->period_km)) {
            $this->error = 'FrotaErrorPeriodicMaintenanceNeedsInterval';
            return -1;
        }
        return 1;
    }

    private function buildReference()
    {
        $referenceDate = !empty($this->date_planned) ? $this->date_planned : (!empty($this->date_start) ? $this->date_start : $this->db->idate(dol_now()));
        $datePart = preg_replace('/[^0-9]/', '', substr((string) $referenceDate, 0, 10));
        return 'MAN-'.$datePart.'-'.sprintf('%06d', (int) $this->id);
    }

    private function createNextMaintenance(User $user, $asset)
    {
        if (!$this->is_periodic || !empty($this->fk_next_maintenance)) {
            return 0;
        }

        $next = new Manutencao($this->db);
        $next->entity = (int) $this->entity;
        $next->fk_veiculo = !empty($this->fk_veiculo) ? (int) $this->fk_veiculo : null;
        $next->fk_implement = !empty($this->fk_implement) ? (int) $this->fk_implement : null;
        $next->type = 'preventiva';
        $next->date_planned = !empty($this->period_days) ? $this->db->idate(dol_time_plus_duree($this->db->jdate($this->date_end), (int) $this->period_days, 'd')) : null;
        $next->due_horimeter = $asset instanceof Veiculo && !empty($this->period_hours) ? (float) $asset->horimeter + (float) $this->period_hours : null;
        $next->due_odometer = $asset instanceof Veiculo && !empty($this->period_km) ? (float) $asset->odometer + (float) $this->period_km : null;
        $next->is_periodic = 1;
        $next->period_days = $this->period_days;
        $next->period_hours = $this->period_hours;
        $next->period_km = $this->period_km;
        $next->fk_origin_maintenance = (int) $this->id;
        $next->description = $this->description;
        $next->labor_cost = $this->labor_cost;
        $next->fk_user_assigned = $this->fk_user_assigned;
        if ($next->create($user, true) <= 0) {
            $this->setObjectError($next, 'FrotaErrorNextMaintenanceCreation');
            return -1;
        }

        foreach ($this->lines as $sourceLine) {
            $line = new ManutencaoLine($this->db);
            $line->entity = (int) $this->entity;
            $line->fk_manutencao = (int) $next->id;
            $line->fk_product = (int) $sourceLine->fk_product;
            $line->fk_warehouse = (int) $sourceLine->fk_warehouse;
            $line->qty = $sourceLine->qty;
            $line->unit_price = $sourceLine->unit_price;
            $line->note = $sourceLine->note;
            if ($line->create($user, true) <= 0) {
                $this->setObjectError($line, 'FrotaErrorNextMaintenanceCreation');
                return -1;
            }
        }
        $next->refreshTotals();
        if ($next->update($user, true) <= 0) {
            $this->setObjectError($next, 'FrotaErrorNextMaintenanceCreation');
            return -1;
        }

        $this->fk_next_maintenance = (int) $next->id;
        return $next->id;
    }

    private function validateMaintenanceAsset()
    {
        $hasVehicle = !empty($this->fk_veiculo);
        $hasImplement = !empty($this->fk_implement);
        if (!$hasVehicle && !$hasImplement) {
            $this->error = 'FrotaErrorMaintenanceAssetRequired';
            return -1;
        }
        if ($hasVehicle && $hasImplement) {
            $this->error = 'FrotaErrorMaintenanceSingleAsset';
            return -1;
        }
        return $this->getMaintenanceAsset() ? 1 : -1;
    }

    private function getMaintenanceAsset()
    {
        global $conf;

        if ((!empty($this->fk_veiculo) && !empty($this->fk_implement)) || (empty($this->fk_veiculo) && empty($this->fk_implement))) {
            $this->error = !empty($this->fk_veiculo) ? 'FrotaErrorMaintenanceSingleAsset' : 'FrotaErrorMaintenanceAssetRequired';
            return null;
        }

        $isVehicle = !empty($this->fk_veiculo);
        $asset = $isVehicle ? new Veiculo($this->db) : new Implemento($this->db);
        $assetId = $isVehicle ? (int) $this->fk_veiculo : (int) $this->fk_implement;
        $entity = !empty($this->entity) ? (int) $this->entity : (int) $conf->entity;
        if ($asset->fetch($assetId) <= 0 || (int) $asset->entity !== $entity) {
            $this->error = $isVehicle ? 'FrotaErrorVehicleNotFound' : 'FrotaErrorImplementNotFound';
            return null;
        }
        return $asset;
    }
}

class ManutencaoLine extends FrotaObject
{
    public $element = 'manutencaoline';
    public $table_element = 'frota_manutencao_line';
    public $picto = 'product';

    public $fk_manutencao;
    public $fk_product;
    public $fk_warehouse;
    public $fk_stock_movement;
    public $qty;
    public $unit_price;
    public $total_amount;
    public $note;
    public $fields = array();

    public function __construct(DoliDB $db)
    {
        parent::__construct($db);
        $this->fields = array_merge(self::auditFields(), array(
            'fk_manutencao'=>array('type'=>'integer', 'label'=>'FrotaMaintenance', 'position'=>10, 'notnull'=>1, 'visible'=>1),
            'fk_product'=>array('type'=>'integer:Product:product/class/product.class.php:0:((fk_product_type:=:0) AND (stockable_product:=:1))', 'label'=>'Product', 'enabled'=>1, 'position'=>20, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'foreignkey'=>'product.rowid', 'picto'=>'product', 'css'=>'minwidth300 maxwidth500'),
            'fk_warehouse'=>array('type'=>'integer:Entrepot:product/stock/class/entrepot.class.php:0:(statut:>:0)', 'label'=>'Warehouse', 'enabled'=>1, 'position'=>30, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'foreignkey'=>'entrepot.rowid', 'picto'=>'stock', 'css'=>'minwidth300 maxwidth500'),
            'fk_stock_movement'=>array('type'=>'integer', 'label'=>'StockMovement', 'position'=>40, 'notnull'=>0, 'visible'=>1),
            'qty'=>array('type'=>'double(24,8)', 'label'=>'Qty', 'position'=>50, 'notnull'=>1, 'visible'=>1),
            'unit_price'=>array('type'=>'double(24,8)', 'label'=>'UnitPrice', 'position'=>60, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'total_amount'=>array('type'=>'double(24,8)', 'label'=>'Total', 'position'=>70, 'notnull'=>1, 'visible'=>1, 'default'=>0),
            'note'=>array('type'=>'text', 'label'=>'Note', 'position'=>80, 'notnull'=>0, 'visible'=>0),
        ));
    }

    public function create(User $user, $notrigger = false)
    {
        $maintenance = $this->getEditableMaintenance();
        if (!$maintenance) {
            return -1;
        }
        $this->entity = (int) $maintenance->entity;
        $this->total_amount = (float) price2num($this->qty, 'MS') * (float) price2num($this->unit_price, 'MU');
        return parent::create($user, $notrigger);
    }

    public function update(User $user, $notrigger = false)
    {
        if (!empty($this->fk_stock_movement)) {
            $this->error = 'FrotaErrorConsumedLineLocked';
            return -1;
        }
        if (!$this->getEditableMaintenance()) {
            return -1;
        }
        $this->total_amount = (float) price2num($this->qty, 'MS') * (float) price2num($this->unit_price, 'MU');
        return parent::update($user, $notrigger);
    }

    public function delete(User $user, $notrigger = false)
    {
        if (!empty($this->fk_stock_movement)) {
            $this->error = 'FrotaErrorConsumedLineLocked';
            return -1;
        }
        if (!$this->getEditableMaintenance()) {
            return -1;
        }
        return parent::delete($user, $notrigger);
    }

    public function setStockMovement(User $user, $movementId)
    {
        $this->fk_stock_movement = (int) $movementId;
        return parent::update($user, true);
    }

    private function getEditableMaintenance()
    {
        $maintenance = new Manutencao($this->db);
        if ($maintenance->fetch((int) $this->fk_manutencao) <= 0) {
            $this->error = 'FrotaErrorMaintenanceNotFound';
            return null;
        }
        if (in_array((int) $maintenance->status, array(Manutencao::STATUS_COMPLETED, Manutencao::STATUS_CANCELED), true)) {
            $this->error = 'FrotaErrorCompletedRecordLocked';
            return null;
        }
        if (!empty($this->entity) && (int) $this->entity !== (int) $maintenance->entity) {
            $this->error = 'FrotaErrorEntityMismatch';
            return null;
        }
        return $maintenance;
    }
}
