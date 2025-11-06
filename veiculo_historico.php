<?php
/*
 * 
 * Page to add vehicle history entries using Historico class (llx_frota_veiculo_historico)
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
$action = GETPOST('action', 'alpha');
$fk_veiculo = GETPOST('fk_veiculo', 'int');
$data = GETPOST('data', 'alpha');
$km = GETPOST('km', 'alpha');
$horimetro = GETPOST('horimetro', 'alpha');
$observacoes = GETPOST('observacoes', 'text');

// Security and permissions
if (!isModEnabled('frota')) accessforbidden();
// Allow only users with write right or admin
if (empty($user->rights->frota->write) && empty($user->admin)) accessforbidden();

$errors = array();

// Create
if ($action === 'create') {
    // CSRF token
    if (empty($_POST['token']) || $_POST['token'] !== newToken()) {
        $errors[] = $langs->trans('ErrorBadFormSent');
    }
    if (empty($fk_veiculo)) $errors[] = $langs->trans('SelectVehicle');
    if ($data === '') $data = date('Y-m-d');
    if ($km === '' && $horimetro === '') $errors[] = $langs->trans('ProvideKmOrHour');

    if (empty($errors)) {
        $obj = new Historico($db);
        // set fields expected by createCommon
        $obj->fk_veiculo = $fk_veiculo;
        // Historico->data is date type, ensure format YYYY-MM-DD
        $ts = strtotime($data);
        if ($ts === false) $obj->date_registro = date('Y-m-d'); else $obj->date_registro = date('Y-m-d', $ts);
        $obj->quilometragem = $km !== '' ? (float)$km : 0;
        $obj->horimetro = $horimetro !== '' ? (float)$horimetro : 0;
        $obj->observacao = $observacoes !== '' ? $observacoes : null;

        $rescreate = $obj->create($user);
        if ($rescreate > 0) {
            setEventMessages($langs->trans('RecordSaved'), null);
            header('Location: '.$_SERVER['PHP_SELF'].'?fk_veiculo='.$fk_veiculo);
            exit;
        } else {
            $errors[] = $langs->trans('ErrorOnSave');
        }
    }
}

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
llxHeader('', $langs->trans('Historico'));

print load_fiche_titre($langs->trans('Historico'), '', 'object_'.$obj->picto);

foreach ($errors as $e) print '<div class="error">'.dol_escape_htmltag($e)."</div>";

// Form
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="create">';

print '<table class="border centpercent">';
print '<tr class="pair"><td class="titlefieldcreate fieldrequired">'.$langs->trans('Vehicle').'</td><td>';
if (empty($vehicles)) {
    print '<div class="warning">'.$langs->trans('NoVehicleRegistered').'</div>';
} else {
    print '<select class="flat minwidth300" id="fk_veiculo" name="fk_veiculo">';
    print '<option value="">&nbsp;</option>';
    foreach ($vehicles as $v) {
        $sel = ($fk_veiculo == $v['rowid']) ? ' selected' : '';
        print '<option value="'.(int)$v['rowid'].'"'.$sel.'>'.dol_escape_htmltag($v['label']).'</option>';
    }
    print '</select>';
}
print '</td></tr>';

print '<tr><td>'.$langs->trans('Data').'</td><td><input type="date" name="data" value="'.dol_escape_htmltag($data ? $data : date('Y-m-d')).'" /></td></tr>';
print '<tr><td>'.$langs->trans('Quilometragem').'</td><td><input type="text" name="km" value="'.dol_escape_htmltag($km).'" /></td></tr>';
print '<tr><td>'.$langs->trans('Horimetro').'</td><td><input type="text" name="horimetro" value="'.dol_escape_htmltag($horimetro).'" /></td></tr>';
print '<tr><td>'.$langs->trans('Observacoes').'</td><td><textarea name="observacoes" rows="3" cols="80">'.dol_escape_htmltag($observacoes).'</textarea></td></tr>';
print '<tr><td></td><td><input type="submit" class="button" value="'.$langs->trans('Save').'" /></td></tr>';
print '</table>';
print '</form>';

// Show history if vehicle selected
if (!empty($fk_veiculo)) {
    $sql = "SELECT h.*, u.login as user_login FROM ".MAIN_DB_PREFIX."frota_veiculo_historico h LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = h.fk_user WHERE h.fk_veiculo = ".(int)$fk_veiculo." ORDER BY h.date_registro DESC";
    $res = $db->query($sql);
    print '<h4>'.$langs->trans('History').'</h4>';
    if ($res) {
        if ($db->num_rows($res) == 0) {
            print $langs->trans('NoRecordFound');
        } else {
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre"><th>'.$langs->trans('Date').'</th><th>'.$langs->trans('Quilometragem').'</th><th>'.$langs->trans('Horimetro').'</th><th>'.$langs->trans('User').'</th><th>'.$langs->trans('Observacoes').'</th></tr>';
            while ($objh = $db->fetch_object($res)) {
                print '<tr>';
                print '<td>'.dol_print_date(dol_stringtotime($objh->date_registro), 'dayhour').'</td>';
                print '<td>'.dol_escape_htmltag($objh->quilometragem).'</td>';
                print '<td>'.dol_escape_htmltag($objh->horimetro).'</td>';
                print '<td>'.dol_escape_htmltag($objh->user_login).'</td>';
                print '<td>'.dol_escape_htmltag($objh->observacao).'</td>';
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
