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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$langs->load('frota@frota');

// Parameters
$fk_veiculo = GETPOST('fk_veiculo', 'int');
$action = GETPOST('action', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortfield) $sortfield = "h.date_registro";
if (!$sortorder) $sortorder = "DESC";

// Security and permissions
if (!isModEnabled('frota')) accessforbidden();
if (empty($user->rights->frota->read) && empty($user->admin)) accessforbidden();

// Handle delete action
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

$veiculo_options = array();

if (is_array($all_veiculos)) {
    foreach ($all_veiculos as $v) {
        $veiculo_options[$v->id] = $v->label;
    }
}

// Fetch vehicles for select
$veiculo_static = new Veiculo($db);
$all_veiculos = $veiculo_static->fetchAll('ASC', 'label');

// Page header
llxHeader('', $langs->trans('Historico'), '', '', 0, 0, array(), dol_buildpath('/frota/js/chart.min.js', 1));

$form = new Form($db);

print load_fiche_titre($langs->trans('Historico'), '', 'fa-history');

// Vehicle selection form
print '<div class="div-table-responsive-no-min">';
print '<form method="get" action="'.$_SERVER['PHP_SELF'].'">';
print '<table class="noborder centpercent">';
print '<tbody>';
print '<tr class="pair">';
print '<td class="titlefieldcreate">'.$langs->trans('SelectVehicle').'</td>';
print '<td>';

if (empty($all_veiculos)) {

    print '<div class="warning">'.$langs->trans('NoVehicleRegistered').'</div>';

} else {

    // Build options array
    $veiculo_options = array();
    foreach ($all_veiculos as $v) {
        $veiculo_options[$v->id] = $v->label;
    }

    // Dolibarr combobox
    print $form->selectarray(
        'fk_veiculo',           // field name
        $veiculo_options,       // options
        $fk_veiculo,            // selected value
        1,                      // show empty option
        0,
        0,
        'onchange="this.form.submit()"',
        0,
        0,
        0,
        'minwidth100'
    );
}

print '</td>';
print '</tr>';
print '</tbody>';
print '</table>';
print '</form>';
print '</div><br>';


function renderChart($id, $title, $label, $labelsData, $datasetData, $bgColor, $borderColor)
{
    if (count($datasetData) <= 1) return;

    $out = '
        <div style="text-align: center; flex: 1; min-width: 300px;">
            <h4>'.$title.'</h4>
            <div style="position: relative; height:300px; width:100%">
                <canvas id="'.$id.'"></canvas>
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
                        borderWidth: 2,
                        tension: 0.1
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
    return $out;
}


// Show history if vehicle selected
if (!empty($fk_veiculo)) {

    // --- Charts ---
    $sql_chart = "SELECT date_registro, quilometragem, horimetro FROM ".MAIN_DB_PREFIX."frota_veiculo_historico WHERE fk_veiculo = ".(int)$fk_veiculo." ORDER BY date_registro ASC";
    $res_chart = $db->query($sql_chart);
    if ($res_chart) {
        $chart_labels_km = array();
        $chart_data_km = array();
        $chart_labels_horimetro = array();
        $chart_data_horimetro = array();

        while ($obj = $db->fetch_object($res_chart)) {
            // Data for KM chart
            $chart_labels_km[] = dol_print_date($obj->date_registro, '%d/%m/%Y');
            $chart_data_km[] = $obj->quilometragem;

            // Data for Horimetro chart
            if ((float)$obj->horimetro > 0) {
                $chart_labels_horimetro[] = dol_print_date($obj->date_registro, '%d/%m/%Y');
                $chart_data_horimetro[] = $obj->horimetro;
            }
        }

        $charts_html = '';
        $charts_html .= renderChart("kmChart", $langs->trans("ChartKm"), $langs->trans("Quilometragem"), $chart_labels_km, $chart_data_km, "rgba(54, 162, 235, 0.2)", "rgba(54, 162, 235, 1)");
        $charts_html .= renderChart("horimetroChart", $langs->trans("ChartHorimetro"), $langs->trans("Horimetro"), $chart_labels_horimetro, $chart_data_horimetro, "rgba(255, 99, 132, 0.2)", "rgba(255, 99, 132, 1)");

        if (!empty(trim($charts_html))) {
            print '<div class="box" style="margin-bottom: 20px;"><div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; align-items: center;">';
            print $charts_html;
            print '</div></div>';
        }
    }


    // --- History List ---
    $sql = " FROM ".MAIN_DB_PREFIX."frota_veiculo_historico h LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = h.fk_user WHERE h.fk_veiculo = ".(int)$fk_veiculo;
    
    $sql_count = "SELECT COUNT(*) as total".$sql;
    $res_count = $db->query($sql_count);
    $nbtotalofrecords = $db->fetch_object($res_count)->total;

    $sql_list = "SELECT h.*, u.login as user_login".$sql.$db->order($sortfield, $sortorder).$db->plimit($limit, $offset);
    $res = $db->query($sql_list);

    if ($res) {
        $num_rows = $db->num_rows($res);
        
        $param = '&fk_veiculo='.$fk_veiculo;
        $newcardbutton = dolGetButtonTitle($langs->trans('NewHistory'), '', 'fa fa-plus-circle', 'veiculo_historico_card.php?fk_veiculo='.$fk_veiculo);

        print_barre_liste($langs->trans('History'), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $newcardbutton, $num_rows, $nbtotalofrecords, 'title_generic.png');

        print '<div class="div-table-responsive">';
        print '<table class="liste centpercent">';

        
        // Table Header
        print '<tr class="liste_titre">';

        print getTitleFieldOfList(
            $langs->trans('Date'),
            0,
            $_SERVER['PHP_SELF'],
            'h.date_registro',
            '',
            $param,
            'class="center"',
            $sortfield,
            $sortorder
        );

        print getTitleFieldOfList(
            $langs->trans('Quilometragem'),
            0,
            $_SERVER['PHP_SELF'],
            'h.quilometragem',
            '',
            $param,
            'class="right"',
            $sortfield,
            $sortorder
        );

        print getTitleFieldOfList(
            $langs->trans('Horimetro'),
            0,
            $_SERVER['PHP_SELF'],
            'h.horimetro',
            '',
            $param,
            'class="right"',
            $sortfield,
            $sortorder
        );

        print getTitleFieldOfList(
            $langs->trans('User'),
            0,
            $_SERVER['PHP_SELF'],
            'u.login',
            '',
            $param,
            '',
            $sortfield,
            $sortorder
        );

        print getTitleFieldOfList(
            $langs->trans('Observacoes'),
            0,
            $_SERVER['PHP_SELF'],
            'h.observacao',
            '',
            $param,
            '',
            $sortfield,
            $sortorder
        );

        // Actions (sem ordenação)
        print '<th class="center nowrap">'.$langs->trans('Actions').'</th>';

        print '</tr>';


        if ($num_rows > 0) {
            while ($objh = $db->fetch_object($res)) {

                print '<tr class="oddeven">';

                print '<td class="center">';
                print dol_print_date($db->jdate($objh->date_registro), 'dayhour');
                print '</td>';

                print '<td class="right">';
                print price($objh->quilometragem);
                print '</td>';

                print '<td class="right">';
                print price($objh->horimetro);
                print '</td>';

                print '<td>';
                print dol_escape_htmltag($objh->user_login);
                print '</td>';

                print '<td>';
                print dol_escape_htmltag($objh->observacao);
                print '</td>';

                // Actions
                print '<td class="center nowrap">';

                // Edit
                print dolGetButtonTitle(
                    $langs->trans('Modify'),
                    '',
                    'fa fa-edit',
                    'veiculo_historico_card.php?action=edit&rowid='.$objh->rowid
                );

                // Delete
                $deleteUrl = $_SERVER['PHP_SELF']
                    .'?action=delete'
                    .'&rowid='.$objh->rowid
                    .'&fk_veiculo='.$fk_veiculo
                    .'&token='.newToken();

                print dolGetButtonTitle(
                    $langs->trans('Delete'),
                    '',
                    'fa fa-trash',
                    $deleteUrl,
                    '',
                    1,
                    array(
                        'confirm' => $langs->trans('ConfirmDelete')
                    )
                );

                print '</td>';
            }

        } else {
            if ($num_rows == 0) {
                print '<tr>';
                print '<td class="center" colspan="6">';
                print $langs->trans('NoRecordFound');
                print '</td>';
                print '</tr>';
            }

        }
        
        print '</table>';
        print '</div>';

    } else {
        dol_print_error($db);
    }
}

llxFooter();
$db->close();

?>