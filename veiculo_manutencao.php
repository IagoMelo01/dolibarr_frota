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
 *    \file       veiculo_manutencao.php
 *    \ingroup    frota
 *    \brief      Page to display vehicle maintenance information
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
dol_include_once('/frota/lib/frota_veiculo.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("frota@frota", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new Veiculo($db);

// Load object
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
}

/*
 * View
 */

$title = $langs->trans("ManutencaoVeiculo");

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-frota page-card');

$head = veiculo_prepare_head($object);
$current_head = 'manutencao';
dol_fiche_head($head, $current_head, $langs->trans("Veiculo"), 0, 'veiculo');

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';

print '<a href="'.dol_buildpath('/frota/veiculo_manutencao_preventiva_list.php?fk_veiculo='.$object->id, 1).'" class="button">Gerenciar Manutenções Preventivas</a>';

print '<br><br>';

print '<table class="border centpercent">';

// Maintenance schedule
print '<tr><td colspan="8" class="titlefield"><b>'.$langs->trans("ManutencaoProgramada").'</b></td></tr>';

$sql = "SELECT tipo_manutencao, intervalo_km, intervalo_horas, intervalo_dias FROM llx_frota_veiculo_manutencao_preventiva WHERE fk_veiculo = ".$object->id;
$preventivas = $db->query($sql);

if ($preventivas) {
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("TipoManutencao").'</td>';
    print '<td>'.$langs->trans("UltimaManutencaoKm").'</td>';
    print '<td>'.$langs->trans("ProximaManutencaoKm").'</td>';
    print '<td>'.$langs->trans("UltimaManutencaoHoras").'</td>';
    print '<td>'.$langs->trans("ProximaManutencaoHoras").'</td>';
    print '<td>'.$langs->trans("UltimaManutencaoData").'</td>';
    print '<td>'.$langs->trans("ProximaManutencaoData").'</td>';
    print '<td>'.$langs->trans("Status").'</td>';
    print '</tr>';

    while ($preventiva = $db->fetch_object($preventivas)) {
        $sql = "SELECT MAX(quilometragem) as last_km, MAX(horimetro) as last_horas, MAX(data_concluida) as last_data FROM llx_frota_manutencao WHERE fk_veiculo = ".$object->id." AND tipo = '".$preventiva->tipo_manutencao."'";
        $manutencao = $db->fetch_object($db->query($sql));

        $next_km = $manutencao->last_km + $preventiva->intervalo_km;
        $next_horas = $manutencao->last_horas + $preventiva->intervalo_horas;
        $next_data = date('Y-m-d', strtotime($manutencao->last_data . ' + '.$preventiva->intervalo_dias.' days'));

        $status = '<span class="badge badge-success">Em dia</span>';
        if (($preventiva->intervalo_km && $object->quilometragem_inicial >= $next_km) ||
            ($preventiva->intervalo_horas && $object->horimetro_inicial >= $next_horas) ||
            ($preventiva->intervalo_dias && date('Y-m-d') >= $next_data)) {
            $status = '<span class="badge badge-danger">Atrasada</span>';
        }

        print '<tr class="oddeven">';
        print '<td>'.$preventiva->tipo_manutencao.'</td>';
        print '<td>'.($manutencao->last_km ? $manutencao->last_km : 0).'</td>';
        print '<td>'.($preventiva->intervalo_km ? $next_km : 'N/A').'</td>';
        print '<td>'.($manutencao->last_horas ? $manutencao->last_horas : 0).'</td>';
        print '<td>'.($preventiva->intervalo_horas ? $next_horas : 'N/A').'</td>';
        print '<td>'.($manutencao->last_data ? dol_print_date($manutencao->last_data, 'day') : 'N/A').'</td>';
        print '<td>'.($preventiva->intervalo_dias ? dol_print_date($next_data, 'day') : 'N/A').'</td>';
        print '<td>'.($status).'</td>';
        print '</tr>';
    }
}

// Cost analysis
print '<tr><td colspan="8" class="titlefield"><b>'.$langs->trans("AnaliseCustos").'</b></td></tr>';

$total_custo = 0;

// Get maintenance costs
$sql = "SELECT SUM(amount) as total FROM llx_frota_manutencao WHERE fk_veiculo = ".$object->id;
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $total_custo += $obj->total;
}

// Get fueling costs
$sql = "SELECT SUM(valor_total) as total FROM llx_frota_abastecimento WHERE fk_veiculo = ".$object->id;
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $total_custo += $obj->total;
}

// Get other costs (replace with your own tables)

$custo_km = ($object->quilometragem_inicial > 0) ? $total_custo / $object->quilometragem_inicial : 0;
$custo_hora = ($object->horimetro_inicial > 0) ? $total_custo / $object->horimetro_inicial : 0;

print '<tr class="liste_titre"><td colspan="2"><b>'.$langs->trans("CustoTotal").'</b></td><td colspan="2"><b>'.$langs->trans("CustoKm").'</b></td><td colspan="4"><b>'.$langs->trans("CustoHora").'</b></td></tr>';
print '<tr class="oddeven"><td colspan="2">'.price($total_custo).'</td><td colspan="2">'.price($custo_km).'</td><td colspan="4">'.price($custo_hora).'</td></tr>';

print '</table>';

print '</div>';

// End of page
llxFooter();
$db->close();
