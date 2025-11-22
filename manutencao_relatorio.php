<?php
/*
 * Page to display a report of scheduled vs. completed maintenance
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once __DIR__.'/class/manutencao.class.php';
require_once __DIR__.'/class/veiculo.class.php';

$langs->loadLangs(array("frota@frota"));

// Security check
if (!$user->hasRight('frota', 'manutencao', 'read')) {
    accessforbidden();
}

// Parameters
$fk_veiculo = GETPOST('fk_veiculo', 'int');
$search_status = GETPOST('search_status', 'alpha');

// Date range
$start_date_day = GETPOST('start_dateday', 'int');
$start_date_month = GETPOST('start_datemonth', 'int');
$start_date_year = GETPOST('start_dateyear', 'int');
$start_date = 0;
if ($start_date_year && $start_date_month && $start_date_day) {
    $start_date = dol_mktime(0, 0, 0, $start_date_month, $start_date_day, $start_date_year);
}

$end_date_day = GETPOST('end_dateday', 'int');
$end_date_month = GETPOST('end_datemonth', 'int');
$end_date_year = GETPOST('end_dateyear', 'int');
$end_date = 0;
if ($end_date_year && $end_date_month && $end_date_day) {
    $end_date = dol_mktime(23, 59, 59, $end_date_month, $end_date_day, $end_date_year);
}


// Page header
llxHeader('', $langs->trans("MaintenanceReport"));

// --- Overdue Maintenance Alerts ---
$sql_overdue = "SELECT v.label as veiculo_label, TIMESTAMPDIFF(HOUR, m.data_prevista, NOW()) as horas_atraso
                FROM ".MAIN_DB_PREFIX."frota_manutencao as m
                LEFT JOIN ".MAIN_DB_PREFIX."frota_veiculo as v ON m.fk_veiculo = v.rowid
                WHERE m.data_concluida IS NULL AND m.data_prevista <= NOW() AND v.label IS NOT NULL";

$res_overdue = $db->query($sql_overdue);
if ($res_overdue) {
    while ($obj = $db->fetch_object($res_overdue)) {
        if ($obj->horas_atraso > 0) {
            $message = sprintf("%s precisa de revisão – %sh atrasado", $obj->veiculo_label, $obj->horas_atraso);
            print dol_htmloutput_mesg($message, '', 'warning');
        }
    }
}

$form = new Form($db);

// --- Summary ---
$sql_summary = "SELECT
    SUM(CASE WHEN m.data_concluida IS NOT NULL THEN 1 ELSE 0 END) as count_realizada,
    SUM(CASE WHEN m.data_concluida IS NULL AND m.data_prevista > NOW() THEN 1 ELSE 0 END) as count_prevista,
    SUM(CASE WHEN m.data_concluida IS NULL AND m.data_prevista <= NOW() THEN 1 ELSE 0 END) as count_atrasada
    FROM ".MAIN_DB_PREFIX."frota_manutencao as m";
if (!empty($fk_veiculo)) {
    $sql_summary .= " WHERE m.fk_veiculo = ".(int)$fk_veiculo;
}

$res_summary = $db->query($sql_summary);
if ($res_summary) {
    $summary = $db->fetch_object($res_summary);
    print '<div class="ficheaddleft">';
    print '<div class="div-table-responsive">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre_infoproduit">';
    print '<td class="center">'.$langs->trans("Completed").'</td>';
    print '<td class="center">'.$langs->trans("Scheduled").'</td>';
    print '<td class="center">'.$langs->trans("Late").'</td>';
    print '</tr>';
    print '<tr class="oddeven">';
    print '<td class="center" style="padding: 5px;"><span class="badge badge-status4" style="font-size: 1.5em;">'.(int)$summary->count_realizada.'</span></td>';
    print '<td class="center" style="padding: 5px;"><span class="badge badge-status5" style="font-size: 1.5em;">'.(int)$summary->count_prevista.'</span></td>';
    print '<td class="center" style="padding: 5px;"><span class="badge badge-status6" style="font-size: 1.5em;">'.(int)$summary->count_atrasada.'</span></td>';
    print '</tr>';
    print '</table>';
    print '</div>';
    print '</div>';
}


// --- Filter form ---
print '<div class="ficheaddright">';
print '<div class="box">';
print '<form method="get" action="'.$_SERVER['PHP_SELF'].'">';
print '<table class="noborder" width="100%">';

// Line 1
print '<tr>';
print '<td class="titlefield">'.$langs->trans('Veiculo').'</td>';
print '<td>';
$veiculo = new Veiculo($db);
$all_veiculos = $veiculo->fetchAll('ASC', 'label');
$options = '<option value="">'.$langs->trans("All").'</option>';
if (is_array($all_veiculos)) {
    foreach ($all_veiculos as $v) {
        $selected = ($fk_veiculo == $v->id) ? ' selected' : '';
        $options .= '<option value="'.$v->id.'"'.$selected.'>'.dol_escape_htmltag($v->label).'</option>';
    }
}
print '<select class="flat" name="fk_veiculo">'.$options.'</select>';
print '</td>';

print '<td class="titlefield">'.$langs->trans('Status').'</td>';
print '<td>';
$status_options = array(
    '' => $langs->trans("All"),
    'prevista' => $langs->trans("Scheduled"),
    'realizada' => $langs->trans("Completed"),
    'atrasada' => $langs->trans("Late")
);
print $form->selectarray('search_status', $status_options, $search_status, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
print '</td>';

print '<td class="right" rowspan="2" valign="middle">';
print '<input type="submit" class="button" value="'.$langs->trans('Search').'">';
print '</td>';
print '</tr>';

// Line 2
print '<tr>';
print '<td class="titlefield">'.$langs->trans('ScheduledDate').'</td>';
print '<td colspan="3">';
print $langs->trans('From').' '.$form->selectDate($start_date, 'start_date', 0, 0, 1, '', 1);
print ' '.$langs->trans('to').' '.$form->selectDate($end_date, 'end_date', 0, 0, 1, '', 1);
print '</td>';
print '</tr>';

print '</table>';
print '</form>';
print '</div>';
print '</div>';

print '<div style="clear:both"></div><br>';


// Build SQL query
$sql = "SELECT m.rowid, m.label as desc_manutencao, m.data_prevista, m.data_concluida, m.tipo, v.label as veiculo_label, ";
$sql .= " CASE
            WHEN m.data_concluida IS NOT NULL THEN 'realizada'
            WHEN m.data_prevista > NOW() THEN 'prevista'
            ELSE 'atrasada'
          END as status_calc
        FROM ".MAIN_DB_PREFIX."frota_manutencao as m
        LEFT JOIN ".MAIN_DB_PREFIX."frota_veiculo as v ON m.fk_veiculo = v.rowid
        WHERE 1=1";

if (!empty($fk_veiculo)) {
    $sql .= " AND m.fk_veiculo = ".(int)$fk_veiculo;
}
if (!empty($start_date)) {
    $sql .= " AND m.data_prevista >= '".$db->idate($start_date)."'";
}
if (!empty($end_date)) {
    $sql .= " AND m.data_prevista <= '".$db->idate($end_date)."'";
}

// The HAVING clause must be after WHERE and before ORDER BY
$sql_having = '';
if (!empty($search_status)) {
    $sql_having = " HAVING status_calc = '".$db->escape($search_status)."'";
}

$sql .= $sql_having;
$sql .= " ORDER BY m.data_prevista DESC";

$resql = $db->query($sql);

if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;

    $param = '';
    if (!empty($fk_veiculo)) {
        $param .= '&fk_veiculo=' . urlencode($fk_veiculo);
    }
    if (!empty($search_status)) {
        $param .= '&search_status=' . urlencode($search_status);
    }
    if (!empty($start_date)) {
        $param .= '&start_dateday=' . $start_date_day . '&start_datemonth=' . $start_date_month . '&start_dateyear=' . $start_date_year;
    }
    if (!empty($end_date)) {
        $param .= '&end_dateday=' . $end_date_day . '&end_datemonth=' . $end_date_month . '&end_dateyear=' . $end_date_year;
    }

    $title = $langs->trans("MaintenanceReportPrevVsReal");

    $arrayfields = array(
        'veiculo_label' => array('label' => $langs->trans("Veiculo"), 'checked' => 1),
        'tipo' => array('label' => $langs->trans("MaintenanceType"), 'checked' => 1),
        'desc_manutencao' => array('label' => $langs->trans("Description"), 'checked' => 1),
        'data_prevista' => array('label' => $langs->trans("ScheduledDate"), 'checked' => 1, 'type' => 'date'),
        'data_concluida' => array('label' => $langs->trans("CompletedDate"), 'checked' => 1, 'type' => 'date'),
        'status_calc' => array('label' => $langs->trans("Status"), 'checked' => 1),
    );

    print_barre_liste($title, 0, $_SERVER["PHP_SELF"], $param, 'm.data_prevista', 'DESC', '', $num, $num, 'fa-tasks');

    print '<table class="liste" width="100%">';
    print '<tr class="liste_titre">';
    foreach ($arrayfields as $key => $val) {
        if (!empty($val['checked'])) {
            print '<th>'.$val['label'].'</th>';
        }
    }
    print '</tr>';

    if ($num > 0) {
        $manutencao_static = new Manutencao($db);

        while ($i < $num) {
            $obj = $db->fetch_object($resql);
            print '<tr class="oddeven">';
            print '<td>'.dol_escape_htmltag($obj->veiculo_label).'</td>';
            print '<td>'.$manutencao_static->fields['tipo']['arrayofkeyval'][$obj->tipo].'</td>';
            print '<td>'.dol_escape_htmltag($obj->desc_manutencao).'</td>';
            print '<td>'.dol_print_date($db->jdate($obj->data_prevista), 'day').'</td>';
            print '<td>'.($obj->data_concluida ? dol_print_date($db->jdate($obj->data_concluida), 'day') : $langs->trans('Pending')).'</td>';
            
            // Status with color
            $status_label = '';
            $status_class = '';
            if ($obj->status_calc == 'realizada') {
                $status_label = $langs->trans('Completed');
                $status_class = 'badge-status4'; // Green
            } elseif ($obj->status_calc == 'prevista') {
                $status_label = $langs->trans('Scheduled');
                $status_class = 'badge-status5'; // Blue
            } else {
                $status_label = $langs->trans('Late');
                $status_class = 'badge-status6'; // Red
            }
            print '<td class="center"><span class="badge '.$status_class.'">'.$status_label.'</span></td>';

            print '</tr>';
            $i++;
        }
    } else {
        print '<tr><td colspan="6" class="center">'.$langs->trans("NoRecordFound").'</td></tr>';
    }

    print '</table>';
    $db->free($resql);
} else {
    dol_print_error($db);
}

// Page footer
llxFooter();
$db->close();
?>
