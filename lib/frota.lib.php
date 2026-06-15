<?php

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

function frotaConfigs()
{
    return array(
        'veiculo'=>array(
            'class'=>'Veiculo', 'file'=>'veiculo.class.php', 'title'=>'FrotaVehicle', 'permission'=>'veiculo',
            'form'=>array('ref'=>'text', 'name'=>'text', 'fk_veiculo_type'=>'vehicle_type', 'brand'=>'text', 'model'=>'text', 'year'=>'integer', 'plate'=>'text', 'chassis'=>'text', 'asset_number'=>'text', 'horimeter'=>'number', 'odometer'=>'number', 'estimated_hour_cost'=>'number', 'status'=>'active_status', 'note'=>'textarea'),
            'list'=>array('ref', 'name', 'fk_veiculo_type', 'brand', 'model', 'horimeter', 'odometer', 'status'),
        ),
        'implemento'=>array(
            'class'=>'Implemento', 'file'=>'implemento.class.php', 'title'=>'FrotaImplement', 'permission'=>'implemento',
            'form'=>array('ref'=>'text', 'name'=>'text', 'type'=>'text', 'brand'=>'text', 'model'=>'text', 'year'=>'integer', 'status'=>'active_status', 'note'=>'textarea'),
            'list'=>array('ref', 'name', 'type', 'brand', 'model', 'status'),
        ),
        'abastecimento'=>array(
            'class'=>'Abastecimento', 'file'=>'abastecimento.class.php', 'title'=>'FrotaFueling', 'permission'=>'abastecimento',
            'form'=>array('fk_veiculo'=>'vehicle', 'fk_product'=>'product', 'fk_warehouse'=>'warehouse', 'date_fueling'=>'datetime', 'qty'=>'number', 'unit_price'=>'number', 'horimeter'=>'number', 'odometer'=>'number', 'fk_user'=>'user', 'note'=>'textarea'),
            'list'=>array('ref', 'fk_veiculo', 'date_fueling', 'qty', 'unit_price', 'total_amount', 'average_consumption', 'consumption_basis', 'status'),
        ),
        'manutencao'=>array(
            'class'=>'Manutencao', 'file'=>'manutencao.class.php', 'title'=>'FrotaMaintenance', 'permission'=>'manutencao',
            'form'=>array('fk_veiculo'=>'vehicle', 'fk_implement'=>'implement', 'type'=>'maintenance_type', 'date_planned'=>'datetime', 'due_horimeter'=>'number', 'due_odometer'=>'number', 'date_start'=>'datetime', 'date_end'=>'datetime', 'horimeter'=>'number', 'odometer'=>'number', 'is_periodic'=>'boolean', 'period_days'=>'integer', 'period_hours'=>'number', 'period_km'=>'number', 'description'=>'textarea', 'labor_cost'=>'number', 'fk_user_assigned'=>'user'),
            'list'=>array('ref', 'fk_veiculo', 'fk_implement', 'type', 'date_planned', 'due_horimeter', 'due_odometer', 'is_periodic', 'total_cost', 'status'),
        ),
        'uso'=>array(
            'class'=>'Uso', 'file'=>'uso.class.php', 'title'=>'FrotaUsage', 'permission'=>'uso',
            'form'=>array('ref'=>'text', 'fk_veiculo'=>'vehicle', 'fk_implement'=>'implement', 'fk_project'=>'project', 'fk_task'=>'task', 'fk_tipo_operacao'=>'operation_type', 'date_uso'=>'datetime', 'horimeter_start'=>'number', 'horimeter_end'=>'number', 'area_ha'=>'number', 'operator_id'=>'user', 'note'=>'textarea'),
            'list'=>array('ref', 'fk_veiculo', 'fk_implement', 'fk_project', 'date_uso', 'worked_hours', 'area_ha', 'estimated_hour_cost', 'estimated_total_cost'),
        ),
    );
}

function frotaConfig($type)
{
    $configs = frotaConfigs();
    return isset($configs[$type]) ? $configs[$type] : null;
}

function frotaNewObject($db, $type)
{
    $config = frotaConfig($type);
    if (!$config) {
        return null;
    }
    require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/'.$config['file'];
    return new $config['class']($db);
}

function frotaAssignPost($object, $config)
{
    foreach ($config['form'] as $field => $inputType) {
        if ($inputType === 'boolean') {
            $object->{$field} = GETPOSTISSET($field) ? 1 : 0;
            continue;
        }
        if (!GETPOSTISSET($field)) {
            continue;
        }
        if (in_array($inputType, array('number', 'integer'), true)) {
            $raw = GETPOST($field, 'alphanohtml');
            $object->{$field} = $raw === '' ? null : ($inputType === 'integer' ? (int) $raw : (float) price2num($raw, 'MS'));
        } elseif (in_array($inputType, array('vehicle', 'implement', 'vehicle_type', 'operation_type', 'product', 'warehouse', 'project', 'task', 'user', 'active_status', 'maintenance_type'), true)) {
            $raw = GETPOST($field, 'alphanohtml');
            $object->{$field} = $raw === '' ? null : (is_numeric($raw) ? (int) $raw : $raw);
        } elseif ($inputType === 'datetime') {
            $raw = GETPOST($field, 'alphanohtml');
            $object->{$field} = $raw === '' ? null : str_replace('T', ' ', $raw).(strlen($raw) === 16 ? ':00' : '');
        } else {
            $object->{$field} = GETPOST($field, 'restricthtml');
        }
    }
}

function frotaPrepareNewObject($object, $config)
{
    foreach ($config['form'] as $field => $inputType) {
        if (isset($object->fields[$field]['default']) && ($object->{$field} === null || $object->{$field} === '')) {
            $object->{$field} = $object->fields[$field]['default'];
        }
    }
    foreach (array('date_fueling', 'date_uso') as $field) {
        if (array_key_exists($field, $config['form']) && empty($object->{$field})) {
            $object->{$field} = $object->db->idate(dol_now());
        }
    }
    frotaAssignPost($object, $config);
}

function frotaOptions(DoliDB $db, $source)
{
    global $conf;
    static $cache = array();

    $cacheKey = $source.'-'.$conf->entity;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $options = array();
    $sql = '';
    if ($source === 'vehicle') {
        $sql = 'SELECT rowid, CONCAT(ref, " - ", name) label FROM '.MAIN_DB_PREFIX.'frota_veiculo WHERE entity = '.((int) $conf->entity).' AND status = 1 ORDER BY ref';
    } elseif ($source === 'implement') {
        $sql = 'SELECT rowid, CONCAT(ref, " - ", name) label FROM '.MAIN_DB_PREFIX.'frota_implemento WHERE entity = '.((int) $conf->entity).' AND status = 1 ORDER BY ref';
    } elseif ($source === 'vehicle_type') {
        $sql = 'SELECT rowid, label FROM '.MAIN_DB_PREFIX.'frota_veiculo_type WHERE entity = '.((int) $conf->entity).' AND active = 1 ORDER BY label';
    } elseif ($source === 'operation_type') {
        $sql = 'SELECT rowid, label FROM '.MAIN_DB_PREFIX.'frota_tipo_operacao WHERE entity = '.((int) $conf->entity).' AND active = 1 ORDER BY label';
    } elseif ($source === 'product') {
        $sql = 'SELECT rowid, CONCAT(ref, " - ", label) label FROM '.MAIN_DB_PREFIX.'product WHERE entity IN ('.getEntity('product').') AND fk_product_type = 0 AND stockable_product = 1 ORDER BY ref';
    } elseif ($source === 'warehouse') {
        $sql = 'SELECT rowid, ref label FROM '.MAIN_DB_PREFIX.'entrepot WHERE entity IN ('.getEntity('stock').') AND statut > 0 ORDER BY ref';
    } elseif ($source === 'project') {
        $sql = 'SELECT rowid, CONCAT(ref, " - ", title) label FROM '.MAIN_DB_PREFIX.'projet WHERE entity IN ('.getEntity('project').') ORDER BY ref';
    } elseif ($source === 'task') {
        $sql = 'SELECT t.rowid, CONCAT(p.ref, " - ", t.label) label FROM '.MAIN_DB_PREFIX.'projet_task t INNER JOIN '.MAIN_DB_PREFIX.'projet p ON p.rowid = t.fk_projet WHERE p.entity IN ('.getEntity('project').') ORDER BY p.ref, t.label';
    } elseif ($source === 'user') {
        $sql = 'SELECT rowid, login label FROM '.MAIN_DB_PREFIX.'user WHERE statut = 1 ORDER BY login';
    } elseif ($source === 'active_status') {
        return array(1=>'Enabled', 0=>'Disabled');
    } elseif ($source === 'maintenance_type') {
        return array('preventiva'=>'FrotaPreventive', 'corretiva'=>'FrotaCorrective');
    }
    if ($sql) {
        $resql = $db->query($sql);
        while ($resql && ($obj = $db->fetch_object($resql))) {
            $options[$obj->rowid] = $obj->label;
        }
    }
    $cache[$cacheKey] = $options;
    return $options;
}

function frotaRenderInput(DoliDB $db, $object, $field, $inputType, $disabled = false)
{
    $value = isset($object->{$field}) ? $object->{$field} : '';
    $attr = $disabled ? ' disabled' : '';
    if ($object instanceof CommonObject && !empty($object->fields[$field]['type']) && preg_match('/^(integer|link):/i', $object->fields[$field]['type'])) {
        if ($disabled) {
            return $object->showOutputField($object->fields[$field], $field, $value);
        }
        return $object->showInputField($object->fields[$field], $field, $value, '', '', '', 'minwidth300 maxwidth500', 1);
    }
    if (in_array($inputType, array('vehicle', 'implement', 'vehicle_type', 'operation_type', 'product', 'warehouse', 'project', 'task', 'user', 'active_status', 'maintenance_type'), true)) {
        $out = '<select class="flat minwidth300" name="'.$field.'"'.$attr.'><option value=""></option>';
        foreach (frotaOptions($db, $inputType) as $key => $label) {
            $out .= '<option value="'.dol_escape_htmltag((string) $key).'"'.((string) $value === (string) $key ? ' selected' : '').'>'.dol_escape_htmltag($GLOBALS['langs']->trans($label)).'</option>';
        }
        return $out.'</select>';
    }
    if ($inputType === 'textarea') {
        return '<textarea class="flat minwidth500" rows="4" name="'.$field.'"'.$attr.'>'.dol_escape_htmltag((string) $value).'</textarea>';
    }
    if ($inputType === 'datetime') {
        $value = $value ? str_replace(' ', 'T', substr((string) $value, 0, 16)) : '';
        return '<input class="flat" type="datetime-local" name="'.$field.'" value="'.dol_escape_htmltag($value).'"'.$attr.'>';
    }
    if ($inputType === 'boolean') {
        return '<input type="checkbox" name="'.$field.'" value="1"'.(!empty($value) ? ' checked' : '').$attr.'>';
    }
    if ($inputType === 'number' || $inputType === 'integer') {
        return '<input class="flat" type="number" step="'.($inputType === 'integer' ? '1' : '0.00000001').'" name="'.$field.'" value="'.dol_escape_htmltag((string) $value).'"'.$attr.'>';
    }
    return '<input class="flat minwidth300" type="text" name="'.$field.'" value="'.dol_escape_htmltag((string) $value).'"'.$attr.'>';
}

function frotaFieldLabel($object, $field)
{
    return isset($object->fields[$field]['label']) ? $object->fields[$field]['label'] : $field;
}

function frotaDisplayValue(DoliDB $db, $object, $config, $field)
{
    $value = isset($object->{$field}) ? $object->{$field} : '';
    if ($field === 'status') {
        return frotaStatusLabel($object->element, $value);
    }
    $inputType = isset($config['form'][$field]) ? $config['form'][$field] : '';
    if ($inputType === 'boolean') {
        return $GLOBALS['langs']->trans(!empty($value) ? 'Yes' : 'No');
    }
    if (in_array($inputType, array('vehicle', 'implement', 'vehicle_type', 'operation_type', 'product', 'warehouse', 'project', 'task', 'user', 'active_status', 'maintenance_type'), true)) {
        $options = frotaOptions($db, $inputType);
        if (isset($options[$value])) {
            return $GLOBALS['langs']->trans($options[$value]);
        }
    }
    if (in_array($field, array('unit_price', 'total_amount', 'labor_cost', 'parts_cost', 'total_cost', 'estimated_hour_cost', 'estimated_total_cost'), true)) {
        return price($value);
    }
    return (string) $value;
}

function frotaStatusLabel($type, $status)
{
    $key = '';
    if (in_array($type, array('veiculo', 'implemento'), true)) {
        return $GLOBALS['langs']->trans(((int) $status === 1) ? 'Enabled' : 'Disabled');
    }
    if ($type === 'abastecimento') {
        $key = array(0=>'FrotaStatusDraft', 1=>'FrotaStatusConfirmed', 9=>'FrotaStatusCanceled')[(int) $status] ?? '';
    } elseif ($type === 'manutencao') {
        $key = array(0=>'FrotaStatusDraft', 1=>'FrotaStatusInProgress', 2=>'FrotaStatusCompleted', 9=>'FrotaStatusCanceled')[(int) $status] ?? '';
    }
    return $key ? $GLOBALS['langs']->trans($key) : (string) $status;
}

function frotaStatusBadge($type, $status)
{
    $class = 'inactive';
    if (in_array($type, array('veiculo', 'implemento'), true)) {
        $class = ((int) $status === 1) ? 'active' : 'inactive';
    } elseif ($type === 'abastecimento') {
        $class = array(0=>'draft', 1=>'confirmed', 9=>'canceled')[(int) $status] ?? 'inactive';
    } elseif ($type === 'manutencao') {
        $class = array(0=>'draft', 1=>'progress', 2=>'completed', 9=>'canceled')[(int) $status] ?? 'inactive';
    }
    return '<span class="frota-status frota-status-'.$class.'">'.dol_escape_htmltag(frotaStatusLabel($type, $status)).'</span>';
}

function frotaFieldHelpKey($type, $field)
{
    $keys = array(
        'veiculo'=>array(
            'ref'=>'FrotaHelpVehicleRef',
            'fk_veiculo_type'=>'FrotaHelpVehicleType',
            'asset_number'=>'FrotaHelpAssetNumber',
            'horimeter'=>'FrotaHelpVehicleHorimeter',
            'odometer'=>'FrotaHelpVehicleOdometer',
            'estimated_hour_cost'=>'FrotaHelpEstimatedHourCost',
            'status'=>'FrotaHelpActiveStatus',
            'note'=>'FrotaHelpVehicleNote',
        ),
        'implemento'=>array(
            'ref'=>'FrotaHelpImplementRef',
            'type'=>'FrotaHelpImplementType',
            'status'=>'FrotaHelpActiveStatus',
            'note'=>'FrotaHelpImplementNote',
        ),
        'manutencao'=>array(
            'fk_veiculo'=>'FrotaHelpMaintenanceVehicle',
            'fk_implement'=>'FrotaHelpMaintenanceImplement',
            'date_planned'=>'FrotaHelpMaintenancePlannedDate',
            'due_horimeter'=>'FrotaHelpMaintenanceDueHorimeter',
            'due_odometer'=>'FrotaHelpMaintenanceDueOdometer',
            'is_periodic'=>'FrotaHelpPeriodicMaintenance',
            'period_days'=>'FrotaHelpPeriodDays',
            'period_hours'=>'FrotaHelpPeriodHours',
            'period_km'=>'FrotaHelpPeriodKm',
        ),
    );
    return $keys[$type][$field] ?? '';
}

function frotaMaintenanceDueState($maintenance, $now = null)
{
    $now = $now === null ? dol_now() : $now;
    $reasons = array();
    if (!empty($maintenance->date_planned) && $GLOBALS['db']->jdate($maintenance->date_planned) <= $now) {
        $reasons[] = 'FrotaMaintenanceDueByDate';
    }
    if (!empty($maintenance->due_horimeter) && isset($maintenance->vehicle_horimeter) && (float) $maintenance->vehicle_horimeter >= (float) $maintenance->due_horimeter) {
        $reasons[] = 'FrotaMaintenanceDueByHorimeter';
    }
    if (!empty($maintenance->due_odometer) && isset($maintenance->vehicle_odometer) && (float) $maintenance->vehicle_odometer >= (float) $maintenance->due_odometer) {
        $reasons[] = 'FrotaMaintenanceDueByOdometer';
    }
    if ($reasons) {
        return array('key'=>'FrotaMaintenanceDue', 'class'=>'progress', 'reasons'=>$reasons);
    }
    if (!empty($maintenance->date_planned) && $GLOBALS['db']->jdate($maintenance->date_planned) <= dol_time_plus_duree($now, 30, 'd')) {
        return array('key'=>'FrotaMaintenanceDueSoon', 'class'=>'draft', 'reasons'=>array('FrotaMaintenanceDueByDate'));
    }
    if (!empty($maintenance->date_planned) || !empty($maintenance->due_horimeter) || !empty($maintenance->due_odometer)) {
        return array('key'=>'FrotaMaintenanceScheduled', 'class'=>'active', 'reasons'=>array());
    }
    return array('key'=>'FrotaMaintenanceNoTrigger', 'class'=>'inactive', 'reasons'=>array());
}

function frotaMaintenanceDueBadge($maintenance, $withReasons = false)
{
    global $langs;
    $state = frotaMaintenanceDueState($maintenance);
    $out = '<span class="frota-status frota-status-'.$state['class'].'">'.$langs->trans($state['key']).'</span>';
    if ($withReasons && $state['reasons']) {
        $labels = array();
        foreach ($state['reasons'] as $reason) {
            $labels[] = $langs->trans($reason);
        }
        $out .= '<span class="frota-table-secondary">'.dol_escape_htmltag(implode(', ', $labels)).'</span>';
    }
    return $out;
}

function frotaMaintenanceScheduleSummary($maintenance)
{
    global $langs;
    $parts = array();
    if (!empty($maintenance->date_planned)) {
        $parts[] = dol_print_date($GLOBALS['db']->jdate($maintenance->date_planned), 'day');
    }
    if (!empty($maintenance->due_horimeter)) {
        $parts[] = $langs->trans('FrotaHorimeterShort').': '.price($maintenance->due_horimeter);
    }
    if (!empty($maintenance->due_odometer)) {
        $parts[] = $langs->trans('FrotaOdometerShort').': '.price($maintenance->due_odometer).' km';
    }
    return $parts ? implode(' | ', $parts) : $langs->trans('FrotaMaintenanceNoTrigger');
}

function frotaBuildUrl($file, $params = array())
{
    $url = dol_buildpath('/frota/'.$file, 1);
    return $params ? $url.'?'.http_build_query($params) : $url;
}
