<?php

require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

class FrotaSeeder
{
    private $db;
    public $error = '';
    public $errors = array();

    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    public function run(User $user)
    {
        global $conf;

        $this->db->begin();
        foreach (array('Estoque Geral', 'Combustíveis', 'Peças e Manutenção') as $warehouseRef) {
            if ($this->ensureWarehouse($warehouseRef, $user) < 0) {
                $this->db->rollback();
                return -1;
            }
        }

        $categories = array(
            'COMBUSTIVEIS'=>'Combustíveis',
            'LUBRIFICANTES-FLUIDOS'=>'Lubrificantes e Fluidos',
            'FILTROS'=>'Filtros',
            'PECAS-MANUTENCAO'=>'Peças de Manutenção',
            'PNEUS-RODADOS'=>'Pneus e Rodados',
            'ELETRICA-ELETRONICA'=>'Elétrica e Eletrônica',
            'HIDRAULICA'=>'Hidráulica',
            'CORREIAS-ROLAMENTOS-MANGUEIRAS'=>'Correias, Rolamentos e Mangueiras',
            'IRRIGACAO'=>'Irrigação',
            'FERRAMENTAS-CONSUMIVEIS'=>'Ferramentas e Consumíveis',
        );
        $categoryIds = array();
        foreach ($categories as $code => $label) {
            $categoryIds[$code] = $this->ensureCategory('FROTA-CAT-'.$code, $label, $user);
            if ($categoryIds[$code] <= 0) {
                $this->db->rollback();
                return -1;
            }
        }

        $products = array(
            array('FROTA-DIESEL-S10', 'Diesel S10', 'COMBUSTIVEIS'),
            array('FROTA-DIESEL-S500', 'Diesel S500', 'COMBUSTIVEIS'),
            array('FROTA-ARLA-32', 'ARLA 32', 'LUBRIFICANTES-FLUIDOS'),
            array('FROTA-GASOLINA-COMUM', 'Gasolina Comum', 'COMBUSTIVEIS'),
            array('FROTA-ETANOL', 'Etanol', 'COMBUSTIVEIS'),
            array('FROTA-OLEO-MOTOR', 'Óleo Motor', 'LUBRIFICANTES-FLUIDOS'),
            array('FROTA-OLEO-HIDRAULICO', 'Óleo Hidráulico', 'LUBRIFICANTES-FLUIDOS'),
            array('FROTA-OLEO-TRANSMISSAO', 'Óleo de Transmissão', 'LUBRIFICANTES-FLUIDOS'),
            array('FROTA-GRAXA-LUBRIFICANTE', 'Graxa Lubrificante', 'LUBRIFICANTES-FLUIDOS'),
            array('FROTA-FILTRO-OLEO', 'Filtro de Óleo', 'FILTROS'),
            array('FROTA-FILTRO-COMBUSTIVEL', 'Filtro de Combustível', 'FILTROS'),
            array('FROTA-FILTRO-AR', 'Filtro de Ar', 'FILTROS'),
            array('FROTA-FILTRO-HIDRAULICO', 'Filtro Hidráulico', 'FILTROS'),
        );
        foreach ($products as $entry) {
            if ($this->ensureProduct($entry[0], $entry[1], $categoryIds[$entry[2]], $user) < 0) {
                $this->db->rollback();
                return -1;
            }
        }

        $vehicleTypes = array(
            'TRATOR'=>'Trator', 'COLHEITADEIRA'=>'Colheitadeira', 'PULVERIZADOR'=>'Pulverizador',
            'PLANTADEIRA'=>'Plantadeira', 'DISTRIBUIDOR-ADUBO'=>'Distribuidor de Adubo',
            'CAMINHAO'=>'Caminhão', 'CAMINHONETE'=>'Caminhonete', 'MOTO'=>'Moto',
            'PIVO-CENTRAL'=>'Pivô Central', 'GERADOR'=>'Gerador', 'BOMBA-IRRIGACAO'=>'Bomba de Irrigação',
            'OUTRO'=>'Outro',
        );
        foreach ($vehicleTypes as $code => $label) {
            if ($this->ensureDictionary('frota_veiculo_type', $code, $label, $user, (int) $conf->entity) < 0) {
                $this->db->rollback();
                return -1;
            }
        }

        $operations = array(
            'PREPARO-SOLO'=>'Preparo de Solo', 'PLANTIO'=>'Plantio', 'ADUBACAO'=>'Adubação',
            'PULVERIZACAO'=>'Pulverização', 'COLHEITA'=>'Colheita', 'TRANSPORTE'=>'Transporte',
            'IRRIGACAO'=>'Irrigação', 'OUTRA'=>'Outra',
        );
        foreach ($operations as $code => $label) {
            if ($this->ensureDictionary('frota_tipo_operacao', $code, $label, $user, (int) $conf->entity) < 0) {
                $this->db->rollback();
                return -1;
            }
        }

        $this->db->commit();
        return 1;
    }

    private function ensureWarehouse($ref, User $user)
    {
        global $conf;
        $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX."entrepot WHERE entity = ".((int) $conf->entity)." AND ref = '".$this->db->escape($ref)."'";
        $resql = $this->db->query($sql);
        if ($resql && ($obj = $this->db->fetch_object($resql))) {
            return (int) $obj->rowid;
        }
        $warehouse = new Entrepot($this->db);
        $warehouse->label = $ref;
        $warehouse->lieu = $ref;
        $warehouse->description = 'Criado pelo seed Frota Agrícola';
        $warehouse->statut = Entrepot::STATUS_OPEN_ALL;
        $warehouse->warehouse_usage = Entrepot::USAGE_INTERNAL;
        $result = $warehouse->create($user);
        return $this->capture($result, $warehouse);
    }

    private function ensureCategory($refExt, $label, User $user)
    {
        global $conf;
        $sql = 'SELECT rowid, ref_ext FROM '.MAIN_DB_PREFIX."categorie WHERE entity = ".((int) $conf->entity).' AND type = 0';
        $sql .= " AND (ref_ext = '".$this->db->escape($refExt)."' OR label = '".$this->db->escape($label)."')";
        $resql = $this->db->query($sql);
        if ($resql && ($obj = $this->db->fetch_object($resql))) {
            if (empty($obj->ref_ext)) {
                $this->db->query('UPDATE '.MAIN_DB_PREFIX."categorie SET ref_ext = '".$this->db->escape($refExt)."' WHERE rowid = ".((int) $obj->rowid));
            }
            return (int) $obj->rowid;
        }
        $category = new Categorie($this->db);
        $category->type = Categorie::TYPE_PRODUCT;
        $category->ref_ext = $refExt;
        $category->label = $label;
        $category->description = 'Categoria padrão do módulo Frota Agrícola';
        $category->visible = 1;
        $result = $category->create($user);
        return $this->capture($result, $category);
    }

    private function ensureProduct($ref, $label, $categoryId, User $user)
    {
        global $conf;
        $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX."product WHERE entity = ".((int) $conf->entity)." AND ref = '".$this->db->escape($ref)."'";
        $resql = $this->db->query($sql);
        $product = new Product($this->db);
        if ($resql && ($obj = $this->db->fetch_object($resql))) {
            $product->fetch((int) $obj->rowid);
        } else {
            $product->entity = (int) $conf->entity;
            $product->ref = $ref;
            $product->label = $label;
            $product->description = 'Produto padrão do módulo Frota Agrícola';
            $product->type = Product::TYPE_PRODUCT;
            $product->status = 1;
            $product->status_buy = 1;
            $product->stockable_product = 1;
            $product->price_base_type = 'HT';
            $product->tva_tx = 0;
            $result = $product->create($user);
            if ($this->capture($result, $product) < 0) {
                return -1;
            }
        }
        $categoryIds = array((int) $categoryId);
        $sql = 'SELECT fk_categorie FROM '.MAIN_DB_PREFIX.'categorie_product WHERE fk_product = '.((int) $product->id);
        $resql = $this->db->query($sql);
        while ($resql && ($obj = $this->db->fetch_object($resql))) {
            $categoryIds[] = (int) $obj->fk_categorie;
        }
        if ($product->setCategories(array_values(array_unique($categoryIds))) < 0) {
            return $this->capture(-1, $product);
        }
        return (int) $product->id;
    }

    private function ensureDictionary($table, $code, $label, User $user, $entity)
    {
        $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$table." WHERE entity = ".((int) $entity)." AND code = '".$this->db->escape($code)."'";
        $resql = $this->db->query($sql);
        if ($resql && $this->db->fetch_object($resql)) {
            return 1;
        }
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.$table.' (entity, code, label, active, fk_user_creat, datec)';
        $sql .= " VALUES (".((int) $entity).", '".$this->db->escape($code)."', '".$this->db->escape($label)."', 1, ".((int) $user->id).", '".$this->db->idate(dol_now())."')";
        if (!$this->db->query($sql)) {
            $this->error = $this->db->lasterror();
            return -1;
        }
        return 1;
    }

    private function capture($result, $object)
    {
        if ($result > 0) {
            return (int) $result;
        }
        $this->error = !empty($object->error) ? $object->error : 'FrotaSeedError';
        $this->errors = !empty($object->errors) ? $object->errors : array($this->error);
        return -1;
    }
}
