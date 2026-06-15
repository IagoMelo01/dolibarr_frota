<?php

require __DIR__.'/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/lib/frota.lib.php';

$type = isset($frotaObjectType) ? $frotaObjectType : GETPOST('type', 'aZ09');
$config = frotaConfig($type);
if (!$config) {
    accessforbidden();
}

$langs->loadLangs(array('frota@frota', 'products', 'stocks', 'projects'));
if (!$user->hasRight('frota', $config['permission'], 'read')) {
    accessforbidden();
}

$prototype = frotaNewObject($db, $type);
$search = trim(GETPOST('search', 'alphanohtml'));
$searchStatus = GETPOST('search_status', 'alphanohtml');
$incomplete = GETPOSTINT('incomplete');
$hasStatus = property_exists($prototype, 'status');

$where = array('t.entity = '.((int) $conf->entity));
if ($search !== '') {
    $searchFields = array();
    foreach (array('ref', 'name', 'brand', 'model', 'type', 'plate', 'asset_number', 'description') as $field) {
        if (property_exists($prototype, $field)) {
            $searchFields[] = 't.'.$field." LIKE '%".$db->escape($search)."%'";
        }
    }
    if ($searchFields) {
        $where[] = '('.implode(' OR ', $searchFields).')';
    }
}
if ($hasStatus && in_array($searchStatus, array('0', '1', '2', '9'), true)) {
    $where[] = 't.status = '.((int) $searchStatus);
}
if ($incomplete && $type === 'veiculo') {
    $where[] = "(t.fk_veiculo_type IS NULL OR t.brand IS NULL OR t.brand = '' OR t.model IS NULL OR t.model = '')";
} elseif ($incomplete && $type === 'implemento') {
    $where[] = "(t.type IS NULL OR t.type = '' OR t.brand IS NULL OR t.brand = '' OR t.model IS NULL OR t.model = '')";
}

$sql = 'SELECT '.$prototype->getFieldList('t').' FROM '.MAIN_DB_PREFIX.$prototype->table_element.' t';
$sql .= ' WHERE '.implode(' AND ', $where).' ORDER BY t.rowid DESC LIMIT 200';
$resql = $db->query($sql);

llxHeader('', $langs->trans($config['title'].'s'), '', '', 0, 0, '', array('/frota/css/frota.css'));
print load_fiche_titre($langs->trans($config['title'].'s'), '', $prototype->picto);

print '<div class="frota-intro">';
print '<h2>'.$langs->trans($config['title'].'s').'</h2>';
print '<p>'.$langs->trans('FrotaListIntro'.$config['title']).'</p>';
if ($user->hasRight('frota', $config['permission'], 'write')) {
    print '<div class="frota-actions"><a class="butAction" href="'.frotaBuildUrl($type.'_card.php', array('action'=>'create')).'">'.img_picto('', 'fa-plus', 'class="pictofixedwidth"').' '.$langs->trans('New').'</a>';
    if ($type === 'manutencao') {
        print '<a class="button" href="'.frotaBuildUrl('manutencao_agenda.php').'">'.img_picto('', 'fa-calendar-check', 'class="pictofixedwidth"').' '.$langs->trans('FrotaMaintenanceAgenda').'</a>';
    }
    print '</div>';
} elseif ($type === 'manutencao') {
    print '<div class="frota-actions"><a class="button" href="'.frotaBuildUrl('manutencao_agenda.php').'">'.img_picto('', 'fa-calendar-check', 'class="pictofixedwidth"').' '.$langs->trans('FrotaMaintenanceAgenda').'</a></div>';
}
print '</div>';

if (in_array($type, array('veiculo', 'implemento'), true)) {
    $statsSql = 'SELECT COUNT(rowid) total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) active, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) inactive';
    $statsSql .= ' FROM '.MAIN_DB_PREFIX.$prototype->table_element.' WHERE entity = '.((int) $conf->entity);
    $statsRes = $db->query($statsSql);
    $stats = $statsRes ? $db->fetch_object($statsRes) : null;
    print '<div class="frota-kpi-grid">';
    frotaPrintListKpi($langs->trans('Total'), $stats ? $stats->total : 0, 'fa-list', frotaBuildUrl($type.'_list.php'));
    frotaPrintListKpi($langs->trans('Enabled'), $stats ? $stats->active : 0, 'fa-check', frotaBuildUrl($type.'_list.php', array('search_status'=>1)));
    frotaPrintListKpi($langs->trans('Disabled'), $stats ? $stats->inactive : 0, 'fa-pause', frotaBuildUrl($type.'_list.php', array('search_status'=>0)));
    if ($type === 'veiculo') {
        $missing = frotaScalar($db, 'SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_veiculo WHERE entity = '.((int) $conf->entity)." AND (fk_veiculo_type IS NULL OR brand IS NULL OR brand = '' OR model IS NULL OR model = '')");
        frotaPrintListKpi($langs->trans('FrotaIncompleteRecords'), $missing, 'fa-info-circle', frotaBuildUrl($type.'_list.php', array('incomplete'=>1)));
    } else {
        $missing = frotaScalar($db, 'SELECT COUNT(rowid) FROM '.MAIN_DB_PREFIX.'frota_implemento WHERE entity = '.((int) $conf->entity)." AND (type IS NULL OR type = '' OR brand IS NULL OR brand = '' OR model IS NULL OR model = '')");
        frotaPrintListKpi($langs->trans('FrotaIncompleteRecords'), $missing, 'fa-info-circle', frotaBuildUrl($type.'_list.php', array('incomplete'=>1)));
    }
    print '</div>';
} elseif ($type === 'manutencao') {
    $nowSql = $db->idate(dol_now());
    $horizonSql = $db->idate(dol_time_plus_duree(dol_now(), 30, 'd'));
    $statsSql = 'SELECT COUNT(m.rowid) total_open,';
    $statsSql .= ' SUM(CASE WHEN m.fk_veiculo IS NOT NULL THEN 1 ELSE 0 END) vehicle_open,';
    $statsSql .= ' SUM(CASE WHEN m.fk_implement IS NOT NULL THEN 1 ELSE 0 END) implement_open,';
    $statsSql .= " SUM(CASE WHEN (m.date_planned IS NOT NULL AND m.date_planned <= '".$db->escape($nowSql)."') OR (m.due_horimeter IS NOT NULL AND m.due_horimeter > 0 AND v.horimeter >= m.due_horimeter) OR (m.due_odometer IS NOT NULL AND m.due_odometer > 0 AND v.odometer >= m.due_odometer) THEN 1 ELSE 0 END) due,";
    $statsSql .= " SUM(CASE WHEN m.date_planned > '".$db->escape($nowSql)."' AND m.date_planned <= '".$db->escape($horizonSql)."'";
    $statsSql .= ' AND NOT (m.due_horimeter IS NOT NULL AND m.due_horimeter > 0 AND v.horimeter >= m.due_horimeter)';
    $statsSql .= ' AND NOT (m.due_odometer IS NOT NULL AND m.due_odometer > 0 AND v.odometer >= m.due_odometer) THEN 1 ELSE 0 END) upcoming,';
    $statsSql .= ' SUM(CASE WHEN m.is_periodic = 1 THEN 1 ELSE 0 END) periodic';
    $statsSql .= ' FROM '.MAIN_DB_PREFIX.'frota_manutencao m LEFT JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = m.fk_veiculo AND v.entity = m.entity';
    $statsSql .= ' WHERE m.entity = '.((int) $conf->entity).' AND m.status IN (0,1)';
    $statsRes = $db->query($statsSql);
    $stats = $statsRes ? $db->fetch_object($statsRes) : null;
    print '<div class="frota-kpi-grid">';
    frotaPrintListKpi($langs->trans('FrotaOpenMaintenances'), $stats ? $stats->total_open : 0, 'fa-tools', frotaBuildUrl('manutencao_list.php'));
    frotaPrintListKpi($langs->trans('FrotaVehicleMaintenances'), $stats ? $stats->vehicle_open : 0, 'fa-tractor', frotaBuildUrl('manutencao_agenda.php', array('asset_type'=>'vehicle')));
    frotaPrintListKpi($langs->trans('FrotaImplementMaintenances'), $stats ? $stats->implement_open : 0, 'fa-cogs', frotaBuildUrl('manutencao_agenda.php', array('asset_type'=>'implement')));
    frotaPrintListKpi($langs->trans('FrotaOverdueMaintenances'), $stats ? $stats->due : 0, 'fa-exclamation-triangle', frotaBuildUrl('manutencao_agenda.php'));
    frotaPrintListKpi($langs->trans('FrotaDueWithinDays', 30), $stats ? $stats->upcoming : 0, 'fa-calendar-alt', frotaBuildUrl('manutencao_agenda.php'));
    frotaPrintListKpi($langs->trans('FrotaPeriodicMaintenances'), $stats ? $stats->periodic : 0, 'fa-redo', frotaBuildUrl('manutencao_agenda.php'));
    print '</div>';
}

print '<div class="frota-panel">';
print '<form method="GET" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'"><div class="frota-filterbar">';
if ($incomplete) {
    print '<input type="hidden" name="incomplete" value="1">';
}
print '<div><label for="search">'.$langs->trans('Search').'</label><input class="flat minwidth300" id="search" name="search" value="'.dol_escape_htmltag($search).'" placeholder="'.$langs->trans('FrotaSearchPlaceholder').'"></div>';
if ($hasStatus) {
    print '<div><label for="search_status">'.$langs->trans('Status').'</label><select class="flat minwidth150" id="search_status" name="search_status"><option value="">'.$langs->trans('All').'</option>';
    foreach (frotaListStatusOptions($type) as $status => $label) {
        print '<option value="'.(int) $status.'"'.((string) $searchStatus === (string) $status ? ' selected' : '').'>'.dol_escape_htmltag($langs->trans($label)).'</option>';
    }
    print '</select></div>';
}
print '<div><button class="button" type="submit">'.img_picto('', 'search', 'class="pictofixedwidth"').' '.$langs->trans('Search').'</button></div>';
if ($search !== '' || $searchStatus !== '' || $incomplete) {
    print '<div><a class="button" href="'.frotaBuildUrl($type.'_list.php').'">'.$langs->trans('Reset').'</a></div>';
}
print '</div></form></div>';

print '<div class="div-table-responsive"><table class="tagtable liste centpercent">';
print '<tr class="liste_titre">';
foreach ($config['list'] as $field) {
    print '<th>'.$langs->trans(frotaFieldLabel($prototype, $field)).'</th>';
}
print '</tr>';
$rowCount = 0;
if (!$resql) {
    print '<tr><td colspan="'.count($config['list']).'">'.$db->lasterror().'</td></tr>';
} else {
    while ($row = $db->fetch_object($resql)) {
        $rowCount++;
        $object = frotaNewObject($db, $type);
        $object->setVarsFromFetchObj($row);
        print '<tr class="oddeven">';
        foreach ($config['list'] as $field) {
            $value = frotaDisplayValue($db, $object, $config, $field);
            $class = $field === 'ref' ? ' class="frota-primary-cell"' : '';
            if ($field === 'ref') {
                $value = '<a href="'.frotaBuildUrl($type.'_card.php', array('id'=>(int) $object->id)).'">'.dol_escape_htmltag($value).'</a>';
            } elseif ($field === 'status') {
                $value = frotaStatusBadge($type, $object->status);
            } elseif (in_array($field, array('horimeter', 'odometer', 'due_horimeter', 'due_odometer', 'worked_hours', 'area_ha', 'qty', 'average_consumption'), true)) {
                $value = price($value);
            } elseif (in_array($field, array('date_planned', 'date_start', 'date_end', 'date_fueling', 'date_uso'), true)) {
                $value = $value ? dol_print_date($db->jdate($value), 'dayhour') : '-';
            } else {
                $value = dol_escape_htmltag($value);
            }
            print '<td'.$class.'>'.$value.'</td>';
        }
        print '</tr>';
    }
}
if ($rowCount === 0 && $resql) {
    print '<tr><td class="frota-empty" colspan="'.count($config['list']).'"><strong>'.$langs->trans('FrotaNoRecordsFound').'</strong><span class="frota-muted">'.$langs->trans('FrotaNoRecordsHint').'</span></td></tr>';
}
print '</table></div>';
llxFooter();
$db->close();

function frotaPrintListKpi($label, $value, $icon, $url)
{
    print '<a class="frota-kpi-card" href="'.$url.'"><span class="frota-kpi-icon">'.img_picto('', $icon).'</span><div><div class="frota-kpi-value">'.(int) $value.'</div><div class="frota-kpi-label">'.dol_escape_htmltag($label).'</div></div></a>';
}

function frotaScalar($db, $sql)
{
    $resql = $db->query($sql);
    $row = $resql ? $db->fetch_row($resql) : null;
    return $row ? $row[0] : 0;
}

function frotaListStatusOptions($type)
{
    if (in_array($type, array('veiculo', 'implemento'), true)) {
        return array(1=>'Enabled', 0=>'Disabled');
    }
    if ($type === 'abastecimento') {
        return array(0=>'FrotaStatusDraft', 1=>'FrotaStatusConfirmed', 9=>'FrotaStatusCanceled');
    }
    if ($type === 'manutencao') {
        return array(0=>'FrotaStatusDraft', 1=>'FrotaStatusInProgress', 2=>'FrotaStatusCompleted', 9=>'FrotaStatusCanceled');
    }
    return array();
}
