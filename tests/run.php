<?php

$root = dirname(__DIR__);
$failures = array();

function check($condition, $message)
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

$requiredTables = array(
    'llx_frota_veiculo.sql',
    'llx_frota_implemento.sql',
    'llx_frota_veiculo_type.sql',
    'llx_frota_tipo_operacao.sql',
    'llx_frota_abastecimento.sql',
    'llx_frota_manutencao.sql',
    'llx_frota_manutencao_line.sql',
    'llx_frota_uso.sql',
);
$auditFields = array('entity', 'fk_user_creat', 'fk_user_modif', 'datec', 'tms');
foreach ($requiredTables as $file) {
    $path = $root.'/sql/'.$file;
    check(is_file($path), 'Missing schema '.$file);
    $sql = is_file($path) ? file_get_contents($path) : '';
    foreach ($auditFields as $field) {
        check((bool) preg_match('/\b'.preg_quote($field, '/').'\b/i', $sql), $file.' missing '.$field);
    }
}

$stock = file_get_contents($root.'/class/frotastockservice.class.php');
check(strpos($stock, 'setOrigin(') !== false, 'Stock service must track origin');
check(strpos($stock, '->livraison(') !== false, 'Stock service must use MouvementStock::livraison');
check(strpos($stock, 'FrotaErrorInsufficientStock') !== false, 'Stock service must reject insufficient stock');

$fueling = file_get_contents($root.'/class/abastecimento.class.php');
check(strpos($fueling, 'fk_stock_movement') !== false, 'Fueling must track stock movement');
check(strpos($fueling, 'STATUS_CONFIRMED') !== false, 'Fueling must have confirmation state');
check(strpos($fueling, 'integer:Veiculo:custom/frota/class/veiculo.class.php') !== false, 'Fueling vehicle must use a Dolibarr link field');
check(strpos($fueling, 'integer:Product:product/class/product.class.php') !== false, 'Fueling product must use a Dolibarr link field');
check(strpos($fueling, 'integer:Entrepot:product/stock/class/entrepot.class.php') !== false, 'Fueling warehouse must use a Dolibarr link field');
check(strpos($fueling, "return 'ABS-'.\$datePart.'-'.sprintf('%06d', (int) \$this->id)") !== false, 'Fueling must generate a meaningful automatic reference');
check(strpos($fueling, '$this->call_trigger(') > strpos($fueling, '$this->ref = $this->buildReference()'), 'Fueling create trigger must receive the definitive automatic reference');

$library = file_get_contents($root.'/lib/frota.lib.php');
check(strpos($library, "\$object->showInputField(") !== false, 'Frota forms must render Dolibarr link fields with CommonObject::showInputField');
check(strpos($library, "'form'=>array('fk_veiculo'=>'vehicle', 'fk_product'=>'product', 'fk_warehouse'=>'warehouse'") !== false, 'Fueling reference must not be editable in the form');
check(strpos($library, "'form'=>array('fk_veiculo'=>'vehicle', 'fk_implement'=>'implement', 'type'=>'maintenance_type'") !== false, 'Maintenance reference must not be editable and both asset selectors must be available');

$maintenance = file_get_contents($root.'/class/manutencao.class.php');
check(strpos($maintenance, 'setStockMovement') !== false, 'Maintenance lines must track stock movement');
check(strpos($maintenance, 'STATUS_COMPLETED') !== false, 'Maintenance must have completed state');
check(strpos($maintenance, "return 'MAN-'.\$datePart.'-'.sprintf('%06d', (int) \$this->id)") !== false, 'Maintenance must generate a meaningful automatic reference');
check(strpos($maintenance, '$this->call_trigger(') > strpos($maintenance, '$this->ref = $this->buildReference()'), 'Maintenance create trigger must receive the definitive automatic reference');
check(strpos($maintenance, 'createNextMaintenance') !== false, 'Periodic maintenance must generate the next occurrence');
check(strpos($maintenance, 'fk_next_maintenance') !== false, 'Periodic maintenance must track the generated next occurrence');
check(strpos($maintenance, 'due_horimeter') !== false && strpos($maintenance, 'due_odometer') !== false, 'Maintenance planning must keep due readings separate from actual readings');
check(strpos($maintenance, 'integer:Product:product/class/product.class.php') !== false, 'Maintenance products must use Dolibarr link fields');
check(strpos($maintenance, 'integer:Entrepot:product/stock/class/entrepot.class.php') !== false, 'Maintenance warehouses must use Dolibarr link fields');
check(strpos($maintenance, 'integer:Implemento:custom/frota/class/implemento.class.php') !== false, 'Maintenance implement must use a Dolibarr link field');
check(strpos($maintenance, 'FrotaErrorMaintenanceSingleAsset') !== false, 'Maintenance must require exactly one vehicle or implement');
check(strpos($maintenance, 'FrotaErrorImplementMaintenanceMeterPeriod') !== false, 'Implement maintenance must reject vehicle meter criteria');
check(is_file($root.'/class/frotamigration.class.php'), 'Missing idempotent Frota schema migration');
check(is_file($root.'/manutencao_agenda.php'), 'Missing maintenance schedule page');
$maintenanceAgenda = file_get_contents($root.'/manutencao_agenda.php');
check(strpos($maintenanceAgenda, "\$assetType === 'vehicle'") !== false && strpos($maintenanceAgenda, "\$assetType === 'implement'") !== false, 'Maintenance schedule must distinguish vehicles from implements');
$maintenanceSchema = file_get_contents($root.'/sql/llx_frota_manutencao.sql');
foreach (array('fk_implement', 'date_planned', 'due_horimeter', 'due_odometer', 'is_periodic', 'period_days', 'period_hours', 'period_km', 'fk_origin_maintenance', 'fk_next_maintenance') as $field) {
    check(strpos($maintenanceSchema, $field) !== false, 'Maintenance schema missing '.$field);
}
$migration = file_get_contents($root.'/class/frotamigration.class.php');
check(strpos($migration, 'DDLDescTable') !== false && strpos($migration, 'DDLAddField') !== false, 'Frota migration must check fields before adding them');

$seed = file_get_contents($root.'/class/frotaseeder.class.php');
foreach (array('FROTA-DIESEL-S10', 'FROTA-FILTRO-HIDRAULICO', 'TRATOR', 'BOMBA-IRRIGACAO', 'OUTRO') as $ref) {
    check(strpos($seed, $ref) !== false, 'Seed missing '.$ref);
}

foreach (array('diagram_new_module.mmd', 'diagrams/stock_flows.mmd', 'diagrams/state_flows.mmd', 'AGENTS.md', 'SKILLS.md') as $file) {
    check(is_file($root.'/'.$file), 'Missing project artifact '.$file);
}

$module = file_get_contents($root.'/core/modules/modFrota.class.php');
check(is_file($root.'/css/frota.css'), 'Missing Frota interface stylesheet');
check(strpos($module, '/frota/css/frota.css') !== false, 'Frota interface stylesheet must be loaded by the module');

$dashboard = file_get_contents($root.'/frotaindex.php');
check(strpos($dashboard, 'frotaDashboardDraftFuelings') !== false, 'Dashboard must show draft fuelings awaiting stock confirmation');
check(strpos($dashboard, 'frotaDashboardRecentFuelings') !== false, 'Dashboard must show recent confirmed fuelings');
check(strpos($dashboard, 'FrotaFuelLast30Days') !== false, 'Dashboard must show the recent fueling summary');
check(strpos($dashboard, "MAIN_DB_PREFIX.'product_stock") !== false, 'Dashboard must read native warehouse stock');
check(strpos($dashboard, "\$user->hasRight('stock', 'lire')") !== false, 'Dashboard must protect native stock balances with the stock read permission');
check(strpos($dashboard, "if (\$canReadFueling)") !== false, 'Dashboard must protect fueling information with the module read permission');
check(strpos($dashboard, 'FrotaOverdueMaintenances') !== false, 'Dashboard must show overdue maintenance');
check(strpos($dashboard, 'manutencao_agenda.php') !== false, 'Dashboard must link to the maintenance schedule');

$module = file_get_contents($root.'/core/modules/modFrota.class.php');
check(strpos($module, 'FrotaMigration') !== false, 'Module activation must run the idempotent schema migration');
check(strpos($module, 'manutencao_agenda.php') !== false, 'Module menu must expose the maintenance schedule');

$legacy = array_merge(glob($root.'/*.back') ?: array(), glob($root.'/class/*.back') ?: array(), glob($root.'/sql/*.back') ?: array());
check(count($legacy) === 0, 'Legacy .back files must not exist');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures).PHP_EOL);
    exit(1);
}

echo "Frota architecture checks passed.\n";
