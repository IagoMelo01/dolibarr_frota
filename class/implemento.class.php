<?php
/*
 * Implement (equipment) business object.
 */

require_once __DIR__ . '/fleetobject.class.php';

/**
 * Class representing an implement attached to the fleet.
 */
class Implemento extends FleetObject
{
    /** @var string */
    public $element = 'implemento';

    /** @var string */
    public $table_element = 'frota_implemento';

    /** @var string */
    public $picto = 'fa-cogs';

    /** @var int */
    public $ismultientitymanaged = 1;

    /** @var int */
    public $isextrafieldmanaged = 1;

    public const STATUS_DRAFT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_OUT_OF_SERVICE = 2;
    public const STATUS_CANCELED = 9;

    /** @var array */
    public $fields = array(
        'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1),
        'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 5, 'notnull' => 1, 'visible' => 0, 'default' => '1', 'index' => 1),
        'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 5, 'default' => '(IMP)', 'index' => 1, 'searchall' => 1, 'validate' => '1'),
        'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300'),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array(
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Enabled',
            self::STATUS_OUT_OF_SERVICE => 'Disabled',
            self::STATUS_CANCELED => 'Canceled',
        )),
        'marca' => array('type' => 'varchar(255)', 'label' => 'Manufacturer', 'enabled' => '1', 'position' => 60, 'notnull' => 0, 'visible' => 1, 'searchall' => 1),
        'tipo' => array('type' => 'varchar(255)', 'label' => 'Type', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 1, 'searchall' => 1),
        'ano_fab' => array('type' => 'varchar(10)', 'label' => 'Year', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 1),
        'num_identificacao' => array('type' => 'varchar(64)', 'label' => 'Serial', 'enabled' => '1', 'position' => 90, 'notnull' => 0, 'visible' => 1, 'searchall' => 1),
        'capacidade' => array('type' => 'double(12,2)', 'label' => 'Capacity', 'enabled' => '1', 'position' => 100, 'notnull' => 0, 'visible' => -1, 'isameasure' => '1'),
        'largura_trab' => array('type' => 'double(12,2)', 'label' => 'WorkingWidth', 'enabled' => '1', 'position' => 110, 'notnull' => 0, 'visible' => -1, 'isameasure' => '1'),
        'horas_op' => array('type' => 'double(12,2)', 'label' => 'CurrentHours', 'enabled' => '1', 'position' => 120, 'notnull' => 0, 'visible' => 1, 'isameasure' => '1'),
        'ultima_manutencao' => array('type' => 'date', 'label' => 'DateLastService', 'enabled' => '1', 'position' => 130, 'notnull' => 0, 'visible' => 1),
        'documento' => array('type' => 'varchar(128)', 'label' => 'Document', 'enabled' => '1', 'position' => 140, 'notnull' => 0, 'visible' => -1),
        'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php:1', 'label' => 'ThirdParty', 'picto' => 'company', 'enabled' => '$conf->societe->enabled', 'position' => 200, 'notnull' => -1, 'visible' => -1, 'index' => 1),
        'fk_project' => array('type' => 'integer:Project:projet/class/project.class.php:1', 'label' => 'Project', 'picto' => 'project', 'enabled' => '$conf->project->enabled', 'position' => 210, 'notnull' => -1, 'visible' => -1, 'index' => 1),
        'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 220, 'notnull' => 0, 'visible' => 3),
        'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => '1', 'position' => 230, 'notnull' => 0, 'visible' => 0),
        'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => '1', 'position' => 240, 'notnull' => 0, 'visible' => 0),
        'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => '1', 'position' => 600, 'notnull' => 0, 'visible' => 0),
        'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'position' => 1000, 'notnull' => -1, 'visible' => -2),
        'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => '1', 'position' => 1010, 'notnull' => -1, 'visible' => 0),
        'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 1020, 'notnull' => 1, 'visible' => -2),
        'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 1030, 'notnull' => 0, 'visible' => -2),
        'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 1040, 'notnull' => 1, 'visible' => -2),
        'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 1050, 'notnull' => -1, 'visible' => -2),
    );

    /** @var float */
    public $horas_op;

    /**
     * Update the hour meter of the implement.
     *
     * @param float $hours Hours to record
     * @param User  $user  Current user
     *
     * @return int
     */
    public function registerUsage($hours, User $user)
    {
        $this->horas_op = round(max($this->horas_op, (float) $hours), 2);
        $this->fk_user_modif = $user->id;

        return $this->updateCommon($user, true);
    }
}
