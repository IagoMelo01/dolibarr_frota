<?php
/*
 * Generic fleet domain object for the Frota module.
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/**
 * Base class with helper logic shared by fleet objects.
 */
abstract class FleetObject extends CommonObject
{
    /**
     * @var string Module identifier
     */
    public $module = 'frota';

    /**
     * Default constructor.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $langs;

        $this->db = $db;

        // Ensure technical id is hidden when asked globally.
        if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
            $this->fields['rowid']['visible'] = 0;
        }

        if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

        foreach ($this->fields as $key => $definition) {
            if (isset($definition['enabled']) && empty($definition['enabled'])) {
                unset($this->fields[$key]);
            }
        }

        if (is_object($langs)) {
            foreach ($this->fields as $key => $definition) {
                if (empty($definition['arrayofkeyval']) || !is_array($definition['arrayofkeyval'])) {
                    continue;
                }

                foreach ($definition['arrayofkeyval'] as $subKey => $value) {
                    $this->fields[$key]['arrayofkeyval'][$subKey] = $langs->trans($value);
                }
            }
        }
    }

    /**
     * Set a status to draft.
     *
     * @param User $user      Current user
     * @param int  $notrigger Disable triggers flag
     *
     * @return int
     */
    public function setDraft(User $user, $notrigger = 0)
    {
        if ($this->status <= static::STATUS_DRAFT) {
            return 0;
        }

        return $this->setStatusCommon($user, static::STATUS_DRAFT, $notrigger, 'FROTA_SET_TO_DRAFT');
    }

    /**
     * Mark the object as canceled.
     *
     * @param User $user      Current user
     * @param int  $notrigger Disable triggers flag
     *
     * @return int
     */
    public function cancel(User $user, $notrigger = 0)
    {
        if ($this->status == static::STATUS_CANCELED) {
            return 0;
        }

        return $this->setStatusCommon($user, static::STATUS_CANCELED, $notrigger, 'FROTA_SET_TO_CANCELLED');
    }

    /**
     * Helper returning tooltip data with common metadata.
     *
     * @param array $params Tooltip params
     *
     * @return array
     */
    public function getTooltipContentArray($params)
    {
        global $langs;

        $datas = array();

        if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
            return array('optimize' => $langs->trans('Show' . ucfirst($this->element)));
        }

        $datas['picto'] = img_picto('', $this->picto) . ' <u>' . $langs->transnoentitiesnoconv(ucfirst($this->element)) . '</u>';

        if (isset($this->status)) {
            $datas['picto'] .= ' ' . $this->getLibStatut(5);
        }

        if (property_exists($this, 'ref') && $this->ref) {
            $datas['ref'] = '<br><b>' . $langs->trans('Ref') . ':</b> ' . dol_escape_htmltag($this->ref);
        }

        if (property_exists($this, 'label') && $this->label) {
            $datas['label'] = '<br><b>' . $langs->trans('Label') . ':</b> ' . dol_escape_htmltag($this->label);
        }

        return $datas;
    }
}
