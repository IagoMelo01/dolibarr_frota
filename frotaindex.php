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

print '<div class="fichecenter"><div class="fichethirdleft">';


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


print '</div><div class="fichetwothirdright">';


$NBMAX = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');

/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
if (isModEnabled('frota') && $user->hasRight('frota', 'read')) {
	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms";
	$sql.= " FROM ".MAIN_DB_PREFIX."frota_myobject as s";
	$sql.= " WHERE s.entity IN (".getEntity($myobjectstatic->element).")";
	//if ($socid)	$sql.= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BoxTitleLatestModifiedMyObjects", $max);
		print '</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$myobjectstatic->id=$objp->rowid;
				$myobjectstatic->ref=$objp->ref;
				$myobjectstatic->label=$objp->label;
				$myobjectstatic->status = $objp->status;

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
				print '<td class="right nowrap">';
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}
}
*/

print '</div></div>';

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

print '</div>';
?>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <canvas id="reservoirChart" style="width: 100%; max-height: 20rem;"></canvas>

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
$manutencoes= $manutencoes_obj->fetchAll('DESC','rowid',8);

// print_r($manutencoes);

// Função para formatar a data no formato desejado
function formatarData($data) {
    return date("d/m/Y", strtotime($data));
}

if($manutencoes > 0) {
    echo '<h2>Últimas Manutenções de Veículos</h2>';
    echo '<table class="veiculo">';
    echo '<tr>';
    echo '<th class="veic_th">Manutenção</th>';
    echo '<th class="veic_th">Veículo</th>';
    echo '<th class="veic_th">Data Prevista</th>';
    echo '<th class="veic_th">Data Realizada</th>';
    echo '</tr>';
    foreach ($manutencoes as $manutencao) {
		$veiculo = new Veiculo($db);
		$veiculo->fetch($manutencao->fk_veiculo);
        echo '<tr>';
        echo '<td class="veic_td"><i class="fas fa-wrench"></i>		' . $manutencao->ref . '</td>';
        echo '<td class="veic_td">' . $veiculo->ref . '</td>';
        echo '<td class="veic_td">' . formatarData($manutencao->data_prevista) . '</td>';
        echo '<td class="veic_td">' . ($manutencao->data_realizada ? formatarData($manutencao->data_concluida) : '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
};

?>


<?php
// End of page
llxFooter();
$db->close();

?>