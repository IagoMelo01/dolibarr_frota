<?php

require __DIR__.'/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/lib/frota.lib.php';

$langs->loadLangs(array('frota@frota', 'projects', 'products', 'stocks'));
if (!$user->hasRight('frota', 'veiculo', 'read')) {
    accessforbidden();
}

$entity = (int) $conf->entity;
$canReadImplement = $user->hasRight('frota', 'implemento', 'read');
$canReadFueling = $user->hasRight('frota', 'abastecimento', 'read');
$canReadMaintenance = $user->hasRight('frota', 'manutencao', 'read');
$canReadUsage = $user->hasRight('frota', 'uso', 'read');
$fuelSince = $db->idate(dol_now() - (30 * 86400));
$fleet = frotaDashboardFetch($db, 'SELECT'
    .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_veiculo WHERE entity = '.$entity.' AND status = 1) active_vehicles,'
    .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_implemento WHERE entity = '.$entity.' AND status = 1) active_implements,'
    .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_abastecimento WHERE entity = '.$entity.' AND status = 0) draft_fuelings,'
    .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_manutencao WHERE entity = '.$entity.' AND status IN (0,1)) open_maintenances,'
    .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_manutencao WHERE entity = '.$entity.' AND status IN (0,1) AND fk_veiculo IS NOT NULL) open_vehicle_maintenances,'
    .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_manutencao WHERE entity = '.$entity.' AND status IN (0,1) AND fk_implement IS NOT NULL) open_implement_maintenances');
$operations = $canReadUsage ? frotaDashboardFetch($db, 'SELECT'
    .' COALESCE(SUM(worked_hours),0) hours, COALESCE(SUM(area_ha),0) area, COALESCE(SUM(estimated_total_cost),0) cost, COUNT(rowid) entries'
    .' FROM '.MAIN_DB_PREFIX.'frota_uso WHERE entity = '.$entity) : null;
$fuel = $canReadFueling ? frotaDashboardFetch($db, 'SELECT COALESCE(SUM(qty),0) qty, COALESCE(SUM(total_amount),0) amount,'
    ." COALESCE(SUM(CASE WHEN date_fueling >= '".$db->escape($fuelSince)."' THEN qty ELSE 0 END),0) qty_30,"
    ." COALESCE(SUM(CASE WHEN date_fueling >= '".$db->escape($fuelSince)."' THEN total_amount ELSE 0 END),0) amount_30,"
    ." COALESCE(SUM(CASE WHEN date_fueling >= '".$db->escape($fuelSince)."' THEN 1 ELSE 0 END),0) entries_30,"
    ." COUNT(DISTINCT CASE WHEN date_fueling >= '".$db->escape($fuelSince)."' THEN fk_veiculo ELSE NULL END) vehicles_30"
    .' FROM '.MAIN_DB_PREFIX.'frota_abastecimento WHERE entity = '.$entity.' AND status = 1') : null;
$fuelAverageUnitCost = $fuel && (float) $fuel->qty_30 > 0 ? (float) $fuel->amount_30 / (float) $fuel->qty_30 : 0;
$maintenanceNow = $db->idate(dol_now());
$maintenanceHorizon = $db->idate(dol_time_plus_duree(dol_now(), 30, 'd'));
$maintenancePlan = $canReadMaintenance ? frotaDashboardFetch($db, 'SELECT'
    ." COALESCE(SUM(CASE WHEN (m.date_planned IS NOT NULL AND m.date_planned <= '".$db->escape($maintenanceNow)."') OR (m.due_horimeter IS NOT NULL AND m.due_horimeter > 0 AND v.horimeter >= m.due_horimeter) OR (m.due_odometer IS NOT NULL AND m.due_odometer > 0 AND v.odometer >= m.due_odometer) THEN 1 ELSE 0 END),0) due,"
    ." COALESCE(SUM(CASE WHEN m.date_planned > '".$db->escape($maintenanceNow)."' AND m.date_planned <= '".$db->escape($maintenanceHorizon)."'"
    .' AND NOT (m.due_horimeter IS NOT NULL AND m.due_horimeter > 0 AND v.horimeter >= m.due_horimeter)'
    .' AND NOT (m.due_odometer IS NOT NULL AND m.due_odometer > 0 AND v.odometer >= m.due_odometer) THEN 1 ELSE 0 END),0) upcoming,'
    .' COALESCE(SUM(CASE WHEN m.is_periodic = 1 THEN 1 ELSE 0 END),0) periodic'
    .' FROM '.MAIN_DB_PREFIX.'frota_manutencao m LEFT JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = m.fk_veiculo AND v.entity = m.entity'
    .' WHERE m.entity = '.$entity.' AND m.status IN (0,1)') : null;
$health = frotaDashboardFetch($db, 'SELECT'
    .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_veiculo WHERE entity = '.$entity." AND (fk_veiculo_type IS NULL OR brand IS NULL OR brand = '' OR model IS NULL OR model = '')) incomplete_vehicles,"
    .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_implemento WHERE entity = '.$entity." AND (type IS NULL OR type = '' OR brand IS NULL OR brand = '' OR model IS NULL OR model = '')) incomplete_implements,"
    .' (SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_veiculo WHERE entity = '.$entity.' AND status = 0) inactive_vehicles');

llxHeader('', $langs->trans('FrotaDashboard'), '', '', 0, 0, '', array('/frota/css/frota.css'));
print load_fiche_titre($langs->trans('FrotaDashboard'), '', 'fa-tractor');

print '<div class="frota-intro"><h2>'.$langs->trans('FrotaDashboardWelcome').'</h2><p>'.$langs->trans('FrotaDashboardIntro').'</p>';
print '<div class="frota-actions">';
if ($user->hasRight('frota', 'veiculo', 'write')) {
    print '<a class="butAction" href="'.frotaBuildUrl('veiculo_card.php', array('action'=>'create')).'">'.img_picto('', 'fa-plus', 'class="pictofixedwidth"').' '.$langs->trans('FrotaNewVehicle').'</a>';
}
if ($user->hasRight('frota', 'implemento', 'write')) {
    print '<a class="button" href="'.frotaBuildUrl('implemento_card.php', array('action'=>'create')).'">'.img_picto('', 'fa-cogs', 'class="pictofixedwidth"').' '.$langs->trans('FrotaNewImplement').'</a>';
}
if ($user->hasRight('frota', 'uso', 'write')) {
    print '<a class="button" href="'.frotaBuildUrl('uso_card.php', array('action'=>'create')).'">'.img_picto('', 'fa-clock', 'class="pictofixedwidth"').' '.$langs->trans('FrotaNewUsage').'</a>';
}
if ($user->hasRight('frota', 'abastecimento', 'write')) {
    print '<a class="button" href="'.frotaBuildUrl('abastecimento_card.php', array('action'=>'create')).'">'.img_picto('', 'fa-gas-pump', 'class="pictofixedwidth"').' '.$langs->trans('FrotaNewFueling').'</a>';
}
if ($user->hasRight('frota', 'manutencao', 'write')) {
    print '<a class="button" href="'.frotaBuildUrl('manutencao_card.php', array('action'=>'create')).'">'.img_picto('', 'fa-tools', 'class="pictofixedwidth"').' '.$langs->trans('FrotaNewMaintenance').'</a>';
}
if ($canReadMaintenance) {
    print '<a class="button" href="'.frotaBuildUrl('manutencao_agenda.php').'">'.img_picto('', 'fa-calendar-check', 'class="pictofixedwidth"').' '.$langs->trans('FrotaMaintenanceAgenda').'</a>';
}
print '</div></div>';

print '<div class="frota-kpi-grid">';
frotaDashboardCard($langs->trans('FrotaActiveVehicles'), (int) ($fleet ? $fleet->active_vehicles : 0), 'fa-tractor', frotaBuildUrl('veiculo_list.php', array('search_status'=>1)), $langs->trans('FrotaReadyForOperation'));
if ($canReadImplement) {
    frotaDashboardCard($langs->trans('FrotaActiveImplements'), (int) ($fleet ? $fleet->active_implements : 0), 'fa-cogs', frotaBuildUrl('implemento_list.php', array('search_status'=>1)), $langs->trans('FrotaReadyForOperation'));
}
if ($canReadMaintenance) {
    frotaDashboardCard($langs->trans('FrotaOpenMaintenances'), (int) ($fleet ? $fleet->open_maintenances : 0), 'fa-tools', frotaBuildUrl('manutencao_list.php'), $langs->trans('FrotaNeedsAttention'));
    frotaDashboardCard($langs->trans('FrotaVehicleMaintenances'), (int) ($fleet ? $fleet->open_vehicle_maintenances : 0), 'fa-tractor', frotaBuildUrl('manutencao_agenda.php', array('asset_type'=>'vehicle')), $langs->trans('FrotaOpenMaintenances'));
    frotaDashboardCard($langs->trans('FrotaImplementMaintenances'), (int) ($fleet ? $fleet->open_implement_maintenances : 0), 'fa-cogs', frotaBuildUrl('manutencao_agenda.php', array('asset_type'=>'implement')), $langs->trans('FrotaOpenMaintenances'));
    frotaDashboardCard($langs->trans('FrotaOverdueMaintenances'), (int) ($maintenancePlan ? $maintenancePlan->due : 0), 'fa-exclamation-triangle', frotaBuildUrl('manutencao_agenda.php'), $langs->trans('FrotaMaintenanceDueByDateOrMeter'));
    frotaDashboardCard($langs->trans('FrotaDueWithinDays', 30), (int) ($maintenancePlan ? $maintenancePlan->upcoming : 0), 'fa-calendar-alt', frotaBuildUrl('manutencao_agenda.php'), $langs->trans('FrotaMaintenancePlanning'));
    frotaDashboardCard($langs->trans('FrotaPeriodicMaintenances'), (int) ($maintenancePlan ? $maintenancePlan->periodic : 0), 'fa-redo', frotaBuildUrl('manutencao_agenda.php'), $langs->trans('FrotaAutomaticNextMaintenance'));
}
if ($canReadFueling) {
    frotaDashboardCard($langs->trans('FrotaDraftFuelings'), (int) ($fleet ? $fleet->draft_fuelings : 0), 'fa-gas-pump', frotaBuildUrl('abastecimento_list.php', array('search_status'=>0)), $langs->trans('FrotaAwaitingConfirmation'));
    frotaDashboardCard($langs->trans('FrotaConfirmedFuel'), price($fuel ? $fuel->qty : 0), 'fa-gas-pump', frotaBuildUrl('abastecimento_list.php', array('search_status'=>1)), price($fuel ? $fuel->amount : 0));
    frotaDashboardCard($langs->trans('FrotaFuelLast30Days'), price($fuel ? $fuel->qty_30 : 0), 'fa-calendar-alt', frotaBuildUrl('abastecimento_list.php', array('search_status'=>1)), $langs->trans('FrotaFuelLast30DaysNote', (int) ($fuel ? $fuel->entries_30 : 0), price($fuel ? $fuel->amount_30 : 0)));
    frotaDashboardCard($langs->trans('FrotaFuelAverageUnitCost'), price($fuelAverageUnitCost), 'fa-calculator', frotaBuildUrl('abastecimento_list.php', array('search_status'=>1)), $langs->trans('FrotaFuelAverageUnitCostNote', (int) ($fuel ? $fuel->vehicles_30 : 0)));
}
if ($canReadUsage) {
    frotaDashboardCard($langs->trans('FrotaUsageHours'), price($operations ? $operations->hours : 0), 'fa-clock', frotaBuildUrl('uso_list.php'), $langs->trans('FrotaAllRecordedUsage'));
    frotaDashboardCard($langs->trans('FrotaAreaWorked'), price($operations ? $operations->area : 0).' ha', 'fa-leaf', frotaBuildUrl('uso_list.php'), $langs->trans('FrotaAllRecordedUsage'));
    frotaDashboardCard($langs->trans('FrotaEstimatedOperationalCost'), price($operations ? $operations->cost : 0), 'fa-coins', frotaBuildUrl('uso_list.php'), $langs->trans('FrotaBasedOnUsageRecords'));
}
print '</div>';

if ($canReadFueling) {
    print '<div class="frota-dashboard-columns">';
    frotaDashboardDraftFuelings($db, $user->hasRight('stock', 'lire'));
    frotaDashboardRecentFuelings($db);
    print '</div>';
}

if ($canReadUsage || $canReadMaintenance) {
    print '<div class="frota-dashboard-columns">';
    if ($canReadUsage) {
        frotaDashboardRecentUsage($db);
    }
    if ($canReadMaintenance) {
        frotaDashboardOpenMaintenance($db);
    }
    print '</div>';
}

print '<div class="frota-dashboard-columns">';
print '<div class="frota-panel"><div class="frota-panel-title"><h3>'.img_picto('', 'fa-clipboard-check', 'class="pictofixedwidth"').' '.$langs->trans('FrotaRegistrationHealth').'</h3></div>';
print '<p class="frota-muted">'.$langs->trans('FrotaRegistrationHealthIntro').'</p><ul class="frota-health-list">';
frotaDashboardHealthItem($langs->trans('FrotaIncompleteVehicles'), (int) ($health ? $health->incomplete_vehicles : 0), frotaBuildUrl('veiculo_list.php', array('incomplete'=>1)));
if ($canReadImplement) {
    frotaDashboardHealthItem($langs->trans('FrotaIncompleteImplements'), (int) ($health ? $health->incomplete_implements : 0), frotaBuildUrl('implemento_list.php', array('incomplete'=>1)));
}
frotaDashboardHealthItem($langs->trans('FrotaInactiveVehicles'), (int) ($health ? $health->inactive_vehicles : 0), frotaBuildUrl('veiculo_list.php', array('search_status'=>0)));
print '</ul></div>';

print '<div class="frota-panel"><div class="frota-panel-title"><h3>'.img_picto('', 'fa-lightbulb', 'class="pictofixedwidth"').' '.$langs->trans('FrotaOperationalGuide').'</h3></div>';
print '<ul class="frota-tip-list"><li>'.$langs->trans('FrotaGuideRegister').'</li><li>'.$langs->trans('FrotaGuideUsage').'</li><li>'.$langs->trans('FrotaGuideStock').'</li><li>'.$langs->trans('FrotaGuideReadings').'</li></ul>';
print '</div></div>';

llxFooter();
$db->close();

function frotaDashboardFetch($db, $sql)
{
    $resql = $db->query($sql);
    return $resql ? $db->fetch_object($resql) : null;
}

function frotaDashboardCard($label, $value, $icon, $url, $note)
{
    print '<a class="frota-kpi-card" href="'.$url.'"><span class="frota-kpi-icon">'.img_picto('', $icon).'</span><div><div class="frota-kpi-value">'.$value.'</div><div class="frota-kpi-label">'.dol_escape_htmltag($label).'</div><div class="frota-kpi-note">'.dol_escape_htmltag($note).'</div></div></a>';
}

function frotaDashboardHealthItem($label, $value, $url)
{
    global $langs;
    print '<li><a href="'.$url.'">'.dol_escape_htmltag($label).'</a><span class="frota-status '.($value > 0 ? 'frota-status-progress' : 'frota-status-active').'">'.($value > 0 ? $value : $langs->trans('FrotaOk')).'</span></li>';
}

function frotaDashboardDraftFuelings($db, $showStock)
{
    global $conf, $langs;
    $sql = 'SELECT a.rowid, a.date_fueling, a.qty, v.name vehicle_name, p.ref product_ref, p.label product_label, e.ref warehouse_ref';
    if ($showStock) {
        $sql .= ', e.statut warehouse_status, COALESCE(ps.reel, 0) stock';
    }
    $sql .= ' FROM '.MAIN_DB_PREFIX.'frota_abastecimento a';
    $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = a.fk_veiculo AND v.entity = a.entity';
    $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'product p ON p.rowid = a.fk_product';
    $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'entrepot e ON e.rowid = a.fk_warehouse';
    if ($showStock) {
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock ps ON ps.fk_product = a.fk_product AND ps.fk_entrepot = a.fk_warehouse';
    }
    $sql .= ' WHERE a.entity = '.((int) $conf->entity).' AND a.status = 0 ORDER BY a.date_fueling DESC, a.rowid DESC LIMIT 5';
    $resql = $db->query($sql);

    $colspan = $showStock ? 7 : 5;
    print '<div class="frota-panel"><div class="frota-panel-title"><h3>'.img_picto('', 'fa-clipboard-list', 'class="pictofixedwidth"').' '.$langs->trans('FrotaFuelingReview').'</h3><a href="'.frotaBuildUrl('abastecimento_list.php', array('search_status'=>0)).'">'.$langs->trans('ViewAll').'</a></div>';
    print '<div class="div-table-responsive"><table class="tagtable liste centpercent"><tr class="liste_titre"><th>'.$langs->trans('FrotaVehicle').'</th><th>'.$langs->trans('Product').'</th><th>'.$langs->trans('Warehouse').'</th><th>'.$langs->trans('Date').'</th><th>'.$langs->trans('Qty').'</th>';
    if ($showStock) {
        print '<th>'.$langs->trans('RealStock').'</th><th>'.$langs->trans('Status').'</th>';
    }
    print '</tr>';
    $count = 0;
    while ($resql && ($row = $db->fetch_object($resql))) {
        $count++;
        print '<tr class="oddeven"><td><a href="'.frotaBuildUrl('abastecimento_card.php', array('id'=>(int) $row->rowid)).'">'.dol_escape_htmltag($row->vehicle_name).'</a></td>';
        print '<td><span class="frota-primary-cell">'.dol_escape_htmltag($row->product_ref).'</span><span class="frota-table-secondary">'.dol_escape_htmltag($row->product_label).'</span></td>';
        print '<td>'.dol_escape_htmltag($row->warehouse_ref).'</td><td>'.dol_print_date($db->jdate($row->date_fueling), 'dayhour').'</td><td>'.price($row->qty).'</td>';
        if ($showStock) {
            print '<td>'.price($row->stock).'</td><td>'.frotaDashboardStockStatus((float) $row->stock, (float) $row->qty, (int) $row->warehouse_status).'</td>';
        }
        print '</tr>';
    }
    if (!$count) {
        print '<tr><td class="frota-empty" colspan="'.$colspan.'">'.$langs->trans('FrotaNoDraftFuelings').'</td></tr>';
    }
    print '</table></div></div>';
}

function frotaDashboardRecentFuelings($db)
{
    global $conf, $langs;
    $sql = 'SELECT a.rowid, a.date_fueling, a.qty, a.total_amount, a.average_consumption, a.consumption_basis,';
    $sql .= ' v.name vehicle_name, p.ref product_ref, p.label product_label';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'frota_abastecimento a';
    $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = a.fk_veiculo AND v.entity = a.entity';
    $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'product p ON p.rowid = a.fk_product';
    $sql .= ' WHERE a.entity = '.((int) $conf->entity).' AND a.status = 1 ORDER BY a.date_confirmed DESC, a.rowid DESC LIMIT 5';
    $resql = $db->query($sql);

    print '<div class="frota-panel"><div class="frota-panel-title"><h3>'.img_picto('', 'fa-gas-pump', 'class="pictofixedwidth"').' '.$langs->trans('FrotaRecentConfirmedFuelings').'</h3><a href="'.frotaBuildUrl('abastecimento_list.php', array('search_status'=>1)).'">'.$langs->trans('ViewAll').'</a></div>';
    print '<div class="div-table-responsive"><table class="tagtable liste centpercent"><tr class="liste_titre"><th>'.$langs->trans('FrotaVehicle').'</th><th>'.$langs->trans('Product').'</th><th>'.$langs->trans('Date').'</th><th>'.$langs->trans('Qty').'</th><th>'.$langs->trans('Total').'</th><th>'.$langs->trans('FrotaAverageConsumption').'</th></tr>';
    $count = 0;
    while ($resql && ($row = $db->fetch_object($resql))) {
        $count++;
        $consumption = isset($row->average_consumption) ? price($row->average_consumption).' '.dol_escape_htmltag($row->consumption_basis) : '-';
        print '<tr class="oddeven"><td><a href="'.frotaBuildUrl('abastecimento_card.php', array('id'=>(int) $row->rowid)).'">'.dol_escape_htmltag($row->vehicle_name).'</a></td>';
        print '<td><span class="frota-primary-cell">'.dol_escape_htmltag($row->product_ref).'</span><span class="frota-table-secondary">'.dol_escape_htmltag($row->product_label).'</span></td>';
        print '<td>'.dol_print_date($db->jdate($row->date_fueling), 'dayhour').'</td><td>'.price($row->qty).'</td><td>'.price($row->total_amount).'</td><td>'.$consumption.'</td></tr>';
    }
    if (!$count) {
        print '<tr><td class="frota-empty" colspan="6">'.$langs->trans('FrotaNoConfirmedFuelings').'</td></tr>';
    }
    print '</table></div></div>';
}

function frotaDashboardStockStatus($stock, $qty, $warehouseStatus)
{
    global $langs;
    if ($warehouseStatus <= 0) {
        return '<span class="frota-status frota-status-inactive">'.$langs->trans('FrotaStockUnavailable').'</span>';
    }
    if ($stock >= $qty) {
        return '<span class="frota-status frota-status-active">'.$langs->trans('FrotaStockReady').'</span>';
    }
    return '<span class="frota-status frota-status-progress">'.$langs->trans('FrotaStockInsufficient').'</span>';
}

function frotaDashboardRecentUsage($db)
{
    global $conf, $langs;
    $sql = 'SELECT u.rowid, u.ref, u.date_uso, u.worked_hours, u.area_ha, v.name vehicle_name';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'frota_uso u INNER JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = u.fk_veiculo AND v.entity = u.entity';
    $sql .= ' WHERE u.entity = '.((int) $conf->entity).' ORDER BY u.date_uso DESC, u.rowid DESC LIMIT 5';
    $resql = $db->query($sql);

    print '<div class="frota-panel"><div class="frota-panel-title"><h3>'.img_picto('', 'fa-history', 'class="pictofixedwidth"').' '.$langs->trans('FrotaRecentUsage').'</h3><a href="'.frotaBuildUrl('uso_list.php').'">'.$langs->trans('ViewAll').'</a></div>';
    print '<div class="div-table-responsive"><table class="tagtable liste centpercent"><tr class="liste_titre"><th>'.$langs->trans('FrotaVehicle').'</th><th>'.$langs->trans('Date').'</th><th>'.$langs->trans('FrotaWorkedHours').'</th><th>'.$langs->trans('FrotaAreaHa').'</th></tr>';
    $count = 0;
    while ($resql && ($row = $db->fetch_object($resql))) {
        $count++;
        print '<tr class="oddeven"><td><a href="'.frotaBuildUrl('uso_card.php', array('id'=>(int) $row->rowid)).'">'.dol_escape_htmltag($row->vehicle_name).'</a></td><td>'.dol_print_date($db->jdate($row->date_uso), 'dayhour').'</td><td>'.price($row->worked_hours).'</td><td>'.price($row->area_ha).'</td></tr>';
    }
    if (!$count) {
        print '<tr><td class="frota-empty" colspan="4">'.$langs->trans('FrotaNoUsageYet').'</td></tr>';
    }
    print '</table></div></div>';
}

function frotaDashboardOpenMaintenance($db)
{
    global $conf, $langs;
    $nowSql = $db->idate(dol_now());
    $sql = 'SELECT m.rowid, m.ref, m.type, m.status, m.date_planned, m.due_horimeter, m.due_odometer, m.is_periodic, m.description,';
    $sql .= " COALESCE(v.name, i.name) asset_name, CASE WHEN m.fk_veiculo IS NOT NULL THEN 'vehicle' ELSE 'implement' END asset_type,";
    $sql .= ' v.horimeter vehicle_horimeter, v.odometer vehicle_odometer';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'frota_manutencao m LEFT JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = m.fk_veiculo AND v.entity = m.entity';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'frota_implemento i ON i.rowid = m.fk_implement AND i.entity = m.entity';
    $sql .= ' WHERE m.entity = '.((int) $conf->entity).' AND m.status IN (0,1)';
    $sql .= " ORDER BY CASE WHEN (m.date_planned IS NOT NULL AND m.date_planned <= '".$db->escape($nowSql)."') OR (m.due_horimeter IS NOT NULL AND m.due_horimeter > 0 AND v.horimeter >= m.due_horimeter) OR (m.due_odometer IS NOT NULL AND m.due_odometer > 0 AND v.odometer >= m.due_odometer) THEN 0 ELSE 1 END,";
    $sql .= ' CASE WHEN m.date_planned IS NULL THEN 1 ELSE 0 END, m.date_planned, m.rowid LIMIT 7';
    $resql = $db->query($sql);

    print '<div class="frota-panel"><div class="frota-panel-title"><h3>'.img_picto('', 'fa-calendar-check', 'class="pictofixedwidth"').' '.$langs->trans('FrotaMaintenanceAgenda').'</h3><a href="'.frotaBuildUrl('manutencao_agenda.php').'">'.$langs->trans('ViewAll').'</a></div>';
    print '<div class="div-table-responsive"><table class="tagtable liste centpercent"><tr class="liste_titre"><th>'.$langs->trans('FrotaMaintenanceAsset').'</th><th>'.$langs->trans('Description').'</th><th>'.$langs->trans('FrotaMaintenanceSchedule').'</th><th>'.$langs->trans('FrotaDueStatus').'</th></tr>';
    $count = 0;
    while ($resql && ($row = $db->fetch_object($resql))) {
        $count++;
        $assetType = $row->asset_type === 'vehicle' ? 'FrotaVehicle' : 'FrotaImplement';
        print '<tr class="oddeven"><td><a href="'.frotaBuildUrl('manutencao_card.php', array('id'=>(int) $row->rowid)).'">'.dol_escape_htmltag($row->asset_name).'</a><span class="frota-table-secondary">'.$langs->trans($assetType).'</span></td>';
        print '<td>'.dol_escape_htmltag($row->description).(!empty($row->is_periodic) ? '<span class="frota-table-secondary">'.$langs->trans('FrotaPeriodicMaintenance').'</span>' : '').'</td>';
        print '<td>'.dol_escape_htmltag(frotaMaintenanceScheduleSummary($row)).'</td><td>'.frotaMaintenanceDueBadge($row, true).'</td></tr>';
    }
    if (!$count) {
        print '<tr><td class="frota-empty" colspan="4">'.$langs->trans('FrotaNoOpenMaintenance').'</td></tr>';
    }
    print '</table></div></div>';
}
