<?php

require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

/**
 * Validates and posts all stock outputs owned by the Frota module.
 */
class FrotaStockService
{
    private $db;
    public $error = '';
    public $errors = array();

    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    public function validateOutput($productId, $warehouseId, $qty)
    {
        $qty = (float) price2num($qty, 'MS');
        if ($productId <= 0 || $warehouseId <= 0 || $qty <= 0) {
            $this->error = 'FrotaErrorInvalidStockParameters';
            return -1;
        }

        $sql = 'SELECT p.rowid, p.fk_product_type, p.stockable_product, e.statut, COALESCE(ps.reel, 0) as stock';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'product p';
        $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'entrepot e ON e.rowid = '.((int) $warehouseId);
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock ps ON ps.fk_product = p.rowid AND ps.fk_entrepot = e.rowid';
        $sql .= ' WHERE p.rowid = '.((int) $productId);
        $sql .= ' AND p.entity IN ('.getEntity('product').')';
        $sql .= ' AND e.entity IN ('.getEntity('stock').')';
        $resql = $this->db->query($sql);
        if (!$resql || !($obj = $this->db->fetch_object($resql))) {
            $this->error = $resql ? 'FrotaErrorProductOrWarehouseNotFound' : $this->db->lasterror();
            return -1;
        }
        if ((int) $obj->fk_product_type !== 0 || empty($obj->stockable_product)) {
            $this->error = 'FrotaErrorProductNotStockManaged';
            return -1;
        }
        if ((int) $obj->statut <= 0) {
            $this->error = 'FrotaErrorWarehouseClosed';
            return -1;
        }
        if ((float) $obj->stock + 0.0000001 < $qty) {
            $this->error = 'FrotaErrorInsufficientStock';
            return -1;
        }

        return 1;
    }

    public function createOutput(User $user, $originType, $originId, $lineId, $productId, $warehouseId, $qty, $unitPrice, $label, $date = '')
    {
        if ($this->validateOutput($productId, $warehouseId, $qty) < 0) {
            return -1;
        }

        $movement = new MouvementStock($this->db);
        $movement->setOrigin($originType, (int) $originId, (int) $lineId, (int) $lineId);
        $result = $movement->livraison(
            $user,
            (int) $productId,
            (int) $warehouseId,
            (float) price2num($qty, 'MS'),
            (float) price2num($unitPrice, 'MU'),
            $label,
            $date
        );
        if ($result <= 0 || empty($movement->id)) {
            $this->error = !empty($movement->error) ? $movement->error : 'FrotaErrorStockMovement';
            $this->errors = !empty($movement->errors) ? $movement->errors : array($this->error);
            return -1;
        }

        return (int) $movement->id;
    }
}

