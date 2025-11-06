<?php
/*
 * 
 * Page to view vehicle history entries
 */

// Load Dolibarr environment (pattern used in other pages)
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
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

// Classes
dol_include_once('/frota/class/historico.class.php');
dol_include_once('/frota/class/veiculo.class.php');

$langs->load('frota@frota');

// Parameters
$fk_veiculo = GETPOST('fk_veiculo', 'int');

// Security and permissions
if (!isModEnabled('frota')) accessforbidden();
if (empty($user->rights->frota->read) && empty($user->admin)) accessforbidden();

// Fetch vehicles for select
$vehicles = array();
$sqlv = "SELECT rowid, label
         FROM ".MAIN_DB_PREFIX."frota_veiculo
         ORDER BY label";

$resql = $db->query($sqlv);
$vehicles = array();

if ($resql) {
    while ($objv = $db->fetch_object($resql)) {
        $vehicles[] = array(
            'rowid' => $objv->rowid,
            'label' => $objv->label
        );
    }
}

// Page header
llxHeader('', $langs->trans('Historico'), '', '', 0, 0, array(), dol_buildpath('/frota/js/chart.min.js', 1));

print load_fiche_titre($langs->trans('Historico'), '', 'object_historico.png');

// Instructional text
print '<p>'.$langs->trans("SelectVehicleToSeeHistory").'</p>';

// Vehicle selection form
print '<form method="get" action="'.$_SERVER['PHP_SELF'].'">';
print '<table class="border centpercent">';
print '<tr class="pair"><td class="titlefieldcreate fieldrequired">'.$langs->trans('Vehicle').'</td><td>';
if (empty($vehicles)) {
    print '<div class="warning">'.$langs->trans('NoVehicleRegistered').'</div>';
} else {
    print '<select class="flat minwidth300" id="fk_veiculo" name="fk_veiculo" onchange="this.form.submit()">';
    print '<option value="">&nbsp;</option>';
    foreach ($vehicles as $v) {
        $sel = ($fk_veiculo == $v['rowid']) ? ' selected' : '';
        print '<option value="'.(int)$v['rowid'].'"'.$sel.'>'.dol_escape_htmltag($v['label']).'</option>';
    }
    print '</select>';
}
print '</td></tr>';
print '</table>';
print '</form>';

// Show history if vehicle selected
if (!empty($fk_veiculo)) {
    print '<div class="tabsAction">';
    print '<a class="butAction" href="veiculo_historico_card.php?fk_veiculo='.$fk_veiculo.'">'.$langs->trans("NewHistory").'</a>';
    print '</div>';

    $sql = "SELECT h.*, u.login as user_login FROM ".MAIN_DB_PREFIX."frota_veiculo_historico h LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = h.fk_user WHERE h.fk_veiculo = ".(int)$fk_veiculo." ORDER BY h.date_registro DESC";
    $res = $db->query($sql);
    print '<h4>'.$langs->trans('History').'</h4>';
    if ($res) {
        $num_rows = $db->num_rows($res);
        if ($num_rows == 0) {
            print $langs->trans('NoRecordFound');
        } else {
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre"><th>'.$langs->trans('Date').'</th><th>'.$langs->trans('Quilometragem').'</th><th>'.$langs->trans('Horimetro').'</th><th>'.$langs->trans('User').'</th><th>'.$langs->trans('Observacoes').'</th></tr>';
            
            $chart_labels = array();
            $chart_data = array();
            $history_rows = array();

            while ($objh = $db->fetch_object($res)) {
                $history_rows[] = $objh;
                $chart_labels[] = dol_print_date($objh->date_registro, '%d/%m/%Y');
                $chart_data[] = $objh->quilometragem;
            }

            // Reverse data for chronological order in chart
            $chart_labels = array_reverse($chart_labels);
            $chart_data = array_reverse($chart_data);

            foreach ($history_rows as $objh) {
                print '<tr>';
                print '<td>'.dol_print_date(dol_stringtotime($objh->date_registro), 'dayhour').'</td>';
                print '<td>'.dol_escape_htmltag($objh->quilometragem).'</td>';
                print '<td>'.dol_escape_htmltag($objh->horimetro).'</td>';
                print '<td>'.dol_escape_htmltag($objh->user_login).'</td>';
                print '<td>'.dol_escape_htmltag($objh->observacao).'</td>';
                print '</tr>';
            }
            print '</table>';

            if ($num_rows > 1) {
                print '<div style="margin-top: 20px;">';
                print '<h4>'.$langs->trans('ChartKm').'</h4>';
                print '<canvas id="kmChart" width="400" height="200"></canvas>';
                print '</div>';

                print '<script>';
                print 'document.addEventListener("DOMContentLoaded", function() {';
                print 'var ctx = document.getElementById("kmChart").getContext("2d");';
                print 'var kmChart = new Chart(ctx, {';
                print '    type: "line",';
                print '    data: {';
                print '        labels: '.json_encode($chart_labels).' ,';
                print '        datasets: [{';
                print '            label: "'.$langs->trans("Quilometragem").'",';
                print '            data: '.json_encode($chart_data).' ,';
                print '            backgroundColor: "rgba(54, 162, 235, 0.2)",';
                print '            borderColor: "rgba(54, 162, 235, 1)",';
                print '            borderWidth: 1';
                print '        }]';
                print '    },';
                print '    options: {';
                print '        scales: {';
                print '            y: { beginAtZero: false }';
                print '        }';
                print '    }';
                print '});';
                print '});';
                print '</script>';
            }
        }
    } else {
        print $langs->trans('ErrorRequest');
    }
}

llxFooter();
$db->close();

?>