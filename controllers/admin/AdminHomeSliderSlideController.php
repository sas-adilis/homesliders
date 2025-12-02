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

class AdminHomeSliderSlideController extends ModuleAdminController
{
    /** @var false|HomeSliders */
    public $module;

    /** @var HomeSlide|null */
    public $object;
    /**
     * @var int
     */
    private $id_homeslider;

    public function __construct()
    {
        $this->table = 'homeslider_slide';
        $this->className = 'HomeSliderSlide';
        $this->identifier = 'id_homeslider_slide';
        $this->list_id = 'homeslider_slide';
        $this->bootstrap = true;
        $this->lang = true;
        $this->_orderBy = 'position';
        $this->_orderWay = 'ASC';
        $this->position_identifier = 'position';
        $this->show_form_cancel_button = false;

        parent::__construct();

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ],
        ];

        if ((int) Tools::getValue('id_homeslider')) {
            $this->id_homeslider = (int) Tools::getValue('id_homeslider');
        } elseif ((int) Tools::getValue('id_homeslider_slide')) {
            $item = new HomeSliderSlide((int) Tools::getValue('id_homeslider_slide'));
            if (Validate::isLoadedObject($item)) {
                $this->id_homeslider = (int) $item->id_homeslider;
            }
        }

        $this->_select = '1 as media';

        $this->fields_list = [
            'id_homeslider_slide' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'media' => [
                'title' => $this->l('Preview'),
                'type' => 'text',
                'orderby' => false,
                'search' => false,
                'callback' => 'printPreview',
                'align' => 'center',
            ],
            'title' => [
                'title' => $this->l('Title'),
            ],
            'legend' => [
                'title' => $this->l('Caption'),
            ],
            'position' => [
                'title' => $this->l('Position'),
                'filter_key' => 'a!position',
                'position' => 'position',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'date_from' => [
                'title' => $this->l('From'),
                'type' => 'datetime',
            ],
            'date_to' => [
                'title' => $this->l('To'),
                'type' => 'datetime',
            ],
            'active_desktop' => [
                'title' => $this->l('Desktop'),
                'active' => 'active_desktop',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
            'active_mobile' => [
                'title' => $this->l('Mobile'),
                'active' => 'active_mobile',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
            'active' => [
                'title' => $this->l('Active'),
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
        ];

        $this->_where = 'AND a.`id_homeslider` = ' . (int) $this->id_homeslider;
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
    }

    public function printPreview($echo, $tr)
    {
        $baseUrl = $this->context->shop->getBaseUrl();
        if (!empty($tr['video_url'])) {
            return sprintf(
                '<video src="%s" controls="controls"></video>',
                $baseUrl . $tr['video_url']
            );
        } elseif (!empty($tr['image_url'])) {
            return sprintf(
                '<img src="%s" alt="" class="img-thumbnail" />',
                $baseUrl . $tr['image_url']
            );
        }
    }

    public function renderList(): string
    {
        if (!$this->id_homeslider) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHomeSlider'));
        }

        $this->addRowAction('edit');
        $this->addRowAction('duplicate');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function initProcess()
    {
        if (Tools::getIsset('duplicate' . $this->table)) {
            if ($this->access('add')) {
                $this->action = 'duplicate';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } elseif ((isset($_GET['active_desktop' . $this->table]) || isset($_GET['active_desktop'])) && Tools::getValue($this->identifier)) {
            /* Change object status (active, inactive) */
            if ($this->access('edit')) {
                $this->action = 'active_desktop';
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', [], 'Admin.Notifications.Error');
            }
        } elseif ((isset($_GET['active_mobile' . $this->table]) || isset($_GET['active_mobile'])) && Tools::getValue($this->identifier)) {
            /* Change object status (active, inactive) */
            if ($this->access('edit')) {
                $this->action = 'active_mobile';
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', [], 'Admin.Notifications.Error');
            }
        } else {
            parent::initProcess();
        }
    }

    public function renderForm(): string
    {
        if (!Validate::isLoadedObject($this->object)) {
            $this->object->date_from = date('Y-m-d H:i:s');
            $this->object->date_to = date('Y-m-d H:i:s', strtotime('+1 year'));
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Create/edit Slide'),
                'icon' => 'icon-list-ul',
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('Slider'),
                    'name' => 'id_homeslider',
                    'required' => true,
                    'options' => [
                        'query' => $this->getSlidersForSelect(),
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'hint' => $this->l('Choose the group this item belongs to.'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on',  'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
                [
                    'type' => 'separator',
                    'form_group_class' => 'separator',
                    'name' => 'separator_medias',
                    'label' => $this->l('Medias'),
                ],
                [
                    'type' => 'text',
                    'name' => 'image_url',
                    'label' => $this->l('Image'),
                    'class' => 'cms-image-upload',
                    'lang' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'image_mobile_url',
                    'label' => $this->l('Image (mobile)'),
                    'class' => 'cms-image-upload',
                    'lang' => true,
                ],
                [
                    'type' => 'text',
                    'name' => 'video_url',
                    'label' => $this->l('Video'),
                    'class' => 'cms-video-upload',
                    'desc' => $this->l('Video in MP4 format. It is advisable to insert an image as a supplement.'),
                ],
                [
                    'type' => 'separator',
                    'form_group_class' => 'separator',
                    'name' => 'separator_texts',
                    'label' => $this->l('Texts'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Title'),
                    'name' => 'title',
                    'lang' => true,
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Caption'),
                    'name' => 'legend',
                    'lang' => true,
                    'required' => true,
                ],
                [
                    'type' => 'select',
                    'name' => 'content_position',
                    'label' => $this->l('Position'),
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => HomeSliderSlide::POSITION_TOP_LEFT, 'name' => $this->l('Top Left')],
                            ['id' => HomeSliderSlide::POSITION_TOP_CENTER, 'name' => $this->l('Top Center')],
                            ['id' => HomeSliderSlide::POSITION_TOP_RIGHT, 'name' => $this->l('Top Right')],
                            ['id' => HomeSliderSlide::POSITION_CENTER_LEFT, 'name' => $this->l('Center Left')],
                            ['id' => HomeSliderSlide::POSITION_CENTER_CENTER, 'name' => $this->l('Center Center')],
                            ['id' => HomeSliderSlide::POSITION_CENTER_RIGHT, 'name' => $this->l('Center Right')],
                            ['id' => HomeSliderSlide::POSITION_BOTTOM_LEFT, 'name' => $this->l('Bottom Left')],
                            ['id' => HomeSliderSlide::POSITION_BOTTOM_CENTER, 'name' => $this->l('Bottom Center')],
                            ['id' => HomeSliderSlide::POSITION_BOTTOM_RIGHT, 'name' => $this->l('Bottom Right')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'separator',
                    'form_group_class' => 'separator',
                    'name' => 'separator_programming',
                    'label' => $this->l('Programming'),
                ],
                [
                    'type' => 'datetime',
                    'name' => 'date_from',
                    'label' => $this->l('From'),
                    'required' => true,
                ],
                [
                    'type' => 'datetime',
                    'name' => 'date_to',
                    'label' => $this->l('To'),
                    'required' => true,
                ],
                [
                    'type' => 'separator',
                    'form_group_class' => 'separator',
                    'name' => 'separator_responsive',
                    'label' => $this->l('Responsive settings'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Display on desktops'),
                    'name' => 'active_desktop',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_desktop_on',  'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_desktop_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Display on mobiles'),
                    'name' => 'active_mobile',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_mobile_on',  'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_mobile_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                ],
            ],
            'buttons' => [
                'cancel' => [
                    'title' => $this->l('Back to list'),
                    'href' => (Tools::safeOutput(Tools::getValue('back'))) ?: $this->context->link->getAdminLink('AdminHomeSliderSlide', true, [], ['id_homeslider' => $this->id_homeslider]),
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
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save',
                    'value' => 0,
                ],
            ],
        ];

        return parent::renderForm();
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

    private function getSlidersForSelect()
    {
        $sql = new DbQuery();
        $sql->select('s.id_homeslider as id, s.name');
        $sql->from('homeslider', 's');
        $sql->orderBy('s.position ASC');
        $rows = Db::getInstance()->executeS($sql) ?: [];
        if (!$rows) {
            // Fallback entry to avoid empty select
            return [['id' => 0, 'name' => $this->l('— Create a group before creating an item —')]];
        }

        return $rows;
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_slide'] = [
                'href' => $this->context->link->getAdminLink('AdminHomeSliderSlide', true, [], ['addhomeslider_slide' => 1, 'id_homeslider' => $this->id_homeslider]),
                'desc' => $this->trans('New slide', [], 'Admin.Catalog.Help'),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initToolbarTitle()
    {
        if (empty($this->display) && $this->id_homeslider) {
            $group = new HomeSlider($this->id_homeslider);
            if (Validate::isLoadedObject($group)) {
                $this->toolbar_title = $group->name;
            }
        }
    }

    public function initToolbar()
    {
        if (empty($this->display)) {
            $this->toolbar_btn['back'] = [
                'href' => $this->context->link->getAdminLink('AdminHomeSlider'),
                'desc' => $this->l('Back to sliders list'),
            ];
        }

        parent::initToolbar();
    }

    public function processStatus()
    {
        parent::processStatus();
        if (!count($this->errors)) {
            $object = $this->loadObject();
            $this->redirect_after .= '&id_homeslider=' . $this->id_homeslider;
        }
    }

    public function processDelete()
    {
        parent::processDelete();
        if (!count($this->errors)) {
            $this->redirect_after .= '&id_homeslider=' . $this->id_homeslider;
        }
    }

    public function processDuplicate()
    {
        $object = new $this->className((int) Tools::getValue($this->identifier));
        if (Validate::isLoadedObject($object)) {
            $clone = clone $object;
            $clone->active = 0;
            $clone->position = HomeSliderSlide::getHigherPosition($object->id_homeslider) + 1;
            unset($clone->id);
            if (!$clone->add()) {
                $this->errors[] = Tools::displayError('An error occurred while duplicate an object.');
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while duplicate an object.');
        }

        if (!count($this->errors) && isset($clone)) {
            $this->redirect_after = self::$currentIndex . '&conf=19&token=' . $this->token . '&id_homeslider=' . (int) $clone->id_homeslider;
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processActiveDesktop(): HomeSliderSlide
    {
        /** @var HomeSliderSlide $object */
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            $object->active_desktop = !$object->active_desktop;
            if ($object->update()) {
                $this->redirect_after = self::$currentIndex . '&token=' . $this->token;
                $page = (int) Tools::getValue('page');
                $page = $page > 1 ? '&submitFilter' . $this->table . '=' . $page : '';
                $this->redirect_after .= '&conf=5' . $page;
                $this->redirect_after .= '&id_homeslider=' . $object->id_homeslider;
            } else {
                $this->errors[] = $this->trans('An error occurred while updating the status.', [], 'Admin.Notifications.Error');
            }
        } else {
            $this->errors[] = $this->trans('An error occurred while updating the status for an object.', [], 'Admin.Notifications.Error')
                . ' <b>' . $this->table . '</b> '
                . $this->trans('(cannot load object)', [], 'Admin.Notifications.Error');
        }

        return $object;
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processActiveMobile(): HomeSliderSlide
    {
        /** @var HomeSliderSlide $object */
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            $object->active_mobile = !$object->active_mobile;
            if ($object->update()) {
                $this->redirect_after = self::$currentIndex . '&token=' . $this->token;
                $page = (int) Tools::getValue('page');
                $page = $page > 1 ? '&submitFilter' . $this->table . '=' . $page : '';
                $this->redirect_after .= '&conf=5' . $page;
                $this->redirect_after .= '&id_homeslider=' . $object->id_homeslider;
            } else {
                $this->errors[] = $this->trans('An error occurred while updating the status.', [], 'Admin.Notifications.Error');
            }
        } else {
            $this->errors[] = $this->trans('An error occurred while updating the status for an object.', [], 'Admin.Notifications.Error')
                . ' <b>' . $this->table . '</b> '
                . $this->trans('(cannot load object)', [], 'Admin.Notifications.Error');
        }

        return $object;
    }
}
