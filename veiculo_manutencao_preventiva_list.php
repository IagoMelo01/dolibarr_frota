<?php
/* Copyright (C) 2024 SuperAdmin <superadmin@superadmin.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file       veiculo_manutencao_preventiva_list.php
 *    \ingroup    frota
 *    \brief      Page to list vehicle preventive maintenance
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
dol_include_once('/frota/class/veiculo.class.php');
dol_include_once('/frota/class/veiculo_manutencao_preventiva.class.php');
dol_include_once('/frota/lib/frota_veiculo.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("frota@frota", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$fk_veiculo = GETPOST('fk_veiculo', 'int');
$preventiva_id = GETPOST('preventiva_id', 'int');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Initialize technical objects
$object = new Veiculo($db);
if ($fk_veiculo) {
    $object->fetch($fk_veiculo);
}

$preventiva = new VeiculoManutencaoPreventiva($db);
if ($preventiva_id) {
    $preventiva->fetch($preventiva_id);
}

$form = new Form($db);

/*
 * Actions
 */

if ($action == 'add' && $user->rights->frota->write && $confirm != 'yes') {
    $preventiva->fk_veiculo = $fk_veiculo;
    $preventiva->tipo_manutencao = GETPOST('tipo_manutencao');
    $preventiva->intervalo_km = GETPOST('intervalo_km', 'int');
    $preventiva->intervalo_horas = GETPOST('intervalo_horas', 'int');
    $preventiva->intervalo_dias = GETPOST('intervalo_dias', 'int');
    if ($preventiva->create($user) > 0) {
        setEventMessages($langs->trans("RecordCreated"), null, 'mesgs');
    } else {
        setEventMessages($preventiva->error, $preventiva->errors, 'errors');
    }
    header('Location: '.$_SERVER["PHP_SELF"].'?fk_veiculo='.$fk_veiculo);
    exit;
}

if ($action == 'update' && $user->rights->frota->write) {
    $preventiva->tipo_manutencao = GETPOST('tipo_manutencao');
    $preventiva->intervalo_km = GETPOST('intervalo_km', 'int');
    $preventiva->intervalo_horas = GETPOST('intervalo_horas', 'int');
    $preventiva->intervalo_dias = GETPOST('intervalo_dias', 'int');
    if ($preventiva->update($user) > 0) {
        setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
    } else {
        setEventMessages($preventiva->error, $preventiva->errors, 'errors');
    }
    header('Location: '.$_SERVER["PHP_SELF"].'?fk_veiculo='.$fk_veiculo);
    exit;
}

if ($action == 'delete' && $confirm == 'yes' && $user->rights->frota->write) {
    if ($preventiva->delete($user) > 0) {
        setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
    } else {
        setEventMessages($preventiva->error, $preventiva->errors, 'errors');
    }
    header('Location: '.$_SERVER["PHP_SELF"].'?fk_veiculo='.$fk_veiculo);
    exit;
}


/*
 * View
 */

$title = $langs->trans("ManutencaoPreventiva");

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-frota page-card');

$head = veiculo_prepare_head($object);
$current_head = 'manutencao_preventiva';
dol_fiche_head($head, $current_head, $langs->trans("Veiculo"), 0, 'veiculo');

if ($action == 'delete') {
    print $form->formconfirm($_SERVER["PHP_SELF"].'?preventiva_id='.$preventiva_id.'&fk_veiculo='.$fk_veiculo, $langs->trans('DeleteRecord'), $langs->trans('ConfirmDeleteRecord'), 'confirm_delete', '', 0, 1);
}

$form_action = 'add';
$form_title = $langs->trans("NovaManutencaoPreventiva");
if ($action == 'edit') {
    $form_action = 'update';
    $form_title = $langs->trans("EditarManutencaoPreventiva");
}

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="'.$form_action.'">';
print '<input type="hidden" name="fk_veiculo" value="'.$object->id.'">';
if ($action == 'edit') {
    print '<input type="hidden" name="preventiva_id" value="'.$preventiva->id.'">';
}

print '<table class="border centpercent">';
print '<thead>';
print '<tr><td colspan="5" class="titlefield"><b>'.$form_title.'</b></td></tr>';
print '</thead>';
print '<tbody>';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("TipoManutencao").'</td>';
print '<td>'.$langs->trans("IntervaloKm").'</td>';
print '<td>'.$langs->trans("IntervaloHoras").'</td>';
print '<td>'.$langs->trans("IntervaloDias").'</td>';
print '<td></td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td><input type="text" name="tipo_manutencao" value="'.$preventiva->tipo_manutencao.'"></td>';
print '<td><input type="number" name="intervalo_km" value="'.$preventiva->intervalo_km.'"></td>';
print '<td><input type="number" name="intervalo_horas" value="'.$preventiva->intervalo_horas.'"></td>';
print '<td><input type="number" name="intervalo_dias" value="'.$preventiva->intervalo_dias.'"></td>';
print '<td><input type="submit" class="button" value="'.($action == 'edit' ? $langs->trans("Update") : $langs->trans("Add")).'"></td>';
print '</tr>';
print '</tbody>';
print '</table>';

print '</form>';

print '<br>';

// List of preventive maintenances
$sql = "SELECT rowid, tipo_manutencao, intervalo_km, intervalo_horas, intervalo_dias, ultima_manutencao_data, proxima_manutencao_data FROM ".MAIN_DB_PREFIX."frota_veiculo_manutencao_preventiva WHERE fk_veiculo = ".$object->id;
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;
    
    print '<table class="noborder centpercent">';
    print '<thead>';
    print '<tr class="liste_titre"><td colspan="7"><b>'.$langs->trans("ManutencoesPreventivas").'</b></td></tr>';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("TipoManutencao").'</td>';
    print '<td>'.$langs->trans("IntervaloKm").'</td>';
    print '<td>'.$langs->trans("IntervaloHoras").'</td>';
    print '<td>'.$langs->trans("IntervaloDias").'</td>';
    print '<td>'.$langs->trans("UltimaManutencao").'</td>';
    print '<td>'.$langs->trans("ProximaManutencao").'</td>';
    print '<td></td>';
    print '</tr>';
    print '</thead>';
    print '<tbody>';

    if ($num > 0) {
        while ($i < $num) {
            $obj = $db->fetch_object($resql);
            print '<tr class="oddeven">';
            print '<td>'.$obj->tipo_manutencao.'</td>';
            print '<td>'.$obj->intervalo_km.'</td>';
            print '<td>'.$obj->intervalo_horas.'</td>';
            print '<td>'.$obj->intervalo_dias.'</td>';
            print '<td>'.dol_print_date($db->jdate($obj->ultima_manutencao_data), 'day').'</td>';
            print '<td>'.dol_print_date($db->jdate($obj->proxima_manutencao_data), 'day').'</td>';
            print '<td>';
            print '<a class="marginright" href="'.$_SERVER["PHP_SELF"].'?action=edit&preventiva_id='.$obj->rowid.'&fk_veiculo='.$object->id.'">'.img_edit().'</a>';
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&preventiva_id='.$obj->rowid.'&fk_veiculo='.$object->id.'">'.img_delete().'</a>';
            print '</td>';
            print '</tr>';
            $i++;
        }
    } else {
        print '<tr><td colspan="7" class="center">'.$langs->trans("NoRecordsFound").'</td></tr>';
    }
    print '</tbody>';
    print '</table>';
} else {
    dol_print_error($db);
}

print '</div>';

// End of page
llxFooter();
$db->close();