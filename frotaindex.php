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

// Rent Expenses
$sql_rent_expenses = "SELECT SUM(amount) as total_amount FROM ".MAIN_DB_PREFIX."frota_aluguel WHERE status = 1 AND inicio BETWEEN '".$db->escape($current_month_start)."' AND '".$db->escape($current_month_end)."'";
$res_rent_expenses = $db->query($sql_rent_expenses);
$rent_expenses = 0;
if ($res_rent_expenses) {
    $obj = $db->fetch_object($res_rent_expenses);
    $rent_expenses = (float) $obj->total_amount;
}

// Insurance Expenses
$sql_insurance_expenses = "SELECT SUM(amount) as total_amount FROM ".MAIN_DB_PREFIX."frota_seguro WHERE status = 1 AND inicio BETWEEN '".$db->escape($current_month_start)."' AND '".$db->escape($current_month_end)."'";
$res_insurance_expenses = $db->query($sql_insurance_expenses);
$insurance_expenses = 0;
if ($res_insurance_expenses) {
    $obj = $db->fetch_object($res_insurance_expenses);
    $insurance_expenses = (float) $obj->total_amount;
}

$total_monthly_expenses = $maintenance_expenses + $fuel_expenses + $rent_expenses + $insurance_expenses;

print '<style>
.dashboard-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
    padding: 20px 0;
}

.dashboard-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
    width: 90%;
}

.dashboard-card {
    position: relative;
    flex: 1;
    min-width: 280px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 12px;
    padding: 20px 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    overflow: hidden;
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
}

.dashboard-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 6px;
    border-radius: 12px 0 0 12px;
}

.border-green::before { background: linear-gradient(180deg, #2ecc71, #27ae60); }
.border-orange::before { background: linear-gradient(180deg, #ffb74d, #f57c00); }
.border-blue::before { background: linear-gradient(180deg, #42a5f5, #1e88e5); }

.dashboard-icon {
    font-size: 36px;
    margin-right: 15px;
    color: #555;
    opacity: 0.9;
    transition: color 0.3s ease;
}

.dashboard-card:hover .dashboard-icon {
    color: #000;
    opacity: 1;
}

.dashboard-info {
    display: flex;
    flex-direction: column;
}

.dashboard-title {
    font-size: 16px;
    font-weight: 600;
    color: #555;
    margin-bottom: 5px;
}

.dashboard-value {
    font-size: 24px;
    font-weight: bold;
    color: #222;
}

/* Seções */
.dashboard-section {
    margin-top: 30px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 20px 25px;
    transition: box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.dashboard-section:hover {
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
}

.dashboard-section::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 6px;
    border-radius: 10px 0 0 10px;
}

.border-green-section::before { background: linear-gradient(180deg, #2ecc71, #27ae60); }
.border-orange-section::before { background: linear-gradient(180deg, #ffb74d, #f57c00); }
.border-blue-section::before { background: linear-gradient(180deg, #42a5f5, #1e88e5); }

.section-title {
    display: flex;
    align-items: center;
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
}

.section-title i {
    margin-right: 8px;
    font-size: 22px;
}

/* Tabelas modernas */
.modern-table {
    border-collapse: collapse;
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    font-size: 14px;
}

.modern-table thead tr {
    background: #f0f2f5;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.modern-table th, 
.modern-table td {
    padding: 10px 14px;
    text-align: left;
    border-bottom: 1px solid #e5e5e5;
}

.modern-table tr:nth-child(even) {
    background: #fafafa;
}

.modern-table tr:hover {
    background: #f3faff;
    transition: background 0.3s ease;
}
</style>';


// ======= DASHBOARD CARDS =======
print '
<div class="dashboard-wrapper">
    <div class="dashboard-row">

        <div class="dashboard-card border-green">
            <div class="dashboard-icon"><i class="fa fa-car"></i></div>
            <div class="dashboard-info">
                <div class="dashboard-title">'.$langs->trans("ActiveVehicles").'</div>
                <div class="dashboard-value">'.$active_vehicles.'</div>
            </div>
        </div>

        <div class="dashboard-card border-orange">
            <div class="dashboard-icon"><i class="fa fa-wrench"></i></div>
            <div class="dashboard-info">
                <div class="dashboard-title">'.$langs->trans("VehiclesInMaintenance").'</div>
                <div class="dashboard-value">'.$vehicles_in_maintenance.'</div>
            </div>
        </div>

        <div class="dashboard-card border-blue">
            <div class="dashboard-icon"><i class="fa fa-money-bill-wave"></i></div>
            <div class="dashboard-info">
                <div class="dashboard-title">'.$langs->trans("TotalMonthlyExpenses").'</div>
                <div class="dashboard-value">'.price($total_monthly_expenses).'</div>
            </div>
        </div>

    </div>
</div>

<div class="clearboth"></div>
';


// ======= ACTIVE VEHICLES LIST =======
$sql_active_vehicles_list = "SELECT rowid FROM ".MAIN_DB_PREFIX."frota_veiculo WHERE status = 1 ORDER BY ref ASC";
$res_active_vehicles_list = $db->query($sql_active_vehicles_list);

if ($res_active_vehicles_list && $db->num_rows($res_active_vehicles_list) > 0) {
    print '<div class="dashboard-section border-green-section">';
    print '<h3 class="section-title"><i class="fa fa-car"></i> '.$langs->trans("ActiveVehicles").'</h3>';
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent modern-table">';
    print '<thead><tr class="liste_titre">';
    print '<th>'.$langs->trans("Ref").'</th>';
    print '<th>'.$langs->trans("Label").'</th>';
    print '<th>'.$langs->trans("Modelo").'</th>';
    print '<th>'.$langs->trans("Ano de fabricação").'</th>';
    print '</tr></thead><tbody>';

    $veiculo_static = new Veiculo($db);
    while ($obj_veiculo = $db->fetch_object($res_active_vehicles_list)) {
        $veiculo_static->fetch($obj_veiculo->rowid);
        print '<tr>';
        print '<td>'.$veiculo_static->getNomUrl(1).'</td>';
        print '<td>'.$veiculo_static->label.'</td>';
        print '<td>'.$veiculo_static->modelo.'</td>';
        print '<td>'.$veiculo_static->ano_fab.'</td>';
        print '</tr>';
    }
    print '</tbody></table></div></div>';
    $db->free($res_active_vehicles_list);
}


// ======= VEHICLES IN MAINTENANCE =======
$sql_maintenance_vehicles_list = "
    SELECT m.rowid, m.ref, m.data_prevista, m.amount, m.fk_veiculo 
    FROM ".MAIN_DB_PREFIX."frota_manutencao AS m
    WHERE m.status = 0 AND (m.data_concluida IS NULL OR m.data_concluida > NOW())
    ORDER BY m.data_prevista ASC
";
$res_maintenance_vehicles_list = $db->query($sql_maintenance_vehicles_list);

if ($res_maintenance_vehicles_list && $db->num_rows($res_maintenance_vehicles_list) > 0) {
    print '<div class="dashboard-section border-orange-section">';
    print '<h3 class="section-title"><i class="fa fa-wrench"></i> '.$langs->trans("VehiclesInMaintenance").'</h3>';
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent modern-table">';
    print '<thead><tr class="liste_titre">';
    print '<th><i class="fa fa-tools"></i> '.$langs->trans("REF.").'</th>';
    print '<th><i class="fa fa-car"></i> '.$langs->trans("VEÍCULO REF.").'</th>';
    print '<th><i class="fa fa-calendar-alt"></i> '.$langs->trans("DATA").'</th>';
    print '<th><i class="fa fa-dollar-sign"></i> '.$langs->trans("ESTIMATIVA DE PREÇO").'</th>';
    print '</tr></thead><tbody>';

    $manutencao_static = new Manutencao($db);
    $veiculo_static = new Veiculo($db);
    while ($obj_manutencao = $db->fetch_object($res_maintenance_vehicles_list)) {
        $manutencao_static->fetch($obj_manutencao->rowid);
        $veiculo_static->fetch($obj_manutencao->fk_veiculo);
        print '<tr>';
        print '<td>'.$manutencao_static->getNomUrl(1).'</td>';
        print '<td>'.$veiculo_static->getNomUrl(1).'</td>';
        print '<td>'.dol_print_date($db->jdate($obj_manutencao->data_prevista), "day").'</td>';
        print '<td>'.price($obj_manutencao->amount).'</td>';
        print '</tr>';
    }
    print '</tbody></table></div></div>';
    $db->free($res_maintenance_vehicles_list);
}


// ======= TOTAL MONTHLY EXPENSES =======
$current_month_start = date("Y-m-01 00:00:00");
$current_month_end = date("Y-m-t 23:59:59");
$all_expenses = [];

// Maintenance expenses
$sql_maintenance_expenses_list = "
    SELECT rowid, data_concluida AS date, 'Maintenance' AS type, ref AS description, amount 
    FROM ".MAIN_DB_PREFIX."frota_manutencao 
    WHERE status = 1 AND data_concluida BETWEEN '".$db->escape($current_month_start)."' AND '".$db->escape($current_month_end)."'
";
$res_maintenance_expenses_list = $db->query($sql_maintenance_expenses_list);
if ($res_maintenance_expenses_list) {
    while ($obj = $db->fetch_object($res_maintenance_expenses_list)) $all_expenses[] = $obj;
    $db->free($res_maintenance_expenses_list);
}

// Fuel expenses
$sql_fuel_expenses_list = "
    SELECT rowid, data_ab AS date, 'Fuel' AS type, CONCAT('Abastecimento ', ref) AS description, amount 
    FROM ".MAIN_DB_PREFIX."frota_abastecimento 
    WHERE data_ab BETWEEN '".$db->escape($current_month_start)."' AND '".$db->escape($current_month_end)."'
";
$res_fuel_expenses_list = $db->query($sql_fuel_expenses_list);
if ($res_fuel_expenses_list) {
    while ($obj = $db->fetch_object($res_fuel_expenses_list)) $all_expenses[] = $obj;
    $db->free($res_fuel_expenses_list);
}

// Rent expenses
$sql_rent_expenses_list = "
    SELECT rowid, inicio AS date, 'Aluguel' AS type, ref AS description, amount 
    FROM ".MAIN_DB_PREFIX."frota_aluguel 
    WHERE status = 1 AND inicio BETWEEN '".$db->escape($current_month_start)."' AND '".$db->escape($current_month_end)."'
";
$res_rent_expenses_list = $db->query($sql_rent_expenses_list);
if ($res_rent_expenses_list) {
    while ($obj = $db->fetch_object($res_rent_expenses_list)) $all_expenses[] = $obj;
    $db->free($res_rent_expenses_list);
}

// Insurance expenses
$sql_insurance_expenses_list = "
    SELECT rowid, inicio AS date, 'Seguro' AS type, ref AS description, amount 
    FROM ".MAIN_DB_PREFIX."frota_seguro 
    WHERE status = 1 AND inicio BETWEEN '".$db->escape($current_month_start)."' AND '".$db->escape($current_month_end)."'
";
$res_insurance_expenses_list = $db->query($sql_insurance_expenses_list);
if ($res_insurance_expenses_list) {
    while ($obj = $db->fetch_object($res_insurance_expenses_list)) $all_expenses[] = $obj;
    $db->free($res_insurance_expenses_list);
}

usort($all_expenses, fn($a, $b) => strtotime($b->date) - strtotime($a->date));

if (count($all_expenses) > 0) {
    print '<div class="dashboard-section border-blue-section">';
    print '<h3 class="section-title"><i class="fa fa-money-bill-wave"></i> '.$langs->trans("TotalMonthlyExpenses").'</h3>';
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent modern-table">';
    print '<thead><tr class="liste_titre">';
    print '<th>'.$langs->trans("Date").'</th>';
    print '<th>'.$langs->trans("Type").'</th>';
    print '<th>'.$langs->trans("Description").'</th>';
    print '<th>'.$langs->trans("Amount").'</th>';
    print '</tr></thead><tbody>';
    foreach ($all_expenses as $expense) {
        print '<tr>';
        print '<td>'.dol_print_date($db->jdate($expense->date), "day").'</td>';
        print '<td>'.$langs->trans($expense->type).'</td>';
        print '<td>'.$expense->description.'</td>';
        print '<td>'.price($expense->amount).'</td>';
        print '</tr>';
    }
    print '</tbody></table></div></div>';
}


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



$reservoir = new Reservatorio($db);
$reservoirs = $reservoir->fetchAll('DESC','rowid',8);
if (!empty($reservoirs)) {
    print '<div class="fichecenter">';
    print '<h3> Reservatórios (últimos 8) </h3>';

    print '<div style="display: flex; flex: 1; flex-wrap: wrap; width: 100%;">';

    $reservoirs_js = [];
    foreach($reservoirs as $key){
        print $key->getKanbanView(0);
        $reservoirs_js[] = [$key->ref, $key->nivel];
    }

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

<?php
}
?>


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

.section-title.blue {
    border-left: 6px solid #007bff;
}
.section-title.blue i {
    color: #007bff;
}


     


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
if (count($manutencoes) > 0) {
    print '<div class="dashboard-section border-blue">';
    print '<h3 class="section-title blue"><i class="fa fa-history"></i> '.$langs->trans("LatestVehicleMaintenances").'</h3>';

    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent modern-table">';
    print '<thead>';
    print '<tr class="liste_titre">';
    print '<th><i class="fa fa-tools"></i> '.$langs->trans("MaintenanceRef").'</th>';
    print '<th><i class="fa fa-car"></i> '.$langs->trans("VehicleRef").'</th>';
    print '<th><i class="fa fa-calendar-check"></i> '.$langs->trans("ScheduledDate").'</th>';
    print '<th><i class="fa fa-calendar-alt"></i> '.$langs->trans("CompletionDate").'</th>';
    print '<th><i class="fa fa-dollar-sign"></i> '.$langs->trans("Cost").'</th>';
    print '</tr>';
    print '</thead>';
    print '<tbody>';

    foreach ($manutencoes as $manutencao) {
        $veiculo = new Veiculo($db);
        $veiculo->fetch($manutencao->fk_veiculo);

        print '<tr>';
        print '<td><i class="fa fa-wrench"></i> '.$manutencao->ref.'</td>';
        print '<td>'.$veiculo->getNomUrl(1).'</td>';
        print '<td>'.dol_print_date($db->jdate($manutencao->data_prevista), 'day').'</td>';
        print '<td>'.($manutencao->data_concluida ? dol_print_date($db->jdate($manutencao->data_concluida), 'day') : '-').'</td>';
        print '<td>'.price($manutencao->amount).'</td>';
        print '</tr>';
    }

    print '</tbody>';
    print '</table>';
    print '</div>'; // div-table-responsive
    print '</div>'; // dashboard-section
};

print '</div>'; // Closes the main fichecenter



?>


<?php
// End of page
llxFooter();
$db->close();

?>