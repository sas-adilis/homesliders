<?php
/**
 * 2015 Adilis.
 *
 * With the "Exchange Order" module, quickly manage your exchanged products.
 * In one interface, select the product to be returned and the product that will replace it,
 * confirm the exchange, and the module will take care of it all: create the return, generate
 * a credit and a voucher, and create an order corresponding to the exchange by applying
 * the voucher and requesting payment of the balance from your client if necessary.
 *
 *  @author    Adilis <support@adilis.fr>
 *  @copyright 2015 SAS Adilis
 *  @license   http://www.adilis.fr
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminHomeSliderController extends ModuleAdminController
{
    /** @var false|HomeSliders */
    public $module;

    /** @var HomeSlider|null */
    public $object;

    public function __construct()
    {
        $this->table = 'homeslider';
        $this->className = 'HomeSlider';
        $this->identifier = 'id_homeslider';
        $this->list_id = 'homeslider';
        $this->bootstrap = true;
        $this->lang = false;
        $this->_orderBy = 'position';
        $this->_orderWay = 'ASC';
        $this->position_identifier = 'position';
        $this->bulk_actions = [];
        $this->show_form_cancel_button = false;

        parent::__construct();

        $this->fields_list = [
            'id_homeslider' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->l('Name'),
            ],
            'position' => [
                'title' => $this->l('Position'),
                'filter_key' => 'a!position',
                'position' => 'position',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'active' => [
                'title' => $this->l('Active'),
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Create/edit slider'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => $this->l('Name'),
                    'required' => true,
                ],
                [
                    'type' => 'switch',
                    'name' => 'active',
                    'required' => true,
                    'is_bool' => true,
                    'label' => $this->l('Active'),
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
            ],
            'buttons' => [
                'cancel' => [
                    'title' => $this->l('Back to list'),
                    'href' => (Tools::safeOutput(Tools::getValue('back'))) ?: $this->context->link->getAdminLink('AdminHomeSlider'),
                    'icon' => 'process-icon-cancel',
                ],
                'save' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitAdd' . $this->table,
                    'type' => 'submit',
                    'class' => 'btn btn-primary pull-right',
                    'icon' => 'process-icon-save',
                    'value' => 0,
                ],
                'save-and-stay' => [
                    'title' => $this->l('Save & stay'),
                    'name' => 'submitAdd' . $this->table . 'AndWait',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save',
                    'value' => 0,
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type' => 'shop',
                'label' => $this->l('Store association'),
                'name' => 'checkBoxShopAsso',
            ];
        }
    }

    public function renderList()
    {
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('duplicate');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * @throws PrestaShopException
     */
    public function initProcess()
    {
        if (Tools::getIsset('duplicate' . $this->table)) {
            if ($this->access('add')) {
                $this->action = 'duplicate';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } else {
            parent::initProcess();
        }
    }

    public function ajaxProcessUpdatePositions()
    {
        if (!$this->access('edit')) {
            return;
        }

        $way = (int) Tools::getValue('way');
        $idObject = (int) Tools::getValue('id');
        $rawPositions = (array) Tools::getValue($this->table, []);

        $positions = [];
        foreach ($rawPositions as $value) {
            if (!empty($value)) {
                $positions[] = $value;
            }
        }

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);

            if (!isset($pos[2]) || (int) $pos[2] !== $idObject) {
                continue;
            }

            $object = new $this->className((int) $pos[2]);
            if (!Validate::isLoadedObject($object)) {
                echo '{"hasError" : true, "errors" : "This feature (' . $idObject . ') can t be loaded"}';
                break;
            }

            if ($object->updatePosition($way, $position, $idObject)) {
                echo 'ok position ' . $position . ' for feature ' . (int) $pos[1] . '\r\n';
            } else {
                echo '{"hasError" : true, "errors" : "Can not update feature ' . $idObject . ' to position ' . $position . ' "}';
            }

            break;
        }
    }

    public function renderView()
    {
        $id_homeslider = (int) Tools::getValue('id_homeslider');
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminHomeSliderSlide', true, [], ['id_homeslider' => $id_homeslider]));
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new slider'] = [
                'href' => self::$currentIndex . '&addhomeslider&token=' . $this->token,
                'desc' => $this->l('New slider'),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function processDuplicate(): void
    {
        $object = new $this->className((int) Tools::getValue($this->identifier));
        if (Validate::isLoadedObject($object)) {
            $clone = clone $object;
            unset($clone->id);

            $clone->active = 0;
            if (!$clone->add()) {
                $this->errors[] = Tools::displayError('An error occurred while duplicate an object.');

                return;
            }

            $sql = new DbQuery();
            $sql->select('i.id_homeslider_slide');
            $sql->from('homeslider_slide', 'i');
            $sql->orderBy('i.position ASC');
            $sql->where('i.id_homeslider = ' . (int) $object->id);
            $items = Db::getInstance()->executeS($sql) ?: [];

            foreach ($items as $item) {
                $itemObject = new HomeSliderSlide($item['id_homeslider_slide']);
                if (Validate::isLoadedObject($itemObject)) {
                    $cloneObject = clone $itemObject;
                    unset($cloneObject->id);

                    $cloneObject->active = 0;
                    $cloneObject->id_homeslider = $clone->id;

                    if (!$cloneObject->add()) {
                        $this->errors[] = Tools::displayError('An error occurred while duplicate an object.');

                        return;
                    }
                }
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while duplicate an object.');
        }

        if (!count($this->errors) && isset($clone)) {
            $this->redirect_after = self::$currentIndex . '&conf=19&token=' . $this->token;
        }
    }
}
