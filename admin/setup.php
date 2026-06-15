<?php

require __DIR__.'/../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/frotaseeder.class.php';

$langs->load('frota@frota');
if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'seed') {
    $seeder = new FrotaSeeder($db);
    if ($seeder->run($user) > 0) {
        setEventMessages($langs->trans('FrotaSeedCompleted'), null, 'mesgs');
    } else {
        setEventMessages($seeder->error, $seeder->errors, 'errors');
    }
}

llxHeader('', $langs->trans('ModuleSetup'));
print load_fiche_titre($langs->trans('ModuleFrotaName').' - '.$langs->trans('ModuleSetup'), '', 'fa-tractor');
print '<p>'.$langs->trans('FrotaDescription').'</p>';
print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="seed">';
print '<button class="button" type="submit">'.$langs->trans('FrotaRunSeed').'</button></form>';
llxFooter();
$db->close();
