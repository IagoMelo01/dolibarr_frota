<?php

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modFrota extends DolibarrModules
{
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;
        $this->numero = 500020;
        $this->rights_class = 'frota';
        $this->family = 'Farmevo';
        $this->module_position = '20';
        $this->name = 'Frota';
        $this->description = 'FrotaDescription';
        $this->editor_name = 'Farmevo';
        $this->editor_url = 'https://farmevo.com.br';
        $this->version = '2.1.0';
        $this->const_name = 'MAIN_MODULE_FROTA';
        $this->picto = 'fa-tractor';
        $this->module_parts = array('triggers'=>0, 'css'=>array('/frota/css/frota.css'), 'js'=>array(), 'hooks'=>array());
        $this->dirs = array('/frota/temp');
        $this->config_page_url = array('setup.php@frota');
        $this->hidden = false;
        $this->depends = array('always'=>array('modProduct', 'modStock', 'modCategorie', 'modProjet'));
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array('frota@frota');
        $this->phpmin = array(8, 1);
        $this->need_dolibarr_version = array(23, 0);
        $this->const = array();
        $this->tabs = array();
        $this->dictionaries = array();
        $this->boxes = array();
        $this->cronjobs = array();

        if (!isModEnabled('frota')) {
            $conf->frota = new stdClass();
            $conf->frota->enabled = 0;
        }

        $this->rights = array();
        $r = 0;
        $objects = array(
            'veiculo'=>'FrotaVehicle',
            'implemento'=>'FrotaImplement',
            'abastecimento'=>'FrotaFueling',
            'manutencao'=>'FrotaMaintenance',
            'uso'=>'FrotaUsage',
        );
        foreach ($objects as $code => $label) {
            foreach (array('read'=>'Read', 'write'=>'Write', 'delete'=>'Delete') as $right => $action) {
                $this->rights[$r][0] = $this->numero.sprintf('%02d', $r + 1);
                $this->rights[$r][1] = $action.' '.$label;
                $this->rights[$r][3] = $right === 'read' ? 1 : 0;
                $this->rights[$r][4] = $code;
                $this->rights[$r][5] = $right;
                $r++;
            }
        }
        foreach (array('abastecimento'=>'FrotaConfirmFueling', 'manutencao'=>'FrotaCompleteMaintenance') as $code => $label) {
            $this->rights[$r][0] = $this->numero.sprintf('%02d', $r + 1);
            $this->rights[$r][1] = $label;
            $this->rights[$r][3] = 0;
            $this->rights[$r][4] = $code;
            $this->rights[$r][5] = 'confirm';
            $r++;
        }

        $this->menu = array();
        $r = 0;
        $this->menu[$r++] = array(
            'fk_menu'=>'', 'type'=>'top', 'titre'=>'ModuleFrotaName', 'prefix'=>img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle"'),
            'mainmenu'=>'frota', 'leftmenu'=>'', 'url'=>'/frota/frotaindex.php', 'langs'=>'frota@frota',
            'position'=>1000, 'enabled'=>'isModEnabled("frota")', 'perms'=>'$user->hasRight("frota", "veiculo", "read")', 'target'=>'', 'user'=>2,
        );

        foreach ($objects as $code => $label) {
            $this->menu[$r++] = array(
                'fk_menu'=>'fk_mainmenu=frota', 'type'=>'left', 'titre'=>$label.'s', 'mainmenu'=>'frota', 'leftmenu'=>'frota_'.$code,
                'url'=>'/frota/'.$code.'_list.php', 'langs'=>'frota@frota', 'position'=>1000 + $r,
                'enabled'=>'isModEnabled("frota")', 'perms'=>'$user->hasRight("frota", "'.$code.'", "read")', 'target'=>'', 'user'=>2,
            );
            $this->menu[$r++] = array(
                'fk_menu'=>'fk_mainmenu=frota,fk_leftmenu=frota_'.$code, 'type'=>'left', 'titre'=>'New', 'mainmenu'=>'frota', 'leftmenu'=>'frota_'.$code.'_new',
                'url'=>'/frota/'.$code.'_card.php?action=create', 'langs'=>'frota@frota', 'position'=>1000 + $r,
                'enabled'=>'isModEnabled("frota")', 'perms'=>'$user->hasRight("frota", "'.$code.'", "write")', 'target'=>'', 'user'=>2,
            );
            if ($code === 'manutencao') {
                $this->menu[$r++] = array(
                    'fk_menu'=>'fk_mainmenu=frota,fk_leftmenu=frota_manutencao', 'type'=>'left', 'titre'=>'FrotaMaintenanceAgenda', 'mainmenu'=>'frota', 'leftmenu'=>'frota_manutencao_agenda',
                    'url'=>'/frota/manutencao_agenda.php', 'langs'=>'frota@frota', 'position'=>1000 + $r,
                    'enabled'=>'isModEnabled("frota")', 'perms'=>'$user->hasRight("frota", "manutencao", "read")', 'target'=>'', 'user'=>2,
                );
            }
        }
    }

    public function init($options = '')
    {
        global $user;

        $result = $this->_load_tables('/frota/sql/');
        if ($result < 0) {
            return -1;
        }
        require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/frotamigration.class.php';
        $migration = new FrotaMigration($this->db);
        if ($migration->run() < 0) {
            $this->error = $migration->error;
            return -1;
        }
        $this->remove($options);
        $result = $this->_init(array(), $options);
        if ($result <= 0) {
            return $result;
        }

        require_once DOL_DOCUMENT_ROOT.'/custom/frota/class/frotaseeder.class.php';
        $seeder = new FrotaSeeder($this->db);
        if ($seeder->run($user) < 0) {
            $this->error = $seeder->error;
            $this->errors = $seeder->errors;
            return -1;
        }
        return 1;
    }

    public function remove($options = '')
    {
        return $this->_remove(array(), $options);
    }
}
