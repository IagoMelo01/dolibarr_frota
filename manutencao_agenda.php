<?php

require __DIR__.'/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/lib/frota.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/manutencao.class.php';

$langs->loadLangs(array('frota@frota', 'users'));
if (!$user->hasRight('frota', 'manutencao', 'read')) {
    accessforbidden();
}

$vehicleId = GETPOSTINT('fk_veiculo');
$implementId = GETPOSTINT('fk_implement');
$assetType = GETPOST('asset_type', 'aZ09');
if (!in_array($assetType, array('vehicle', 'implement'), true)) {
    $assetType = '';
}
$horizon = GETPOSTINT('horizon');
if (!in_array($horizon, array(30, 60, 90, 180), true)) {
    $horizon = 30;
}
$now = dol_now();
$horizonDate = dol_time_plus_duree($now, $horizon, 'd');
$where = array('m.entity = '.((int) $conf->entity), 'm.status IN (0,1)');
if ($vehicleId > 0) {
    $where[] = 'm.fk_veiculo = '.((int) $vehicleId);
}
if ($implementId > 0) {
    $where[] = 'm.fk_implement = '.((int) $implementId);
}
if ($assetType === 'vehicle') {
    $where[] = 'm.fk_veiculo IS NOT NULL';
} elseif ($assetType === 'implement') {
    $where[] = 'm.fk_implement IS NOT NULL';
}

$nowSql = $db->idate($now);
$horizonSql = $db->idate($horizonDate);
$statsSql = 'SELECT COUNT(m.rowid) total_open,';
$statsSql .= " COALESCE(SUM(CASE WHEN (m.date_planned IS NOT NULL AND m.date_planned <= '".$db->escape($nowSql)."')";
$statsSql .= ' OR (m.due_horimeter IS NOT NULL AND m.due_horimeter > 0 AND v.horimeter >= m.due_horimeter)';
$statsSql .= ' OR (m.due_odometer IS NOT NULL AND m.due_odometer > 0 AND v.odometer >= m.due_odometer) THEN 1 ELSE 0 END),0) due,';
$statsSql .= " COALESCE(SUM(CASE WHEN m.date_planned > '".$db->escape($nowSql)."' AND m.date_planned <= '".$db->escape($horizonSql)."'";
$statsSql .= ' AND NOT (m.due_horimeter IS NOT NULL AND m.due_horimeter > 0 AND v.horimeter >= m.due_horimeter)';
$statsSql .= ' AND NOT (m.due_odometer IS NOT NULL AND m.due_odometer > 0 AND v.odometer >= m.due_odometer) THEN 1 ELSE 0 END),0) upcoming,';
$statsSql .= ' COALESCE(SUM(CASE WHEN m.is_periodic = 1 THEN 1 ELSE 0 END),0) periodic,';
$statsSql .= ' COALESCE(SUM(CASE WHEN m.status = 1 THEN 1 ELSE 0 END),0) progress';
$statsSql .= ' FROM '.MAIN_DB_PREFIX.'frota_manutencao m';
$statsSql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = m.fk_veiculo AND v.entity = m.entity';
$statsSql .= ' WHERE '.implode(' AND ', $where);
$statsRes = $db->query($statsSql);
$statsRow = $statsRes ? $db->fetch_object($statsRes) : null;
$stats = array(
    'due'=>(int) ($statsRow ? $statsRow->due : 0),
    'upcoming'=>(int) ($statsRow ? $statsRow->upcoming : 0),
    'periodic'=>(int) ($statsRow ? $statsRow->periodic : 0),
    'progress'=>(int) ($statsRow ? $statsRow->progress : 0),
);

$prototype = new Manutencao($db);
$prototype->fk_veiculo = $vehicleId;
$prototype->fk_implement = $implementId;
$sql = 'SELECT '.$prototype->getFieldList('m').', COALESCE(v.name, i.name) asset_name, COALESCE(v.ref, i.ref) asset_ref,';
$sql .= " CASE WHEN m.fk_veiculo IS NOT NULL THEN 'vehicle' ELSE 'implement' END asset_type,";
$sql .= ' v.horimeter vehicle_horimeter, v.odometer vehicle_odometer, u.login assigned_user';
$sql .= ' FROM '.MAIN_DB_PREFIX.'frota_manutencao m';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = m.fk_veiculo AND v.entity = m.entity';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'frota_implemento i ON i.rowid = m.fk_implement AND i.entity = m.entity';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user u ON u.rowid = m.fk_user_assigned';
$sql .= ' WHERE '.implode(' AND ', $where);
$sql .= ' ORDER BY CASE WHEN m.date_planned IS NULL THEN 1 ELSE 0 END, m.date_planned, m.rowid LIMIT 500';
$resql = $db->query($sql);

$groups = array('due'=>array(), 'upcoming'=>array(), 'later'=>array());
while ($resql && ($row = $db->fetch_object($resql))) {
    $state = frotaMaintenanceDueState($row, $now);
    if ($state['key'] === 'FrotaMaintenanceDue') {
        $groups['due'][] = $row;
    } elseif (!empty($row->date_planned) && $db->jdate($row->date_planned) <= $horizonDate) {
        $groups['upcoming'][] = $row;
    } else {
        $groups['later'][] = $row;
    }
}

llxHeader('', $langs->trans('FrotaMaintenanceAgenda'), '', '', 0, 0, '', array('/frota/css/frota.css'));
print load_fiche_titre($langs->trans('FrotaMaintenanceAgenda'), '', 'fa-calendar-check');

print '<div class="frota-intro"><h2>'.$langs->trans('FrotaMaintenanceAgenda').'</h2><p>'.$langs->trans('FrotaMaintenanceAgendaIntro').'</p>';
print '<div class="frota-actions"><a class="button" href="'.frotaBuildUrl('manutencao_list.php').'">'.img_picto('', 'fa-list', 'class="pictofixedwidth"').' '.$langs->trans('FrotaMaintenances').'</a>';
if ($user->hasRight('frota', 'manutencao', 'write')) {
    print '<a class="butAction" href="'.frotaBuildUrl('manutencao_card.php', array('action'=>'create')).'">'.img_picto('', 'fa-plus', 'class="pictofixedwidth"').' '.$langs->trans('FrotaNewMaintenance').'</a>';
}
print '</div></div>';

print '<div class="frota-kpi-grid">';
frotaAgendaKpi($langs->trans('FrotaOverdueMaintenances'), $stats['due'], 'fa-exclamation-triangle');
frotaAgendaKpi($langs->trans('FrotaDueWithinDays', $horizon), $stats['upcoming'], 'fa-calendar-alt');
frotaAgendaKpi($langs->trans('FrotaPeriodicMaintenances'), $stats['periodic'], 'fa-redo');
frotaAgendaKpi($langs->trans('FrotaStatusInProgress'), $stats['progress'], 'fa-tools');
print '</div>';

print '<div class="frota-panel"><form method="GET" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'"><div class="frota-filterbar">';
print '<div class="frota-filter-field frota-filter-field-relation"><label for="fk_veiculo">'.$langs->trans('FrotaVehicle').'</label>'.frotaRenderInput($db, $prototype, 'fk_veiculo', 'vehicle').'</div>';
print '<div class="frota-filter-field frota-filter-field-relation"><label for="fk_implement">'.$langs->trans('FrotaImplement').'</label>'.frotaRenderInput($db, $prototype, 'fk_implement', 'implement').'</div>';
print '<div class="frota-filter-field frota-filter-field-compact"><label for="asset_type">'.$langs->trans('FrotaMaintenanceAssetType').'</label><select class="flat minwidth150" id="asset_type" name="asset_type"><option value="">'.$langs->trans('All').'</option>';
print '<option value="vehicle"'.($assetType === 'vehicle' ? ' selected' : '').'>'.$langs->trans('FrotaVehicle').'</option>';
print '<option value="implement"'.($assetType === 'implement' ? ' selected' : '').'>'.$langs->trans('FrotaImplement').'</option></select></div>';
print '<div class="frota-filter-field frota-filter-field-compact"><label for="horizon">'.$langs->trans('FrotaPlanningHorizon').'</label><select class="flat minwidth150" id="horizon" name="horizon">';
foreach (array(30, 60, 90, 180) as $days) {
    print '<option value="'.$days.'"'.($horizon === $days ? ' selected' : '').'>'.$langs->trans('FrotaDays', $days).'</option>';
}
print '</select></div><div class="frota-filter-action"><button class="button" type="submit">'.img_picto('', 'search', 'class="pictofixedwidth"').' '.$langs->trans('Apply').'</button></div>';
if ($vehicleId > 0 || $implementId > 0 || $assetType !== '' || $horizon !== 30) {
    print '<div class="frota-filter-action"><a class="button" href="'.frotaBuildUrl('manutencao_agenda.php').'">'.$langs->trans('Reset').'</a></div>';
}
print '</div></form></div>';

frotaAgendaGroup($groups['due'], 'FrotaOverdueMaintenances', 'fa-exclamation-triangle', 'FrotaNoOverdueMaintenances');
frotaAgendaGroup($groups['upcoming'], 'FrotaUpcomingMaintenances', 'fa-calendar-alt', 'FrotaNoUpcomingMaintenances');
frotaAgendaGroup($groups['later'], 'FrotaLaterMaintenances', 'fa-calendar', 'FrotaNoLaterMaintenances');

llxFooter();
$db->close();

function frotaAgendaKpi($label, $value, $icon)
{
    print '<div class="frota-kpi-card"><span class="frota-kpi-icon">'.img_picto('', $icon).'</span><div><div class="frota-kpi-value">'.(int) $value.'</div><div class="frota-kpi-label">'.dol_escape_htmltag($label).'</div></div></div>';
}

function frotaAgendaGroup($rows, $titleKey, $icon, $emptyKey)
{
    global $langs;
    print '<div class="frota-panel"><div class="frota-panel-title"><h3>'.img_picto('', $icon, 'class="pictofixedwidth"').' '.$langs->trans($titleKey).'</h3><span class="badge">'.count($rows).'</span></div>';
    print '<div class="div-table-responsive"><table class="tagtable liste centpercent"><tr class="liste_titre">';
    print '<th>'.$langs->trans('FrotaMaintenanceAsset').'</th><th>'.$langs->trans('Description').'</th><th>'.$langs->trans('FrotaMaintenanceSchedule').'</th><th>'.$langs->trans('FrotaDueStatus').'</th><th>'.$langs->trans('FrotaAssignedUser').'</th><th>'.$langs->trans('Status').'</th></tr>';
    foreach ($rows as $row) {
        $assetType = $row->asset_type === 'vehicle' ? 'FrotaVehicle' : 'FrotaImplement';
        print '<tr class="oddeven"><td><a href="'.frotaBuildUrl('manutencao_card.php', array('id'=>(int) $row->rowid)).'"><span class="frota-primary-cell">'.dol_escape_htmltag($row->asset_name).'</span></a><span class="frota-table-secondary">'.$langs->trans($assetType).' | '.dol_escape_htmltag($row->asset_ref).'</span></td>';
        print '<td>'.dol_escape_htmltag($row->description).(!empty($row->is_periodic) ? '<span class="frota-table-secondary">'.$langs->trans('FrotaPeriodicMaintenance').'</span>' : '').'</td>';
        print '<td>'.dol_escape_htmltag(frotaMaintenanceScheduleSummary($row)).'</td><td>'.frotaMaintenanceDueBadge($row, true).'</td>';
        print '<td>'.dol_escape_htmltag($row->assigned_user ?: '-').'</td><td>'.frotaStatusBadge('manutencao', $row->status).'</td></tr>';
    }
    if (!$rows) {
        print '<tr><td class="frota-empty" colspan="6">'.$langs->trans($emptyKey).'</td></tr>';
    }
    print '</table></div></div>';
}
