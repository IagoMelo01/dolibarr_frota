<?php

require __DIR__.'/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/lib/frota.lib.php';

$type = isset($frotaObjectType) ? $frotaObjectType : GETPOST('type', 'aZ09');
$config = frotaConfig($type);
if (!$config) {
    accessforbidden();
}
$langs->loadLangs(array('frota@frota', 'products', 'stocks', 'projects', 'users'));
if (!$user->hasRight('frota', $config['permission'], 'read')) {
    accessforbidden();
}

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$object = frotaNewObject($db, $type);
if ($id > 0 && $object->fetch($id) <= 0) {
    accessforbidden();
}
if ($id <= 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    frotaPrepareNewObject($object, $config);
}

$writeAllowed = $user->hasRight('frota', $config['permission'], 'write');
$deleteAllowed = $user->hasRight('frota', $config['permission'], 'delete');
$confirmAllowed = $user->hasRight('frota', $config['permission'], 'confirm');
$error = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' && $writeAllowed) {
        frotaAssignPost($object, $config);
        $result = $object->create($user);
        if ($result > 0) {
            header('Location: '.$type.'_card.php?id='.(int) $result);
            exit;
        }
        $error++;
    } elseif ($action === 'update' && $writeAllowed && $object->id > 0) {
        frotaAssignPost($object, $config);
        if ($object->update($user) > 0) {
            header('Location: '.$type.'_card.php?id='.(int) $object->id);
            exit;
        }
        $error++;
    } elseif ($action === 'delete' && $deleteAllowed && $object->id > 0) {
        if ($object->delete($user) > 0) {
            header('Location: '.$type.'_list.php');
            exit;
        }
        $error++;
    } elseif ($action === 'confirm' && $type === 'abastecimento' && $confirmAllowed) {
        if ($object->confirm($user) >= 0) {
            header('Location: abastecimento_card.php?id='.(int) $object->id);
            exit;
        }
        $error++;
    } elseif ($action === 'complete' && $type === 'manutencao' && $confirmAllowed) {
        if ($object->complete($user) >= 0) {
            header('Location: manutencao_card.php?id='.(int) $object->id);
            exit;
        }
        $error++;
    } elseif ($action === 'start' && $type === 'manutencao' && $writeAllowed && (int) $object->status === Manutencao::STATUS_DRAFT) {
        if ($object->start($user) > 0) {
            header('Location: manutencao_card.php?id='.(int) $object->id);
            exit;
        }
        $error++;
    } elseif ($action === 'cancel' && $writeAllowed && $object->id > 0) {
        $canCancel = ($type === 'abastecimento' && (int) $object->status === Abastecimento::STATUS_DRAFT)
            || ($type === 'manutencao' && in_array((int) $object->status, array(Manutencao::STATUS_DRAFT, Manutencao::STATUS_IN_PROGRESS), true));
        if ($canCancel) {
            if ($object->cancel($user) > 0) {
                header('Location: '.$type.'_card.php?id='.(int) $object->id);
                exit;
            }
        }
        $error++;
    } elseif ($action === 'addline' && $type === 'manutencao' && $writeAllowed && !in_array((int) $object->status, array(Manutencao::STATUS_COMPLETED, Manutencao::STATUS_CANCELED), true)) {
        $line = new ManutencaoLine($db);
        $line->entity = $object->entity;
        $line->fk_manutencao = $object->id;
        $line->fk_product = GETPOSTINT('line_fk_product');
        $line->fk_warehouse = GETPOSTINT('line_fk_warehouse');
        $line->qty = (float) price2num(GETPOST('line_qty', 'alphanohtml'), 'MS');
        $line->unit_price = (float) price2num(GETPOST('line_unit_price', 'alphanohtml'), 'MU');
        $line->note = GETPOST('line_note', 'restricthtml');
        if ($line->create($user) > 0) {
            $object->refreshTotals();
            $object->update($user, true);
            header('Location: manutencao_card.php?id='.(int) $object->id);
            exit;
        }
        $object->error = $line->error;
        $object->errors = $line->errors;
        $error++;
    } elseif ($action === 'deleteline' && $type === 'manutencao' && $writeAllowed && !in_array((int) $object->status, array(Manutencao::STATUS_COMPLETED, Manutencao::STATUS_CANCELED), true)) {
        $line = new ManutencaoLine($db);
        if ($line->fetch(GETPOSTINT('lineid')) > 0 && (int) $line->fk_manutencao === (int) $object->id && empty($line->fk_stock_movement) && $line->delete($user) > 0) {
            $object->refreshTotals();
            $object->update($user, true);
            header('Location: manutencao_card.php?id='.(int) $object->id);
            exit;
        }
        $object->error = 'FrotaErrorConsumedLineLocked';
        $error++;
    } else {
        accessforbidden();
    }
}

if ($error) {
    setEventMessages($object->error, $object->errors, 'errors');
}

$isCreate = empty($object->id);
$locked = (!$isCreate && (($type === 'abastecimento' && (int) $object->status !== Abastecimento::STATUS_DRAFT)
    || ($type === 'manutencao' && in_array((int) $object->status, array(Manutencao::STATUS_COMPLETED, Manutencao::STATUS_CANCELED), true))));

llxHeader('', $langs->trans($config['title']), '', '', 0, 0, '', array('/frota/css/frota.css'));
print load_fiche_titre($langs->trans($config['title']).($object->ref ? ' '.$object->ref : ''), '', $object->picto);

if (in_array($type, array('veiculo', 'implemento'), true)) {
    frotaRenderObjectHero($object, $type);
}

print '<div class="frota-page-grid"><div>';
print '<div class="frota-panel">';
print '<div class="frota-panel-title"><h3>'.$langs->trans($isCreate ? 'FrotaNewRecordDetails' : 'FrotaRecordDetails').'</h3>';
if (!$isCreate && property_exists($object, 'status')) {
    print frotaStatusBadge($type, $object->status);
}
print '</div>';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.(int) $object->id.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="'.($isCreate ? 'create' : 'update').'">';
print '<table class="border centpercent tableforfield">';
foreach ($config['form'] as $field => $inputType) {
    $required = !empty($object->fields[$field]['notnull']) ? ' fieldrequired' : '';
    print '<tr><td class="titlefield'.$required.'">'.$langs->trans(frotaFieldLabel($object, $field)).'</td><td>';
    print frotaRenderInput($db, $object, $field, $inputType, $locked);
    $helpKey = frotaFieldHelpKey($type, $field);
    if ($helpKey) {
        print '<span class="frota-field-help">'.$langs->trans($helpKey).'</span>';
    }
    print '</td></tr>';
}
if (!$isCreate && property_exists($object, 'status')) {
    print '<tr><td>'.$langs->trans('Status').'</td><td>'.frotaStatusBadge($type, $object->status).'</td></tr>';
}
foreach (array('total_amount', 'average_consumption', 'consumption_basis', 'parts_cost', 'total_cost', 'worked_hours', 'estimated_hour_cost', 'estimated_total_cost', 'fk_stock_movement') as $field) {
    if (!$isCreate && property_exists($object, $field) && $object->{$field} !== null) {
        print '<tr><td>'.$langs->trans(frotaFieldLabel($object, $field)).'</td><td>'.dol_escape_htmltag(frotaDisplayValue($db, $object, $config, $field)).'</td></tr>';
    }
}
print '</table>';
if ($writeAllowed && !$locked) {
    print '<div class="center"><button class="button button-save" type="submit">'.$langs->trans('Save').'</button></div>';
}
print '</form></div></div>';
frotaRenderCardTips($type, $isCreate);
print '</div>';

if (!$isCreate) {
    print '<div class="tabsAction">';
    if ($type === 'abastecimento' && (int) $object->status === Abastecimento::STATUS_DRAFT && $confirmAllowed) {
        frotaActionForm('confirm', $langs->trans('FrotaConfirm'), $object->id);
    }
    if ($type === 'manutencao' && (int) $object->status === Manutencao::STATUS_DRAFT && $writeAllowed) {
        frotaActionForm('start', $langs->trans('FrotaStart'), $object->id);
    }
    if ($type === 'manutencao' && in_array((int) $object->status, array(Manutencao::STATUS_DRAFT, Manutencao::STATUS_IN_PROGRESS), true) && $confirmAllowed) {
        frotaActionForm('complete', $langs->trans('FrotaComplete'), $object->id);
    }
    if (in_array($type, array('abastecimento', 'manutencao'), true) && !$locked && $writeAllowed) {
        frotaActionForm('cancel', $langs->trans('FrotaCancel'), $object->id);
    }
    if ($deleteAllowed && !$locked) {
        frotaActionForm('delete', $langs->trans('Delete'), $object->id);
    }
    print '</div>';
}

if (!$isCreate && in_array($type, array('veiculo', 'implemento'), true)) {
    frotaRenderObjectInsights($db, $object, $type);
}

if ($type === 'manutencao' && !$isCreate) {
    frotaRenderMaintenancePlan($object);
    frotaRenderMaintenanceLines($db, $object, $writeAllowed && !$locked);
}

llxFooter();
$db->close();

function frotaActionForm($actionName, $label, $id)
{
    print '<form class="inline-block" method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.(int) $id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="'.$actionName.'">';
    print '<button class="butAction" type="submit">'.$label.'</button></form> ';
}

function frotaRenderObjectHero($object, $type)
{
    global $langs, $user;

    $title = trim((string) $object->name) !== '' ? $object->name : $langs->trans($type === 'veiculo' ? 'FrotaVehicle' : 'FrotaImplement');
    print '<div class="frota-intro"><div class="frota-object-hero"><div>';
    print '<h2>'.dol_escape_htmltag($title).'</h2>';
    if (!empty($object->ref)) {
        print '<div class="frota-muted">'.dol_escape_htmltag($object->ref).'</div>';
    }
    if (!empty($object->id)) {
        print '<div class="frota-object-meta">';
        if (!empty($object->brand) || !empty($object->model)) {
            print '<span>'.img_picto('', 'fa-tag', 'class="pictofixedwidth"').' '.dol_escape_htmltag(trim($object->brand.' '.$object->model)).'</span>';
        }
        if (!empty($object->year)) {
            print '<span>'.img_picto('', 'fa-calendar', 'class="pictofixedwidth"').' '.(int) $object->year.'</span>';
        }
        if ($type === 'veiculo' && !empty($object->plate)) {
            print '<span>'.img_picto('', 'fa-id-card', 'class="pictofixedwidth"').' '.dol_escape_htmltag($object->plate).'</span>';
        }
        print '</div>';
    }
    print '</div>';
    if (!empty($object->id)) {
        print frotaStatusBadge($type, $object->status);
    }
    print '</div>';

    if (!empty($object->id) && (int) $object->status === 1) {
        print '<div class="frota-actions">';
        if ($type === 'veiculo') {
            if ($user->hasRight('frota', 'abastecimento', 'write')) {
                print '<a class="button" href="'.frotaBuildUrl('abastecimento_card.php', array('action'=>'create', 'fk_veiculo'=>(int) $object->id)).'">'.img_picto('', 'fa-gas-pump', 'class="pictofixedwidth"').' '.$langs->trans('FrotaNewFueling').'</a>';
            }
        }
        if ($user->hasRight('frota', 'manutencao', 'write')) {
            $maintenanceParams = array('action'=>'create');
            $maintenanceParams[$type === 'veiculo' ? 'fk_veiculo' : 'fk_implement'] = (int) $object->id;
            print '<a class="button" href="'.frotaBuildUrl('manutencao_card.php', $maintenanceParams).'">'.img_picto('', 'fa-tools', 'class="pictofixedwidth"').' '.$langs->trans('FrotaNewMaintenance').'</a>';
        }
        if ($user->hasRight('frota', 'uso', 'write')) {
            $params = array('action'=>'create');
            $params[$type === 'veiculo' ? 'fk_veiculo' : 'fk_implement'] = (int) $object->id;
            print '<a class="button" href="'.frotaBuildUrl('uso_card.php', $params).'">'.img_picto('', 'fa-clock', 'class="pictofixedwidth"').' '.$langs->trans('FrotaNewUsage').'</a>';
        }
        print '</div>';
    } elseif (!empty($object->id)) {
        print '<p class="frota-muted">'.$langs->trans('FrotaInactiveQuickActions').'</p>';
    }
    print '</div>';
}

function frotaRenderCardTips($type, $isCreate)
{
    global $langs;

    $tipKeys = array(
        'veiculo'=>array('FrotaTipVehicleReadings', 'FrotaTipVehicleCost', 'FrotaTipVehicleStatus'),
        'implemento'=>array('FrotaTipImplementType', 'FrotaTipImplementUsage', 'FrotaTipImplementStatus'),
        'abastecimento'=>array('FrotaTipFuelingStock', 'FrotaTipFuelingReadings'),
        'manutencao'=>array('FrotaTipMaintenancePlanning', 'FrotaTipMaintenancePeriodic', 'FrotaTipMaintenanceStock', 'FrotaTipMaintenanceCompletion'),
        'uso'=>array('FrotaTipUsageProject', 'FrotaTipUsageReadings'),
    );

    print '<aside><div class="frota-panel">';
    print '<div class="frota-panel-title"><h3>'.img_picto('', 'fa-lightbulb', 'class="pictofixedwidth"').' '.$langs->trans('FrotaGoodPractices').'</h3></div>';
    print '<p class="frota-muted">'.$langs->trans($isCreate ? 'FrotaCreateGuidance' : 'FrotaEditGuidance').'</p>';
    print '<ul class="frota-tip-list">';
    foreach ($tipKeys[$type] ?? array() as $key) {
        print '<li>'.$langs->trans($key).'</li>';
    }
    print '</ul></div></aside>';
}

function frotaRenderObjectInsights($db, $object, $type)
{
    global $conf, $langs;

    $entity = (int) $conf->entity;
    $objectId = (int) $object->id;
    if ($type === 'veiculo') {
        $usageSql = 'SELECT COUNT(rowid) entries, COALESCE(SUM(worked_hours),0) hours, COALESCE(SUM(area_ha),0) area, MAX(date_uso) last_date FROM '.MAIN_DB_PREFIX.'frota_uso WHERE entity = '.$entity.' AND fk_veiculo = '.$objectId;
        $fuelSql = 'SELECT COALESCE(SUM(qty),0) qty, COALESCE(SUM(total_amount),0) amount FROM '.MAIN_DB_PREFIX.'frota_abastecimento WHERE entity = '.$entity.' AND fk_veiculo = '.$objectId.' AND status = 1';
        $maintenanceSql = 'SELECT COUNT(rowid) open_count FROM '.MAIN_DB_PREFIX.'frota_manutencao WHERE entity = '.$entity.' AND fk_veiculo = '.$objectId.' AND status IN (0,1)';
        $usage = frotaFetchObject($db, $usageSql);
        $fuel = frotaFetchObject($db, $fuelSql);
        $maintenance = frotaFetchObject($db, $maintenanceSql);
        print '<div class="frota-kpi-grid">';
        frotaPrintCardMetric($langs->trans('FrotaUsageHours'), price($usage ? $usage->hours : 0), 'fa-clock', $langs->trans('FrotaAllRecordedUsage'));
        frotaPrintCardMetric($langs->trans('FrotaAreaWorked'), price($usage ? $usage->area : 0).' ha', 'fa-leaf', $langs->trans('FrotaAllRecordedUsage'));
        frotaPrintCardMetric($langs->trans('FrotaConfirmedFuel'), price($fuel ? $fuel->qty : 0), 'fa-gas-pump', price($fuel ? $fuel->amount : 0));
        frotaPrintCardMetric($langs->trans('FrotaOpenMaintenances'), (int) ($maintenance ? $maintenance->open_count : 0), 'fa-tools', $langs->trans('FrotaNeedsAttention'));
        print '</div>';
        frotaRenderRecentUsage($db, 'fk_veiculo', $objectId);
    } else {
        $usageSql = 'SELECT COUNT(rowid) entries, COUNT(DISTINCT fk_veiculo) vehicles, COALESCE(SUM(worked_hours),0) hours, COALESCE(SUM(area_ha),0) area, MAX(date_uso) last_date FROM '.MAIN_DB_PREFIX.'frota_uso WHERE entity = '.$entity.' AND fk_implement = '.$objectId;
        $maintenanceSql = 'SELECT COUNT(rowid) open_count FROM '.MAIN_DB_PREFIX.'frota_manutencao WHERE entity = '.$entity.' AND fk_implement = '.$objectId.' AND status IN (0,1)';
        $usage = frotaFetchObject($db, $usageSql);
        $maintenance = frotaFetchObject($db, $maintenanceSql);
        print '<div class="frota-kpi-grid">';
        frotaPrintCardMetric($langs->trans('FrotaUsageRecords'), (int) ($usage ? $usage->entries : 0), 'fa-list', $langs->trans('FrotaOperationalHistory'));
        frotaPrintCardMetric($langs->trans('FrotaUsageHours'), price($usage ? $usage->hours : 0), 'fa-clock', $langs->trans('FrotaAllRecordedUsage'));
        frotaPrintCardMetric($langs->trans('FrotaAreaWorked'), price($usage ? $usage->area : 0).' ha', 'fa-leaf', $langs->trans('FrotaAllRecordedUsage'));
        frotaPrintCardMetric($langs->trans('FrotaVehiclesUsed'), (int) ($usage ? $usage->vehicles : 0), 'fa-tractor', $langs->trans('FrotaOperationalHistory'));
        frotaPrintCardMetric($langs->trans('FrotaOpenMaintenances'), (int) ($maintenance ? $maintenance->open_count : 0), 'fa-tools', $langs->trans('FrotaNeedsAttention'));
        print '</div>';
        frotaRenderRecentUsage($db, 'fk_implement', $objectId);
    }
}

function frotaRenderRecentUsage($db, $foreignKey, $objectId)
{
    global $conf, $langs;

    $sql = 'SELECT u.rowid, u.ref, u.date_uso, u.worked_hours, u.area_ha, v.name vehicle_name';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'frota_uso u INNER JOIN '.MAIN_DB_PREFIX.'frota_veiculo v ON v.rowid = u.fk_veiculo AND v.entity = u.entity';
    $sql .= ' WHERE u.entity = '.((int) $conf->entity).' AND u.'.$foreignKey.' = '.((int) $objectId);
    $sql .= ' ORDER BY u.date_uso DESC, u.rowid DESC LIMIT 5';
    $resql = $db->query($sql);

    print '<div class="frota-panel"><div class="frota-panel-title"><h3>'.img_picto('', 'fa-history', 'class="pictofixedwidth"').' '.$langs->trans('FrotaRecentUsage').'</h3><a href="'.frotaBuildUrl('uso_list.php').'">'.$langs->trans('ViewAll').'</a></div>';
    print '<div class="div-table-responsive"><table class="tagtable liste centpercent"><tr class="liste_titre"><th>'.$langs->trans('Ref').'</th><th>'.$langs->trans('FrotaVehicle').'</th><th>'.$langs->trans('Date').'</th><th>'.$langs->trans('FrotaWorkedHours').'</th><th>'.$langs->trans('FrotaAreaHa').'</th></tr>';
    $count = 0;
    while ($resql && ($row = $db->fetch_object($resql))) {
        $count++;
        print '<tr class="oddeven"><td><a href="'.frotaBuildUrl('uso_card.php', array('id'=>(int) $row->rowid)).'">'.dol_escape_htmltag($row->ref).'</a></td>';
        print '<td>'.dol_escape_htmltag($row->vehicle_name).'</td><td>'.dol_print_date($db->jdate($row->date_uso), 'dayhour').'</td><td>'.price($row->worked_hours).'</td><td>'.price($row->area_ha).'</td></tr>';
    }
    if (!$count) {
        print '<tr><td class="frota-empty" colspan="5">'.$langs->trans('FrotaNoUsageYet').'</td></tr>';
    }
    print '</table></div></div>';
}

function frotaPrintCardMetric($label, $value, $icon, $note)
{
    print '<div class="frota-kpi-card"><span class="frota-kpi-icon">'.img_picto('', $icon).'</span><div><div class="frota-kpi-value">'.$value.'</div><div class="frota-kpi-label">'.dol_escape_htmltag($label).'</div><div class="frota-kpi-note">'.dol_escape_htmltag($note).'</div></div></div>';
}

function frotaFetchObject($db, $sql)
{
    $resql = $db->query($sql);
    return $resql ? $db->fetch_object($resql) : null;
}

function frotaRenderMaintenanceLines($db, Manutencao $object, $canEdit)
{
    global $langs;
    $object->fetchLines();
    $products = frotaOptions($db, 'product');
    $warehouses = frotaOptions($db, 'warehouse');
    print load_fiche_titre($langs->trans('FrotaMaintenanceItems'), '', 'product');
    print '<div class="div-table-responsive"><table class="tagtable liste centpercent">';
    print '<tr class="liste_titre"><th>'.$langs->trans('Product').'</th><th>'.$langs->trans('Warehouse').'</th><th>'.$langs->trans('Qty').'</th><th>'.$langs->trans('UnitPrice').'</th><th>'.$langs->trans('Total').'</th><th>'.$langs->trans('StockMovement').'</th><th></th></tr>';
    foreach ($object->lines as $line) {
        print '<tr class="oddeven"><td>'.dol_escape_htmltag($products[$line->fk_product] ?? $line->fk_product).'</td>';
        print '<td>'.dol_escape_htmltag($warehouses[$line->fk_warehouse] ?? $line->fk_warehouse).'</td>';
        print '<td>'.dol_escape_htmltag($line->qty).'</td><td>'.price($line->unit_price).'</td><td>'.price($line->total_amount).'</td><td>'.dol_escape_htmltag($line->fk_stock_movement).'</td><td>';
        if ($canEdit && empty($line->fk_stock_movement)) {
            print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="deleteline"><input type="hidden" name="lineid" value="'.(int) $line->id.'"><button class="button-delete" type="submit">'.$langs->trans('Delete').'</button></form>';
        }
        print '</td></tr>';
    }
    print '</table></div>';
    if ($canEdit) {
        $lineInput = new ManutencaoLine($db);
        print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="addline">';
        print '<div class="div-table-responsive"><table class="tagtable liste centpercent">';
        print '<tr class="liste_titre"><th>'.$langs->trans('Product').'</th><th>'.$langs->trans('Warehouse').'</th><th>'.$langs->trans('Qty').'</th><th>'.$langs->trans('UnitPrice').'</th><th></th></tr>';
        print '<tr>';
        print '<td>'.$lineInput->showInputField($lineInput->fields['fk_product'], 'fk_product', '', '', '', 'line_', 'minwidth300 maxwidth500', 1).'</td>';
        print '<td>'.$lineInput->showInputField($lineInput->fields['fk_warehouse'], 'fk_warehouse', '', '', '', 'line_', 'minwidth300 maxwidth500', 1).'</td>';
        print '<td><input class="flat width75" type="number" step="0.00000001" name="line_qty" required></td>';
        print '<td><input class="flat width75" type="number" step="0.00000001" name="line_unit_price" value="0"></td>';
        print '<td><button class="button" type="submit">'.$langs->trans('Add').'</button></td></tr>';
        print '</table></div></form>';
    }
}

function frotaRenderMaintenancePlan(Manutencao $object)
{
    global $db, $langs;

    if (!empty($object->fk_veiculo)) {
        $vehicle = new Veiculo($db);
        if ($vehicle->fetch((int) $object->fk_veiculo) > 0) {
            $object->vehicle_horimeter = $vehicle->horimeter;
            $object->vehicle_odometer = $vehicle->odometer;
        }
    }

    print '<div class="frota-panel"><div class="frota-panel-title"><h3>'.img_picto('', 'fa-calendar-check', 'class="pictofixedwidth"').' '.$langs->trans('FrotaMaintenancePlan').'</h3>';
    print '<a href="'.frotaBuildUrl('manutencao_agenda.php').'">'.$langs->trans('FrotaMaintenanceAgenda').'</a></div>';
    $planStatus = in_array((int) $object->status, array(Manutencao::STATUS_DRAFT, Manutencao::STATUS_IN_PROGRESS), true)
        ? frotaMaintenanceDueBadge($object, true)
        : frotaStatusBadge('manutencao', $object->status);
    print '<div class="frota-object-meta"><span>'.$planStatus.'</span>';
    print '<span>'.img_picto('', 'fa-calendar-alt', 'class="pictofixedwidth"').' '.dol_escape_htmltag(frotaMaintenanceScheduleSummary($object)).'</span>';
    if (!empty($object->is_periodic)) {
        $periods = array();
        if (!empty($object->period_days)) {
            $periods[] = $langs->trans('FrotaEveryDays', (int) $object->period_days);
        }
        if (!empty($object->period_hours)) {
            $periods[] = $langs->trans('FrotaEveryHours', price($object->period_hours));
        }
        if (!empty($object->period_km)) {
            $periods[] = $langs->trans('FrotaEveryKm', price($object->period_km));
        }
        print '<span>'.img_picto('', 'fa-redo', 'class="pictofixedwidth"').' '.dol_escape_htmltag(implode(' | ', $periods)).'</span>';
    }
    print '</div>';
    if (!empty($object->fk_origin_maintenance) || !empty($object->fk_next_maintenance)) {
        print '<div class="frota-actions">';
        if (!empty($object->fk_origin_maintenance)) {
            print '<a class="button" href="'.frotaBuildUrl('manutencao_card.php', array('id'=>(int) $object->fk_origin_maintenance)).'">'.$langs->trans('FrotaPreviousMaintenance').'</a>';
        }
        if (!empty($object->fk_next_maintenance)) {
            print '<a class="button" href="'.frotaBuildUrl('manutencao_card.php', array('id'=>(int) $object->fk_next_maintenance)).'">'.$langs->trans('FrotaNextMaintenance').'</a>';
        }
        print '</div>';
    }
    print '</div>';
}
