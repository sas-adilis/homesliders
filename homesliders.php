<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

include_once __DIR__ . '/classes/HomeSlider.php';
include_once __DIR__ . '/classes/HomeSliderSlide.php';

class HomeSliders extends Module implements WidgetInterface
{
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

        $this->templateFile = 'module:homesliders/views/templates/hook/sliders.tpl';
    }

    /**
     * @see Module::install()
     */
    public function install()
    {
        if (parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayHome')
            && $this->registerHook('actionShopDataDuplication')
        ) {
            $res = $this->installTabs();
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
              `speed` int(10) unsigned NOT NULL DEFAULT \'5000\',
              `pause_on_hover` tinyint(1) unsigned NOT NULL DEFAULT \'1\',
              `loop` tinyint(1) unsigned NOT NULL DEFAULT \'1\',
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
        $this->context->controller->registerStylesheet('modules-homeslider', 'modules/' . $this->name . '/views/css/homesliders.css', ['media' => 'all', 'priority' => 150]);
        $this->context->controller->registerJavascript('modules-responsiveslides', 'modules/' . $this->name . '/views/js/responsiveslides.min.js', ['position' => 'bottom', 'priority' => 150]);
        $this->context->controller->registerJavascript('modules-homeslider', 'modules/' . $this->name . '/views/js/homeslider.js', ['position' => 'bottom', 'priority' => 150]);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        $cache_id = $this->getCacheId($this->templateFile);
        if (isset($configuration['slider'])) {
            $cache_id .= '|' . (int) $configuration['slider'];
        }

        if (!$this->isCached($this->templateFile, $cache_id)) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId());
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getWidgetVariables($hookName = null, array $configuration = []): array
    {
        $id_shop = $this->context->shop->id;
        $id_lang = $this->context->language->id;

        if (isset($configuration['slider'])) {
            $slider = HomeSlider::getSliders($id_shop, $id_lang, (int) $configuration['slider']);
            if (!$slider) {
                return [];
            }
            $sliders = [$slider];
        } else {
            $sliders = HomeSlider::getSliders($id_shop, $id_lang);
            if (empty($sliders)) {
                return [];
            }
        }

        return [
            'sliders' => $sliders,
        ];
    }

    public function clearCache()
    {
        $this->_clearCache($this->templateFile);
    }

    protected function getCacheId($name = null)
    {
        $cache_array = [];
        $cache_array[] = $name !== null ? $name : $this->name;
        if (Configuration::get('PS_SSL_ENABLED')) {
            $cache_array[] = (int) Tools::usingSecureMode();
        }
        if (isset($this->context->shop) && Shop::isFeatureActive()) {
            $cache_array[] = (int) $this->context->shop->id;
        }
        if (isset($this->context->language) && Language::isMultiLanguageActivated()) {
            $cache_array[] = (int) $this->context->language->id;
        }

        return implode('|', $cache_array);
    }
}
