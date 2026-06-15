<?php

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Small CommonObject base shared by the Frota MVP objects.
 */
abstract class FrotaObject extends CommonObject
{
    public $module = 'frota';
    public $ismultientitymanaged = 1;
    public $isextrafieldmanaged = 0;

    public $rowid;
    public $entity;
    public $ref;
    public $fk_user_creat;
    public $fk_user_modif;
    public $datec;
    public $tms;

    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    public function create(User $user, $notrigger = false)
    {
        global $conf;

        $this->entity = empty($this->entity) ? (int) $conf->entity : (int) $this->entity;
        $this->datec = empty($this->datec) ? $this->db->idate(dol_now()) : $this->datec;

        return $this->createCommon($user, $notrigger);
    }

    public function fetch($id, $ref = null, $noextrafields = 1)
    {
        return $this->fetchCommon($id, $ref, '', $noextrafields);
    }

    public function update(User $user, $notrigger = false)
    {
        return $this->updateCommon($user, $notrigger);
    }

    public function delete(User $user, $notrigger = false)
    {
        return $this->deleteCommon($user, $notrigger);
    }

    protected function generateRef($prefix)
    {
        if (empty($this->ref)) {
            $this->ref = $prefix.'-'.dol_print_date(dol_now(), '%Y%m%d%H%M%S').'-'.strtoupper(substr(md5(uniqid('', true)), 0, 5));
        }
    }

    public function getNomUrl($withpicto = 0, $option = '')
    {
        $url = dol_buildpath('/frota/'.$this->element.'_card.php', 1).'?id='.(int) $this->id;
        $label = dol_escape_htmltag((string) $this->ref);
        if ($option === 'nolink') {
            return $label;
        }

        return '<a href="'.$url.'">'.($withpicto ? img_picto('', $this->picto, 'class="pictofixedwidth"').' ' : '').$label.'</a>';
    }

    protected function setObjectError($source, $fallback)
    {
        $this->error = !empty($source->error) ? $source->error : $fallback;
        $this->errors = !empty($source->errors) ? $source->errors : array($this->error);
    }

    public static function auditFields()
    {
        return array(
            'rowid' => array('type'=>'integer', 'label'=>'ID', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>1),
            'entity' => array('type'=>'integer', 'label'=>'Entity', 'position'=>2, 'notnull'=>1, 'visible'=>0, 'default'=>1),
            'fk_user_creat' => array('type'=>'integer', 'label'=>'UserAuthor', 'position'=>900, 'notnull'=>1, 'visible'=>0),
            'fk_user_modif' => array('type'=>'integer', 'label'=>'UserModif', 'position'=>910, 'notnull'=>0, 'visible'=>0),
            'datec' => array('type'=>'datetime', 'label'=>'DateCreation', 'position'=>920, 'notnull'=>1, 'visible'=>0),
            'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'position'=>930, 'notnull'=>0, 'visible'=>0),
        );
    }
}

