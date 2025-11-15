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
$start_date = GETPOST('start_date', 'alpha');
$end_date = GETPOST('end_date', 'alpha');

// Page header
llxHeader('', $langs->trans("MaintenanceReport"));

$form = new Form($db);

print load_fiche_titre($langs->trans("MaintenanceReportPrevVsReal"), '', 'fa-tasks');

// Filter form
print '<form method="get" action="'.$_SERVER['PHP_SELF'].'">';
print '<table class="border" width="100%">';

// Vehicle filter
print '<tr><td class="titlefield">'.$langs->trans('Veiculo').'</td><td>';
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
print '</td></tr>';

// Status filter
print '<tr><td class="titlefield">'.$langs->trans('Status').'</td><td>';
$status_options = array(
    '' => $langs->trans("All"),
    'prevista' => $langs->trans("Scheduled"),
    'realizada' => $langs->trans("Completed"),
    'atrasada' => $langs->trans("Late")
);
print $form->selectarray('search_status', $status_options, $search_status, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
print '</td></tr>';

// Date range filter
print '<tr><td class="titlefield">'.$langs->trans('ScheduledDate').'</td><td>';
print $langs->trans('From').' '.$form->selectDate($start_date ? dol_stringtotime($start_date) : '', 'start_date', 0, 0, 1, '', 1, 0, 1);
print ' '.$langs->trans('to').' '.$form->selectDate($end_date ? dol_stringtotime($end_date) : '', 'end_date', 0, 0, 1, '', 1, 0, 1);
print '</td></tr>';

print '<tr><td colspan="2" class="center"><input type="submit" class="button" value="'.$langs->trans('Search').'"></td></tr>';

print '</table>';
print '</form><br>';


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
    $sql .= " AND m.data_prevista >= '".$db->idate(dol_stringtotime($start_date))."'";
}
if (!empty($end_date)) {
    $sql .= " AND m.data_prevista <= '".$db->idate(dol_stringtotime($end_date))."'";
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

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans('Veiculo').'</th>';
    print '<th>'.$langs->trans('MaintenanceType').'</th>';
    print '<th>'.$langs->trans('Description').'</th>';
    print '<th>'.$langs->trans('ScheduledDate').'</th>';
    print '<th>'.$langs->trans('CompletedDate').'</th>';
    print '<th>'.$langs->trans('Status').'</th>';
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
            print '<td><span class="badge '.$status_class.'">'.$status_label.'</span></td>';

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
