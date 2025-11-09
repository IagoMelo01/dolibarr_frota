<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       frota/frotaindex.php
 *	\ingroup    frota
 *	\brief      Home page of frota top menu
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/reservatorio.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/manutencao.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/veiculo.class.php';

// Load translation files required by the page
$langs->loadLangs(array("frota@frota"));

$action = GETPOST('action', 'aZ09');

$max = 5;
$now = dol_now();

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//if (!isModEnabled('frota')) {
//	accessforbidden('Module not enabled');
//}
//if (! $user->hasRight('frota', 'myobject', 'read')) {
//	accessforbidden();
//}
//restrictedArea($user, 'frota', 0, 'frota_myobject', 'myobject', '', 'rowid');
//if (empty($user->admin)) {
//	accessforbidden('Must be admin');
//}


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("FrotaArea"), '', '', 0, 0, '', '', '', 'mod-frota page-index');

print load_fiche_titre($langs->trans("FrotaArea"), '', 'frota.png@frota');

print '<div class="fichecenter">';

// Fetch data for the dashboard
// Active Vehicles
$sql_active_vehicles = "SELECT COUNT(*) as total_active FROM ".MAIN_DB_PREFIX."frota_veiculo WHERE status = 1";
$res_active_vehicles = $db->query($sql_active_vehicles);
$active_vehicles = 0;
if ($res_active_vehicles) {
    $obj = $db->fetch_object($res_active_vehicles);
    $active_vehicles = $obj->total_active;
}

// Vehicles in Maintenance
$sql_maintenance_vehicles = "SELECT COUNT(DISTINCT fk_veiculo) as total_maintenance FROM ".MAIN_DB_PREFIX."frota_manutencao WHERE status = 0 AND (data_concluida IS NULL OR data_concluida > NOW())";
$res_maintenance_vehicles = $db->query($sql_maintenance_vehicles);
$vehicles_in_maintenance = 0;
if ($res_maintenance_vehicles) {
    $obj = $db->fetch_object($res_maintenance_vehicles);
    $vehicles_in_maintenance = $obj->total_maintenance;
}

// Total Monthly Expenses
$current_month_start = date('Y-m-01 00:00:00');
$current_month_end = date('Y-m-t 23:59:59');

$sql_maintenance_expenses = "SELECT SUM(amount) as total_amount FROM ".MAIN_DB_PREFIX."frota_manutencao WHERE status = 1 AND data_concluida BETWEEN '".$db->escape($current_month_start)."' AND '".$db->escape($current_month_end)."'";
$res_maintenance_expenses = $db->query($sql_maintenance_expenses);
$maintenance_expenses = 0;
if ($res_maintenance_expenses) {
    $obj = $db->fetch_object($res_maintenance_expenses);
    $maintenance_expenses = (float) $obj->total_amount;
}

$sql_fuel_expenses = "SELECT SUM(amount) as total_amount FROM ".MAIN_DB_PREFIX."frota_abastecimento WHERE data_ab BETWEEN '".$db->escape($current_month_start)."' AND '".$db->escape($current_month_end)."'";
$res_fuel_expenses = $db->query($sql_fuel_expenses);
$fuel_expenses = 0;
if ($res_fuel_expenses) {
    $obj = $db->fetch_object($res_fuel_expenses);
    $fuel_expenses = (float) $obj->total_amount;
}

$total_monthly_expenses = $maintenance_expenses + $fuel_expenses;

print '<div class="dashboard-container">';

// Active Vehicles Box
print '<div class="dashboard-item-container">';
print '<div class="dashboard-item">';
print '<div class="dashboard-item-content">';
print '<div class="dashboard-item-title">'.$langs->trans("ActiveVehicles").'</div>';
print '<div class="dashboard-item-value">'.$active_vehicles.'</div>';
print '</div>';
print '<div class="dashboard-item-icon"><i class="fa fa-car"></i></div>';
print '</div>';
print '</div>';

// Vehicles in Maintenance Box
print '<div class="dashboard-item-container">';
print '<div class="dashboard-item">';
print '<div class="dashboard-item-content">';
print '<div class="dashboard-item-title">'.$langs->trans("VehiclesInMaintenance").'</div>';
print '<div class="dashboard-item-value">'.$vehicles_in_maintenance.'</div>';
print '</div>';
print '<div class="dashboard-item-icon"><i class="fa fa-wrench"></i></div>';
print '</div>';
print '</div>';

// Total Monthly Expenses Box
print '<div class="dashboard-item-container">';
print '<div class="dashboard-item">';
print '<div class="dashboard-item-content">';
print '<div class="dashboard-item-title">'.$langs->trans("TotalMonthlyExpenses").'</div>';
print '<div class="dashboard-item-value">'.price($total_monthly_expenses).'</div>';
print '</div>';
print '<div class="dashboard-item-icon"><i class="fa fa-money"></i></div>';
print '</div>';
print '</div>';

print '</div>';

print '<div class="clearboth"></div>';

// List Active Vehicles
print '<h3>'.$langs->trans("ActiveVehicles").'</h3>';
print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Ref").'</th>';
print '<th>'.$langs->trans("Label").'</th>';
print '<th>'.$langs->trans("Modelo").'</th>';
print '<th>'.$langs->trans("Ano de fabricação").'</th>';
print '</tr>';

$sql_active_vehicles_list = "SELECT rowid FROM ".MAIN_DB_PREFIX."frota_veiculo WHERE status = 1 ORDER BY ref ASC";
$res_active_vehicles_list = $db->query($sql_active_vehicles_list);

if ($res_active_vehicles_list) {
    $num = $db->num_rows($res_active_vehicles_list);
    if ($num > 0) {
        $veiculo_static = new Veiculo($db);
        while ($obj_veiculo = $db->fetch_object($res_active_vehicles_list)) {
            $veiculo_static->fetch($obj_veiculo->rowid);
            print '<tr class="oddeven">';
            print '<td>'.$veiculo_static->getNomUrl(1).'</td>';
            print '<td>'.$veiculo_static->label.'</td>';
            print '<td>'.$veiculo_static->modelo.'</td>';
            print '<td>'.$veiculo_static->ano_fab.'</td>';
            print '</tr>';
        }
    } else {
        print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
    }
    $db->free($res_active_vehicles_list);
} else {
    dol_print_error($db);
}
print '</table>';
print '</div>';

// List Vehicles in Maintenance
print '<h3>'.$langs->trans("VehiclesInMaintenance").'</h3>';
print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("MaintenanceRef").'</th>';
print '<th>'.$langs->trans("VehicleRef").'</th>';
print '<th>'.$langs->trans("ScheduledDate").'</th>';
print '<th>'.$langs->trans("EstimatedCost").'</th>';
print '</tr>';

$sql_maintenance_vehicles_list = "SELECT m.rowid, m.ref, m.data_prevista, m.amount, m.fk_veiculo FROM ".MAIN_DB_PREFIX."frota_manutencao as m WHERE m.status = 0 AND (m.data_concluida IS NULL OR m.data_concluida > NOW()) ORDER BY m.data_prevista ASC";
$res_maintenance_vehicles_list = $db->query($sql_maintenance_vehicles_list);

if ($res_maintenance_vehicles_list) {
    $num = $db->num_rows($res_maintenance_vehicles_list);
    if ($num > 0) {
        $manutencao_static = new Manutencao($db);
        $veiculo_static = new Veiculo($db);
        while ($obj_manutencao = $db->fetch_object($res_maintenance_vehicles_list)) {
            $manutencao_static->fetch($obj_manutencao->rowid);
            $veiculo_static->fetch($obj_manutencao->fk_veiculo);
            print '<tr class="oddeven">';
            print '<td>'.$manutencao_static->getNomUrl(1).'</td>';
            print '<td>'.$veiculo_static->getNomUrl(1).'</td>';
            print '<td>'.dol_print_date($db->jdate($obj_manutencao->data_prevista), 'day').'</td>';
            print '<td>'.price($obj_manutencao->amount).'</td>';
            print '</tr>';
        }
    } else {
        print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
    }
    $db->free($res_maintenance_vehicles_list);
} else {
    dol_print_error($db);
}
print '</table>';
print '</div>';
print '</div>';

// List of Total Monthly Expenses
print '<div class="fichecenter">';
print '<h3>'.$langs->trans("TotalMonthlyExpenses").'</h3>';
print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Date").'</th>';
print '<th>'.$langs->trans("Type").'</th>';
print '<th>'.$langs->trans("Description").'</th>';
print '<th>'.$langs->trans("Amount").'</th>';
print '</tr>';

$current_month_start = date('Y-m-01 00:00:00');
$current_month_end = date('Y-m-t 23:59:59');

$all_expenses = [];

// Fetch completed maintenance expenses for the current month
$sql_maintenance_expenses_list = "SELECT rowid, data_concluida as date, 'Maintenance' as type, ref as description, amount FROM ".MAIN_DB_PREFIX."frota_manutencao WHERE status = 1 AND data_concluida BETWEEN '".$db->escape($current_month_start)."' AND '".$db->escape($current_month_end)."'";
$res_maintenance_expenses_list = $db->query($sql_maintenance_expenses_list);
if ($res_maintenance_expenses_list) {
    while ($obj = $db->fetch_object($res_maintenance_expenses_list)) {
        $all_expenses[] = $obj;
    }
    $db->free($res_maintenance_expenses_list);
} else {
    dol_print_error($db);
}

// Fetch fuel expenses for the current month
$sql_fuel_expenses_list = "SELECT rowid, data_ab as date, 'Fuel' as type, CONCAT('Abastecimento ', ref) as description, amount FROM ".MAIN_DB_PREFIX."frota_abastecimento WHERE data_ab BETWEEN '".$db->escape($current_month_start)."' AND '".$db->escape($current_month_end)."'";
$res_fuel_expenses_list = $db->query($sql_fuel_expenses_list);
if ($res_fuel_expenses_list) {
    while ($obj = $db->fetch_object($res_fuel_expenses_list)) {
        $all_expenses[] = $obj;
    }
    $db->free($res_fuel_expenses_list);
} else {
    dol_print_error($db);
}

// Sort all expenses by date in descending order
usort($all_expenses, function($a, $b) {
    return strtotime($b->date) - strtotime($a->date);
});

if (count($all_expenses) > 0) {
    foreach ($all_expenses as $expense) {
        print '<tr class="oddeven">';
        print '<td>'.dol_print_date($db->jdate($expense->date), 'day').'</td>';
        print '<td>'.$langs->trans($expense->type).'</td>';
        print '<td>'.$expense->description.'</td>';
        print '<td>'.price($expense->amount).'</td>';
        print '</tr>';
    }
} else {
    print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
}
print '</table>';
print '</div>';
print '</div>';




/* BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (isModEnabled('frota') && $user->hasRight('frota', 'read')) {
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.tva as total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
	$sql.= ", s.code_client";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	if ($socid)	$sql.= " AND c.fk_soc = ".((int) $socid);

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftMyObjects").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$myobjectstatic->id=$obj->rowid;
				$myobjectstatic->ref=$obj->ref;
				$myobjectstatic->ref_client=$obj->ref_client;
				$myobjectstatic->total_ht = $obj->total_ht;
				$myobjectstatic->total_tva = $obj->total_tva;
				$myobjectstatic->total_ttc = $obj->total_ttc;

				print $myobjectstatic->getNomUrl(1);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '<td class="right" class="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{

				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
END MODULEBUILDER DRAFT MYOBJECT */




print '<div class="fichecenter">';
print '<h3> Reservatórios (últimos 8) </h3>';

print '<div style="display: flex; flex: 1; flex-wrap: wrap; width: 100%;">';

$reservoir = new Reservatorio($db);
$reservoirs = $reservoir->fetchAll('DESC','rowid',8);
$reservoirs_js = [];
foreach($reservoirs as $key){
	print $key->getKanbanView(0);
	$reservoirs_js[] = [$key->ref, $key->nivel];
}
// print $reservoir->getKanbanView(0);
// echo '<pre>';

// print_r($reservoirs_js);

// echo '</pre>';

print '</div>'; // Closes the flex div for reservoirs
print '</div>'; // Closes the fichecenter for reservoirs
?>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="fichecenter">
    <canvas id="reservoirChart" style="width: 100%; max-height: 20rem;"></canvas>
</div>
    <script>
        // Recupere os dados dos reservatórios do PHP
        var reservoirsData = <?php echo json_encode($reservoirs_js); ?>;

        // Extrai os nomes e níveis dos reservatórios
        var reservoirNames = reservoirsData.map(function(reservoir) {
			// alert(reservoir[1])
            return reservoir[0];
        });

        var reservoirLevels = reservoirsData.map(function(reservoir) {
            return reservoir[1];
        });


        // Crie um gráfico de barras
        var ctx = document.getElementById('reservoirChart').getContext('2d');
        var reservoirChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: reservoirNames,
                datasets: [{
                    label: 'Nível dos Reservatórios',
                    data: reservoirLevels,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)', // Cor de fundo das barras
                    borderColor: 'rgba(54, 162, 235, 1)', // Cor da borda das barras
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    </script>




<style>
.dashboard-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center; /* Center the items horizontally */
    gap: 20px; /* Space between dashboard items */
    margin-bottom: 20px; /* Space below the dashboard items */
}
.dashboard-item-container {
    flex: 1 1 calc(33% - 20px); /* Roughly one-third width, accounting for gap */
    max-width: calc(33% - 20px); /* Ensure it doesn't grow too much */
    min-width: 280px; /* Minimum width for smaller screens */
    padding: 0; /* Remove padding as it's handled by gap */
}

/* Adjust existing styles for better centering and responsiveness */
@media (max-width: 768px) {
    .dashboard-item-container {
        flex: 1 1 calc(50% - 20px); /* Two items per row on medium screens */
        max-width: calc(50% - 20px);
    }
}

@media (max-width: 480px) {
    .dashboard-item-container {
        flex: 1 1 100%; /* One item per row on small screens */
        max-width: 100%;
    }
}

.dashboard-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,.05);
    height: 100px; /* Fixed height for consistency */
}
.dashboard-item-content {
    flex-grow: 1;
}
.dashboard-item-title {
    font-size: 14px;
    color: #777;
    margin-bottom: 5px;
}
.dashboard-item-value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}
.dashboard-item-icon {
    font-size: 40px;
    color: #007bff; /* Dolibarr primary color */
    margin-left: 15px;
}
.fichehalfleft {
    width: 100%;
    float: none;
}
.fichehalfright {
    display: none; /* Hide the right column if not needed */
}
.div-table-responsive {
    overflow-x: auto;
}
.notopnoleftnoright {
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
}
</style>

<style>
.veiculo {
	width: 100%;
	border-collapse: collapse;
}
.veiculo, .veic_th, .veic_td {
	border: 1px solid black;
	padding: 8px;
	text-align: left;
}
</style>


<?php
// Array de objetos com informações de manutenção de veículos
$manutencoes_obj = new Manutencao($db);
$filter_maintenance = array(
    'status' => array(Manutencao::STATUS_DRAFT, Manutencao::STATUS_VALIDATED),
    'customsql' => "(t.data_concluida IS NULL OR t.data_concluida > NOW())"
);
$manutencoes = $manutencoes_obj->fetchAll('DESC', 'rowid', 8, 0, $filter_maintenance);

// print_r($manutencoes);

// Função para formatar a data no formato desejado
function formatarData($data) {
    return date("d/m/Y", strtotime($data));
}

if(count($manutencoes) > 0) {
    echo '<div class="fichecenter">';
    echo '<h2>Últimas Manutenções de Veículos</h2>';
    echo '<table class="veiculo">';
    echo '<tr>';
    echo '<th class="veic_th">Manutenção</th>';
    echo '<th class="veic_th">Veículo</th>';
    echo '<th class="veic_th">Data Prevista</th>';
    echo '<th class="veic_th">Data Realizada</th>';
    echo '<th class="veic_th">Custo</th>';
    echo '</tr>';
    foreach ($manutencoes as $manutencao) {
		$veiculo = new Veiculo($db);
		$veiculo->fetch($manutencao->fk_veiculo);
        echo '<tr>';
        echo '<td class="veic_td"><i class="fas fa-wrench"></i>		' . $manutencao->ref . '</td>';
        echo '<td class="veic_td">' . $veiculo->ref . '</td>';
        echo '<td class="veic_td">' . formatarData($manutencao->data_prevista) . '</td>';
        echo '<td class="veic_td">' . ($manutencao->data_concluida ? formatarData($manutencao->data_concluida) : '-') . '</td>';
        echo '<td class="veic_td">'.price($manutencao->amount).'</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
};

print '</div>'; // Closes the main fichecenter

?>


<?php
// End of page
llxFooter();
$db->close();

?>