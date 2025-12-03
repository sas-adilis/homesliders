<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

include_once __DIR__ . '/classes/HomeSlider.php';
include_once __DIR__ . '/classes/HomeSliderSlide.php';

class HomeSliders extends Module implements WidgetInterface
{
    protected $_html = '';
    protected $default_speed = 5000;
    protected $default_pause_on_hover = 1;
    protected $default_wrap = 1;
    protected $templateFile;
    /**
     * @var string
     */
    public $secure_key;

    public function __construct()
    {
        $this->name = 'homesliders';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Adilis';
        $this->need_instance = 0;
        $this->secure_key = Tools::encrypt($this->name);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Image slider');
        $this->description = $this->l('Add sliding images to your homepage to welcome your visitors in a visual and friendly way.');
        $this->ps_versions_compliancy = ['min' => '1.7.4.0', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:homeslider/views/templates/hook/slider.tpl';
    }

    /**
     * @see Module::install()
     */
    public function install()
    {
        /* Adds Module */
        if (parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayHome')
            && $this->registerHook('actionShopDataDuplication')
        ) {
            $shops = Shop::getContextListShopID();
            $shop_groups_list = [];
            $res = true;

            /* Setup each shop */
            foreach ($shops as $shop_id) {
                $shop_group_id = (int) Shop::getGroupFromShop($shop_id, true);

                if (!in_array($shop_group_id, $shop_groups_list)) {
                    $shop_groups_list[] = $shop_group_id;
                }

                /* Sets up configuration */
                $res &= Configuration::updateValue('HOMESLIDER_SPEED', $this->default_speed, false, $shop_group_id, $shop_id);
                $res &= Configuration::updateValue('HOMESLIDER_PAUSE_ON_HOVER', $this->default_pause_on_hover, false, $shop_group_id, $shop_id);
                $res &= Configuration::updateValue('HOMESLIDER_WRAP', $this->default_wrap, false, $shop_group_id, $shop_id);
            }

            /* Sets up Shop Group configuration */
            if (count($shop_groups_list)) {
                foreach ($shop_groups_list as $shop_group_id) {
                    $res &= Configuration::updateValue('HOMESLIDER_SPEED', $this->default_speed, false, $shop_group_id);
                    $res &= Configuration::updateValue('HOMESLIDER_PAUSE_ON_HOVER', $this->default_pause_on_hover, false, $shop_group_id);
                    $res &= Configuration::updateValue('HOMESLIDER_WRAP', $this->default_wrap, false, $shop_group_id);
                }
            }

            /* Sets up Global configuration */
            $res &= Configuration::updateValue('HOMESLIDER_SPEED', $this->default_speed);
            $res &= Configuration::updateValue('HOMESLIDER_PAUSE_ON_HOVER', $this->default_pause_on_hover);
            $res &= Configuration::updateValue('HOMESLIDER_WRAP', $this->default_wrap);

            /* Creates tables */
            $res &= $this->installTabs();
            $res &= $this->createTables();

            return (bool) $res;
        }

        return false;
    }

    /**
     * @see Module::uninstall()
     */
    public function uninstall()
    {
        /* Deletes Module */
        if (parent::uninstall()) {
            /* Deletes tables */
            $res = $this->deleteTables();

            /* Unsets configuration */
            $res &= Configuration::deleteByName('HOMESLIDER_SPEED');
            $res &= Configuration::deleteByName('HOMESLIDER_PAUSE_ON_HOVER');
            $res &= Configuration::deleteByName('HOMESLIDER_WRAP');

            return (bool) $res;
        }

        return false;
    }

    public function installTabs(): bool
    {
        $tab = new Tab((int) Tab::getIdFromClassName('AdminHomeSlider'));
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Sliders homepage';
        }
        $tab->class_name = 'AdminHomeSlider';
        $tab->module = $this->name;
        $tab->id_parent = 0;

        $rs = $tab->save();

        $tab = new Tab((int) Tab::getIdFromClassName('AdminHomeSliderSlide'));
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Slide';
        }
        $tab->class_name = 'AdminHomeSliderSlide';
        $tab->module = $this->name;
        $tab->id_parent = 0;

        $rs &= $tab->save();

        return $rs;
    }

    /**
     * Creates tables
     */
    protected function createTables()
    {
        /* Sliders */
        $res = Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'homeslider` (
              `id_homeslider` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `position` int(10) unsigned NOT NULL DEFAULT \'0\',
              `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
              PRIMARY KEY (`id_homeslider`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        $res = Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'homeslider_shop` (
              `id_homeslider` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `id_shop` INT UNSIGNED NOT NULL,
              PRIMARY KEY (`id_homeslider`,`id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        /* Slides */
        $res &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'homeslider_slide` (
              `id_homeslider_slide` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `id_shop` int(10) unsigned NOT NULL,
              `name` varchar(255) NOT NULL,
              `video_url` varchar(255) NOT NULL,
              `video_mobile_url` varchar(255) NOT NULL,
              `content_position` int(10) unsigned NOT NULL DEFAULT \'0\',
              `position` int(10) unsigned NOT NULL DEFAULT \'0\',
              `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
              `active_desktop` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
              `active_mobile` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
              `date_from` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
              `date_to` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
              PRIMARY KEY (`id_homeslider_slide`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        /* Slides lang configuration */
        $res &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'homeslider_slide_lang` (
              `id_homeslider_slide` int(10) unsigned NOT NULL,
              `id_lang` int(10) unsigned NOT NULL,
              `title` varchar(255) NOT NULL,
              `legend` varchar(255) NOT NULL,
              `url` varchar(255) NOT NULL,
              `image_url` varchar(255) NOT NULL,
              `image_mobile_url` varchar(255) NOT NULL,
              `image` varchar(255) NOT NULL,
              PRIMARY KEY (`id_homeslider_slide`,`id_lang`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        return $res;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminHomeSlider'));
    }

    public function hookDisplayHeader($params)
    {
        $this->context->controller->registerStylesheet('modules-homeslider', 'modules/' . $this->name . '/css/homeslider.css', ['media' => 'all', 'priority' => 150]);
        $this->context->controller->registerJavascript('modules-responsiveslides', 'modules/' . $this->name . '/js/responsiveslides.min.js', ['position' => 'bottom', 'priority' => 150]);
        $this->context->controller->registerJavascript('modules-homeslider', 'modules/' . $this->name . '/js/homeslider.js', ['position' => 'bottom', 'priority' => 150]);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        return 'Salut';
        if (!$this->isCached($this->templateFile, $this->getCacheId())) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId());
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $slides = $this->getSlides(true);
        if (is_array($slides)) {
            foreach ($slides as &$slide) {
                $slide['sizes'] = @getimagesize(__DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $slide['image']);
                if (isset($slide['sizes'][3]) && $slide['sizes'][3]) {
                    $slide['size'] = $slide['sizes'][3];
                }
            }
        }

        $config = $this->getConfigFieldsValues();

        return [
            'homeslider' => [
                'speed' => $config['HOMESLIDER_SPEED'],
                'pause' => $config['HOMESLIDER_PAUSE_ON_HOVER'] ? 'hover' : '',
                'wrap' => $config['HOMESLIDER_WRAP'] ? 'true' : 'false',
                'slides' => $slides,
            ],
        ];
    }

    protected function updateUrl($link)
    {
        // Empty or anchor link.
        if (empty($link) || 0 === strpos($link, '#')) {
            return $link;
        }

        if (substr($link, 0, 7) !== 'http://' && substr($link, 0, 8) !== 'https://') {
            $link = 'http://' . $link;
        }

        return $link;
    }

    public function clearCache()
    {
        $this->_clearCache($this->templateFile);
    }

    public function hookActionShopDataDuplication($params)
    {
        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'homeslider (id_homeslider_slide, id_shop)
            SELECT id_homeslider_slide, ' . (int) $params['new_id_shop'] . '
            FROM ' . _DB_PREFIX_ . 'homeslider
            WHERE id_shop = ' . (int) $params['old_id_shop']
        );
        $this->clearCache();
    }

    public function headerHTML()
    {
        if (Tools::getValue('controller') != 'AdminModules' && Tools::getValue('configure') != $this->name) {
            return;
        }

        $this->context->controller->addJqueryUI('ui.sortable');
        /* Style & js for fieldset 'slides configuration' */
        $html = '<script type="text/javascript">
            $(function() {
                var $mySlides = $("#slides");
                $mySlides.sortable({
                    opacity: 0.6,
                    cursor: "move",
                    update: function() {
                        var order = $(this).sortable("serialize") + "&action=updateSlidesPosition";
                        $.post("' . $this->context->shop->physical_uri . $this->context->shop->virtual_uri . 'modules/' . $this->name . '/ajax_' . $this->name . '.php?secure_key=' . $this->secure_key . '", order);
                        }
                    });
                $mySlides.hover(function() {
                    $(this).css("cursor","move");
                    },
                    function() {
                    $(this).css("cursor","auto");
                });
            });
        </script>';

        return $html;
    }

    public function getNextPosition()
    {
        $row = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->getRow('
            SELECT MAX(hss.`position`) AS `next_position`
            FROM `' . _DB_PREFIX_ . 'homeslider_slide` hss, `' . _DB_PREFIX_ . 'homeslider` hs
            WHERE hss.`id_homeslider_slide` = hs.`id_homeslider_slide` AND hs.`id_shop` = ' . (int) $this->context->shop->id
        );

        return ++$row['next_position'];
    }

    public function getSlides($frontend = null)
    {
        $this->context = Context::getContext();
        $id_shop = $this->context->shop->id;
        $id_lang = $this->context->language->id;

        $query = new DbQuery();
        $query->select('hs.`id_homeslider_slide` as id_slide, hss.`position`, hss.`active`, hssl.`title`');
        $query->select('hssl.`url`, hssl.`legend`, hssl.`image`, hss.active_desktop, hss.active_mobile, hss.date_from, hss.date_to');
        $query->from('homeslider', 'hs');
        $query->leftJoin('homeslider_slide', 'hss', 'hss.`id_homeslider_slide` = hs.`id_homeslider_slide`');
        $query->leftJoin('homeslider_slide_lang', 'hssl', 'hss.`id_homeslider_slide` = hssl.`id_homeslider_slide`');
        $query->where('hs.`id_shop` = ' . (int) $id_shop);
        $query->where('hssl.`id_lang` = ' . (int) $id_lang);

        if ($frontend) {
            $query->where('hss.`active` = 1');
            $query->where('hss.`date_from` <= NOW()');
            $query->where('hss.`date_to` >= NOW()');

            if ($this->context->getDevice() == Context::DEVICE_COMPUTER) {
                $query->where('hss.`active_desktop` = 1');
            } else {
                $query->where('hss.`active_mobile` = 1');
            }
        }

        $query->orderBy('hss.`position`');

        $slides = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS($query);

        foreach ($slides as &$slide) {
            $slide['image_url'] = $this->context->link->getMediaLink(_MODULE_DIR_ . 'homeslider/images/' . $slide['image']);
            $slide['url'] = $this->updateUrl($slide['url']);
        }

        return $slides;
    }

    public function getAllImagesBySlidesId($id_slides, $active = null, $id_shop = null)
    {
        $this->context = Context::getContext();
        $images = [];

        if (!isset($id_shop)) {
            $id_shop = $this->context->shop->id;
        }

        $results = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS('
            SELECT hssl.`image`, hssl.`id_lang`
            FROM ' . _DB_PREFIX_ . 'homeslider hs
            LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slide hss ON (hs.id_homeslider_slide = hss.id_homeslider_slide)
            LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slide_lang hssl ON (hss.id_homeslider_slide = hssl.id_homeslider_slide)
            WHERE hs.`id_homeslider_slide` = ' . (int) $id_slides . ' AND hs.`id_shop` = ' . (int) $id_shop
            . ($active ? ' AND hss.`active` = 1' : ' ')
        );

        foreach ($results as $result) {
            $images[$result['id_lang']] = $result['image'];
        }

        return $images;
    }

    public function getConfigFieldsValues()
    {
        $id_shop_group = Shop::getContextShopGroupID();
        $id_shop = Shop::getContextShopID();

        return [
            'HOMESLIDER_SPEED' => Tools::getValue('HOMESLIDER_SPEED', Configuration::get('HOMESLIDER_SPEED', null, $id_shop_group, $id_shop)),
            'HOMESLIDER_PAUSE_ON_HOVER' => Tools::getValue('HOMESLIDER_PAUSE_ON_HOVER', Configuration::get('HOMESLIDER_PAUSE_ON_HOVER', null, $id_shop_group, $id_shop)),
            'HOMESLIDER_WRAP' => Tools::getValue('HOMESLIDER_WRAP', Configuration::get('HOMESLIDER_WRAP', null, $id_shop_group, $id_shop)),
        ];
    }

    protected function getCacheId($name = null)
    {
        return parent::getCacheId($name) . '|' . $this->context->getDevice();
    }
}
