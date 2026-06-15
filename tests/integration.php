<?php

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
require dirname(__DIR__, 3).'/master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/veiculo.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/implemento.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/abastecimento.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/manutencao.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/uso.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/frotamigration.class.php';

$user = new User($db);
if ($user->fetch(1) <= 0) {
    throw new RuntimeException('Admin user 1 not found.');
}

function scalarQuery($db, $sql)
{
    $resql = $db->query($sql);
    if (!$resql || !($row = $db->fetch_row($resql))) {
        throw new RuntimeException($db->lasterror());
    }
    return $row[0];
}

function assertIntegration($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$entity = (int) $conf->entity;
$migration = new FrotaMigration($db);
assertIntegration($migration->run() > 0 && $migration->run() > 0, 'Frota schema migration is not idempotent: '.$migration->error);
$dashboardSince = $db->idate(dol_now() - (30 * 86400));
$dashboardQueries = array(
    'fuel summary'=>'SELECT COALESCE(SUM(qty),0) qty, COALESCE(SUM(total_amount),0) amount,'
        ." COALESCE(SUM(CASE WHEN date_fueling >= '".$db->escape($dashboardSince)."' THEN qty ELSE 0 END),0) qty_30,"
        ." COALESCE(SUM(CASE WHEN date_fueling >= '".$db->escape($dashboardSince)."' THEN total_amount ELSE 0 END),0) amount_30,"
        ." COALESCE(SUM(CASE WHEN date_fueling >= '".$db->escape($dashboardSince)."' THEN 1 ELSE 0 END),0) entries_30,"
        ." COUNT(DISTINCT CASE WHEN date_fueling >= '".$db->escape($dashboardSince)."' THEN fk_veiculo ELSE NULL END) vehicles_30"
        .' FROM '.MAIN_DB_PREFIX.'frota_abastecimento WHERE entity = '.$entity.' AND status = 1',
    'draft fuelings with stock'=>'SELECT a.rowid, COALESCE(ps.reel, 0) stock'
        .' FROM '.MAIN_DB_PREFIX.'frota_abastecimento a'
        .' INNER JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = a.fk_veiculo AND v.entity = a.entity'
        .' INNER JOIN '.MAIN_DB_PREFIX.'product p ON p.rowid = a.fk_product'
        .' INNER JOIN '.MAIN_DB_PREFIX.'entrepot e ON e.rowid = a.fk_warehouse'
        .' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock ps ON ps.fk_product = a.fk_product AND ps.fk_entrepot = a.fk_warehouse'
        .' WHERE a.entity = '.$entity.' AND a.status = 0 LIMIT 5',
    'recent confirmed fuelings'=>'SELECT a.rowid, a.average_consumption, a.consumption_basis'
        .' FROM '.MAIN_DB_PREFIX.'frota_abastecimento a'
        .' INNER JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = a.fk_veiculo AND v.entity = a.entity'
        .' INNER JOIN '.MAIN_DB_PREFIX.'product p ON p.rowid = a.fk_product'
        .' WHERE a.entity = '.$entity.' AND a.status = 1 ORDER BY a.date_confirmed DESC, a.rowid DESC LIMIT 5',
    'maintenance planning summary'=>'SELECT COUNT(m.rowid),'
        .' SUM(CASE WHEN (m.date_planned IS NOT NULL AND m.date_planned <= \''.$db->escape($db->idate(dol_now())).'\')'
        .' OR (m.due_horimeter IS NOT NULL AND v.horimeter >= m.due_horimeter)'
        .' OR (m.due_odometer IS NOT NULL AND v.odometer >= m.due_odometer) THEN 1 ELSE 0 END)'
        .' FROM '.MAIN_DB_PREFIX.'frota_manutencao m'
        .' LEFT JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = m.fk_veiculo AND v.entity = m.entity'
        .' WHERE m.entity = '.$entity.' AND m.status IN (0,1)',
    'maintenance asset split'=>'SELECT'
        .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_manutencao WHERE entity = '.$entity.' AND status IN (0,1) AND fk_veiculo IS NOT NULL),'
        .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_manutencao WHERE entity = '.$entity.' AND status IN (0,1) AND fk_implement IS NOT NULL)',
    'maintenance agenda assets'=>'SELECT m.rowid, COALESCE(v.name, i.name) asset_name,'
        ." CASE WHEN m.fk_veiculo IS NOT NULL THEN 'vehicle' ELSE 'implement' END asset_type"
        .' FROM '.MAIN_DB_PREFIX.'frota_manutencao m'
        .' LEFT JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = m.fk_veiculo AND v.entity = m.entity'
        .' LEFT JOIN '.MAIN_DB_PREFIX.'frota_implemento i ON i.rowid = m.fk_implement AND i.entity = m.entity'
        .' WHERE m.entity = '.$entity.' AND m.status IN (0,1) LIMIT 5',
);
foreach ($dashboardQueries as $dashboardQueryName => $dashboardQuery) {
    assertIntegration((bool) $db->query($dashboardQuery), 'Dashboard '.$dashboardQueryName.' query failed: '.$db->lasterror());
}

$suffix = strtoupper(substr(md5(uniqid('', true)), 0, 8));
$db->begin('Frota integration test');

try {
    $vehicleType = (int) scalarQuery($db, 'SELECT rowid FROM '.MAIN_DB_PREFIX."frota_veiculo_type WHERE entity = ".$entity." AND code = 'TRATOR'");
    $operationType = (int) scalarQuery($db, 'SELECT rowid FROM '.MAIN_DB_PREFIX."frota_tipo_operacao WHERE entity = ".$entity." AND code = 'PLANTIO'");
    $fuelProduct = (int) scalarQuery($db, 'SELECT rowid FROM '.MAIN_DB_PREFIX."product WHERE entity = ".$entity." AND ref = 'FROTA-DIESEL-S10'");
    $partProduct = (int) scalarQuery($db, 'SELECT rowid FROM '.MAIN_DB_PREFIX."product WHERE entity = ".$entity." AND ref = 'FROTA-FILTRO-OLEO'");
    $warehouse = (int) scalarQuery($db, 'SELECT rowid FROM '.MAIN_DB_PREFIX."entrepot WHERE entity = ".$entity." AND ref = 'Combustíveis'");

    $vehicle = new Veiculo($db);
    $vehicle->ref = 'TEST-VEI-'.$suffix;
    $vehicle->name = 'Integration vehicle';
    $vehicle->fk_veiculo_type = $vehicleType;
    $vehicle->status = 1;
    assertIntegration($vehicle->create($user, true) > 0, 'Vehicle create failed: '.$vehicle->error);

    $implement = new Implemento($db);
    $implement->ref = 'TEST-IMP-'.$suffix;
    $implement->name = 'Integration implement';
    $implement->status = 1;
    assertIntegration($implement->create($user, true) > 0, 'Implement create failed: '.$implement->error);

    $usage = new Uso($db);
    $usage->entity = $entity;
    $usage->fk_veiculo = $vehicle->id;
    $usage->fk_implement = $implement->id;
    $usage->fk_tipo_operacao = $operationType;
    $usage->date_uso = $db->idate(dol_now());
    $usage->horimeter_start = 100;
    $usage->horimeter_end = 105;
    $usage->area_ha = 12.5;
    $usage->operator_id = $user->id;
    assertIntegration($usage->create($user, true) > 0, 'Usage create failed: '.$usage->error);
    assertIntegration(abs($usage->worked_hours - 5) < 0.0001, 'Worked hours were not calculated.');

    $invalidUsage = new Uso($db);
    $invalidUsage->entity = $entity;
    $invalidUsage->fk_veiculo = $vehicle->id;
    $invalidUsage->fk_tipo_operacao = $operationType;
    $invalidUsage->date_uso = $db->idate(dol_now());
    $invalidUsage->horimeter_start = 110;
    $invalidUsage->horimeter_end = 109;
    $invalidUsage->area_ha = 1;
    assertIntegration($invalidUsage->create($user, true) < 0 && $invalidUsage->error === 'FrotaErrorInvalidHorimeterRange', 'Invalid hour meter range was accepted.');

    $invalidPeriodicMaintenance = new Manutencao($db);
    $invalidPeriodicMaintenance->entity = $entity;
    $invalidPeriodicMaintenance->fk_veiculo = $vehicle->id;
    $invalidPeriodicMaintenance->type = 'corretiva';
    $invalidPeriodicMaintenance->is_periodic = 1;
    $invalidPeriodicMaintenance->period_days = 30;
    $invalidPeriodicMaintenance->description = 'Invalid periodic corrective maintenance';
    assertIntegration($invalidPeriodicMaintenance->create($user, true) < 0 && $invalidPeriodicMaintenance->error === 'FrotaErrorPeriodicMaintenanceMustBePreventive', 'Corrective maintenance accepted periodicity.');

    $maintenanceWithoutAsset = new Manutencao($db);
    $maintenanceWithoutAsset->entity = $entity;
    $maintenanceWithoutAsset->type = 'preventiva';
    $maintenanceWithoutAsset->description = 'Maintenance without asset';
    assertIntegration($maintenanceWithoutAsset->create($user, true) < 0 && $maintenanceWithoutAsset->error === 'FrotaErrorMaintenanceAssetRequired', 'Maintenance without an asset was accepted.');

    $maintenanceWithTwoAssets = new Manutencao($db);
    $maintenanceWithTwoAssets->entity = $entity;
    $maintenanceWithTwoAssets->fk_veiculo = $vehicle->id;
    $maintenanceWithTwoAssets->fk_implement = $implement->id;
    $maintenanceWithTwoAssets->type = 'preventiva';
    $maintenanceWithTwoAssets->description = 'Maintenance with two assets';
    assertIntegration($maintenanceWithTwoAssets->create($user, true) < 0 && $maintenanceWithTwoAssets->error === 'FrotaErrorMaintenanceSingleAsset', 'Maintenance with vehicle and implement was accepted.');

    $invalidImplementMeterMaintenance = new Manutencao($db);
    $invalidImplementMeterMaintenance->entity = $entity;
    $invalidImplementMeterMaintenance->fk_implement = $implement->id;
    $invalidImplementMeterMaintenance->type = 'preventiva';
    $invalidImplementMeterMaintenance->is_periodic = 1;
    $invalidImplementMeterMaintenance->period_hours = 50;
    $invalidImplementMeterMaintenance->description = 'Invalid implement meter maintenance';
    assertIntegration($invalidImplementMeterMaintenance->create($user, true) < 0 && $invalidImplementMeterMaintenance->error === 'FrotaErrorImplementMaintenanceMeterPeriod', 'Implement maintenance accepted an hour meter period.');

    $implementMaintenance = new Manutencao($db);
    $implementMaintenance->entity = $entity;
    $implementMaintenance->fk_implement = $implement->id;
    $implementMaintenance->type = 'preventiva';
    $implementMaintenance->date_planned = $db->idate(dol_now());
    $implementMaintenance->description = 'Periodic planter maintenance';
    $implementMaintenance->is_periodic = 1;
    $implementMaintenance->period_days = 90;
    assertIntegration($implementMaintenance->create($user, true) > 0, 'Implement maintenance create failed: '.$implementMaintenance->error);
    assertIntegration($implementMaintenance->complete($user) > 0, 'Implement maintenance completion failed: '.$implementMaintenance->error);
    $nextImplementMaintenance = new Manutencao($db);
    assertIntegration($nextImplementMaintenance->fetch((int) $implementMaintenance->fk_next_maintenance) > 0, 'Next implement maintenance was not generated.');
    assertIntegration((int) $nextImplementMaintenance->fk_implement === (int) $implement->id && empty($nextImplementMaintenance->fk_veiculo), 'Next implement maintenance changed its asset type.');
    assertIntegration((int) $nextImplementMaintenance->period_days === 90 && empty($nextImplementMaintenance->period_hours) && empty($nextImplementMaintenance->period_km), 'Next implement maintenance did not preserve date-only periodicity.');
    assertIntegration($implementMaintenance->complete($user) === 0, 'Implement maintenance completion is not idempotent.');
    assertIntegration((int) scalarQuery($db, 'SELECT COUNT(*) FROM '.MAIN_DB_PREFIX.'frota_manutencao WHERE entity = '.$entity.' AND fk_origin_maintenance = '.((int) $implementMaintenance->id)) === 1, 'Implement maintenance completion generated duplicate next occurrences.');

    foreach (array($fuelProduct=>50, $partProduct=>5) as $productId => $qty) {
        $receipt = new MouvementStock($db);
        $receipt->setOrigin('frota_test', $vehicle->id);
        assertIntegration($receipt->reception($user, $productId, $warehouse, $qty, 0, 'Frota integration test receipt') > 0, 'Test stock receipt failed.');
    }

    $fueling = new Abastecimento($db);
    $fueling->entity = $entity;
    $fueling->fk_veiculo = $vehicle->id;
    $fueling->fk_product = $fuelProduct;
    $fueling->fk_warehouse = $warehouse;
    $fueling->date_fueling = $db->idate(dol_now());
    $fueling->qty = 10;
    $fueling->unit_price = 6;
    $fueling->horimeter = 105;
    $fueling->ref = 'MANUAL-REF-MUST-BE-IGNORED';
    assertIntegration($fueling->create($user) > 0, 'Fueling create failed: '.$fueling->error);
    assertIntegration((bool) preg_match('/^ABS-\d{8}-\d{6,}$/', $fueling->ref), 'Fueling reference was not generated automatically.');
    assertIntegration($fueling->confirm($user) > 0, 'Fueling confirmation failed: '.$fueling->error);
    $movementId = $fueling->fk_stock_movement;
    assertIntegration($movementId > 0, 'Fueling movement was not tracked.');
    assertIntegration($fueling->confirm($user) === 0, 'Fueling confirmation is not idempotent.');
    assertIntegration((int) scalarQuery($db, 'SELECT COUNT(*) FROM '.MAIN_DB_PREFIX.'stock_mouvement WHERE rowid = '.((int) $movementId)) === 1, 'Fueling movement missing.');

    $maintenance = new Manutencao($db);
    $maintenance->entity = $entity;
    $maintenance->fk_veiculo = $vehicle->id;
    $maintenance->type = 'preventiva';
    $maintenance->date_start = $db->idate(dol_now());
    $maintenance->description = 'Integration maintenance';
    $maintenance->labor_cost = 100;
    $maintenance->horimeter = 106;
    $maintenance->odometer = 10;
    $maintenance->is_periodic = 1;
    $maintenance->period_days = 30;
    $maintenance->period_hours = 50;
    $maintenance->period_km = 1000;
    $maintenance->ref = 'MANUAL-MAINTENANCE-REF-MUST-BE-IGNORED';
    assertIntegration($maintenance->create($user, true) > 0, 'Maintenance create failed: '.$maintenance->error);
    assertIntegration((bool) preg_match('/^MAN-\d{8}-\d{6,}$/', $maintenance->ref), 'Maintenance reference was not generated automatically.');

    $line = new ManutencaoLine($db);
    $line->entity = $entity;
    $line->fk_manutencao = $maintenance->id;
    $line->fk_product = $partProduct;
    $line->fk_warehouse = $warehouse;
    $line->qty = 2;
    $line->unit_price = 25;
    assertIntegration($line->create($user, true) > 0, 'Maintenance line create failed: '.$line->error);
    assertIntegration($maintenance->complete($user) > 0, 'Maintenance completion failed: '.$maintenance->error);
    $nextMaintenanceId = (int) $maintenance->fk_next_maintenance;
    assertIntegration($nextMaintenanceId > 0, 'Periodic maintenance did not generate the next occurrence.');
    $nextMaintenance = new Manutencao($db);
    assertIntegration($nextMaintenance->fetch($nextMaintenanceId) > 0, 'Generated next maintenance was not found.');
    assertIntegration((bool) preg_match('/^MAN-\d{8}-\d{6,}$/', $nextMaintenance->ref), 'Generated next maintenance reference is invalid.');
    assertIntegration((int) $nextMaintenance->fk_origin_maintenance === (int) $maintenance->id, 'Generated maintenance does not track its origin.');
    assertIntegration((int) $nextMaintenance->is_periodic === 1, 'Generated maintenance did not preserve periodicity.');
    assertIntegration(abs((float) $nextMaintenance->due_horimeter - 156) < 0.0001, 'Next due hour meter is incorrect.');
    assertIntegration(abs((float) $nextMaintenance->due_odometer - 1010) < 0.0001, 'Next due odometer is incorrect.');
    assertIntegration($nextMaintenance->fetchLines() === 1, 'Generated maintenance did not copy planned products.');
    assertIntegration(empty($nextMaintenance->lines[0]->fk_stock_movement), 'Generated maintenance consumed stock before completion.');
    assertIntegration($nextMaintenance->delete($user, true) < 0 && $nextMaintenance->error === 'FrotaErrorGeneratedMaintenanceDeletion', 'Generated periodic maintenance deletion was accepted.');
    assertIntegration($line->fetch((int) $line->id) > 0, 'Completed maintenance line could not be reloaded.');
    $maintenanceMovementId = (int) $line->fk_stock_movement;
    assertIntegration($maintenanceMovementId > 0, 'Maintenance stock movement was not tracked.');
    assertIntegration($maintenance->complete($user) === 0, 'Maintenance completion is not idempotent.');
    assertIntegration((int) scalarQuery($db, 'SELECT COUNT(*) FROM '.MAIN_DB_PREFIX.'stock_mouvement WHERE rowid = '.$maintenanceMovementId) === 1, 'Maintenance completion duplicated the stock movement.');
    assertIntegration((int) scalarQuery($db, 'SELECT COUNT(*) FROM '.MAIN_DB_PREFIX.'frota_manutencao WHERE entity = '.$entity.' AND fk_origin_maintenance = '.((int) $maintenance->id)) === 1, 'Maintenance completion generated duplicate next occurrences.');
    assertIntegration(abs($maintenance->total_cost - 150) < 0.0001, 'Maintenance total cost is incorrect.');
    assertIntegration($line->delete($user, true) < 0, 'Completed maintenance line deletion was accepted.');

    $lateLine = new ManutencaoLine($db);
    $lateLine->entity = $entity;
    $lateLine->fk_manutencao = $maintenance->id;
    $lateLine->fk_product = $partProduct;
    $lateLine->fk_warehouse = $warehouse;
    $lateLine->qty = 1;
    $lateLine->unit_price = 25;
    assertIntegration($lateLine->create($user, true) < 0, 'New line was accepted after maintenance completion.');

    $db->rollback('Frota integration test complete');
    assertIntegration((int) scalarQuery($db, 'SELECT COUNT(*) FROM '.MAIN_DB_PREFIX."frota_veiculo WHERE ref = '".$db->escape($vehicle->ref)."'") === 0, 'Integration rollback failed.');
    echo "Frota integration test passed.\n";
} catch (Throwable $e) {
    while (!empty($db->transaction_opened)) {
        $db->rollback('Frota integration test failed');
    }
    fwrite(STDERR, $e->getMessage().PHP_EOL);
    exit(1);
}
