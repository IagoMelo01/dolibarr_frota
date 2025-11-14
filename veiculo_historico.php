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

print load_fiche_titre($langs->trans('Historico'), '', 'fa-car');

// Instructional text
print '<p>'.$langs->trans("SelectVehicleToSeeHistory").'</p>';

// Vehicle selection form
print '<form method="get" action="'.$_SERVER['PHP_SELF'].'">';
print '<table class="border centpercent">';
print '<tr class="pair"><td class="titlefieldcreate fieldrequired">'.$langs->trans('SelectVehicle').'</td><td>';
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

function renderChart($id, $title, $label, $labelsData, $datasetData, $bgColor, $borderColor)
{
    if (count($datasetData) <= 1) return;

    print '
        <div style="margin-top: 20px; text-align: center;">
            <h4>'.$title.'</h4>

            <div style="display: inline-block; width: 600px; max-width: 100%;">
                <canvas id="'.$id.'" style="width: 100%; height: 350px;"></canvas>
            </div>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById("'.$id.'").getContext("2d");

            new Chart(ctx, {
                type: "line",
                data: {
                    labels: '.json_encode($labelsData).',
                    datasets: [{
                        label: "'.$label.'",
                        data: '.json_encode($datasetData).',
                        backgroundColor: "'.$bgColor.'",
                        borderColor: "'.$borderColor.'",
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: false }
                    }
                }
            });
        });
        </script>
    ';
}



// Handle delete action
$action = GETPOST('action', 'alpha');
if ($action === 'delete') {
    $rowid = GETPOST('rowid', 'int');
    $token = GETPOST('token', 'alpha');

    if (!empty($rowid) && !empty($token) && $token === newToken()) {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."frota_veiculo_historico WHERE rowid = ".(int)$rowid;
        $resql = $db->query($sql);

        if ($resql) {
            setEventMessages($langs->trans('RecordDeleted'), null);
        } else {
            setEventMessages($langs->trans('ErrorRecordNotDeleted').': '.$db->lasterror(), null, 'errors');
        }
    } else {
        setEventMessages($langs->trans('ErrorInvalidTokenOrRowID'), null, 'errors');
    }

    header('Location: '.$_SERVER['PHP_SELF'].'?fk_veiculo='.$fk_veiculo);
    exit;
}

// Show history if vehicle selected
if (!empty($fk_veiculo)) {

    $sql = "SELECT h.*, u.login as user_login FROM ".MAIN_DB_PREFIX."frota_veiculo_historico h LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = h.fk_user WHERE h.fk_veiculo = ".(int)$fk_veiculo." ORDER BY h.date_registro DESC";
    $res = $db->query($sql);
    print '<h4>'.$langs->trans('History').'</h4>';

    if ($res) {
        $num_rows = $db->num_rows($res);
        if ($num_rows == 0) {
            print $langs->trans('NoRecordFound');
        } else {
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre"><th>'.$langs->trans('Date').'</th><th>'.$langs->trans('Quilometragem').'</th><th>'.$langs->trans('Horimetro').'</th><th>'.$langs->trans('User').'</th><th>'.$langs->trans('Observacoes').'</th><th>'.$langs->trans('Ações').'</th></tr>';
            
            $chart_labels_km = array();
            $chart_data_km = array();
            $chart_labels_horimetro = array();
            $chart_data_horimetro = array();
            $history_rows = array();

            while ($objh = $db->fetch_object($res)) {
                $history_rows[] = $objh;
                // Data for KM chart
                $chart_labels_km[] = dol_print_date($objh->date_registro, '%d/%m/%Y');
                $chart_data_km[] = $objh->quilometragem;

                // Data for Horimetro chart
                if ((float)$objh->horimetro > 0) {
                    $chart_labels_horimetro[] = dol_print_date($objh->date_registro, '%d/%m/%Y');
                    $chart_data_horimetro[] = $objh->horimetro;
                }
            }

            // Reverse data for chronological order in charts
            $chart_labels_km = array_reverse($chart_labels_km);
            $chart_data_km = array_reverse($chart_data_km);
            $chart_labels_horimetro = array_reverse($chart_labels_horimetro);
            $chart_data_horimetro = array_reverse($chart_data_horimetro);

            // Render charts before the table
            print '<div style="display: flex; gap: 20px; margin-top: 20px; justify-content: center; align-items: center;">';


                if (count($chart_data_km) > 1) {
                    renderChart(
                        "kmChart",
                        $langs->trans("ChartKm"),
                        $langs->trans("Quilometragem"),
                        $chart_labels_km,
                        $chart_data_km,
                        "rgba(54, 162, 235, 0.2)",
                        "rgba(54, 162, 235, 1)"
                    );
                }

                if (count($chart_data_horimetro) > 1) {
                    renderChart(
                        "horimetroChart",
                        $langs->trans("ChartHorimetro"),
                        $langs->trans("Horimetro"),
                        $chart_labels_horimetro,
                        $chart_data_horimetro,
                        "rgba(255, 99, 132, 0.2)",
                        "rgba(255, 99, 132, 1)"
                    );
                }

            print '</div>';

             print '<div class="tabsAction">';
            print '<a class="butAction" href="veiculo_historico_card.php?fk_veiculo='.$fk_veiculo.'">'.$langs->trans("NewHistory").'</a>';
            print '</div>';

            foreach ($history_rows as $objh) {
                print '<tr>';
                print '<td>' . dol_print_date(dol_stringtotime($objh->date_registro), 'dayhour') . '</td>';
                print '<td>' . dol_escape_htmltag($objh->quilometragem) . '</td>';
                print '<td>' . dol_escape_htmltag($objh->horimetro) . '</td>';
                print '<td>' . dol_escape_htmltag($objh->user_login) . '</td>';
                print '<td>' . dol_escape_htmltag($objh->observacao) . '</td>';

                // CSRF token
                $token = newToken();

                // Ações
                print '<td style="text-align:center; white-space: nowrap;">';

                // Edit button
                print '<a class="butAction" 
                            title="'.$langs->trans("Edit").'" 
                            href="veiculo_historico_card.php?action=edit&rowid='.$objh->rowid.'" 
                            style="margin-right:5px;">
                            <i class="fa fa-edit"></i>
                    </a>';

                // Delete button
                print '<a class="butActionDelete" 
                            title="'.$langs->trans("Delete").'" 
                            href="'.$_SERVER['PHP_SELF'].'?action=delete&rowid='.$objh->rowid.'&token='.$token.'" 
                            onclick="return confirm(\''.$langs->trans("ConfirmDelete").'\');">
                            <i class="fa fa-trash"></i>
                    </a>';

                print '</td>';

                print '</tr>';
            }
            print '</table>';

            
        }
    } else {
        print $langs->trans('ErrorRequest');
    }
}

llxFooter();
$db->close();

?>