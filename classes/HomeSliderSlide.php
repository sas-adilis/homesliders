<?php
/**
 * 2007-2020 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
class HomeSliderSlide extends ObjectModel
{
    public const POSITION_TOP_LEFT = 0;
    public const POSITION_TOP_CENTER = 1;
    public const POSITION_TOP_RIGHT = 2;
    public const POSITION_CENTER_LEFT = 3;
    public const POSITION_CENTER_CENTER = 4;
    public const POSITION_CENTER_RIGHT = 5;
    public const POSITION_BOTTOM_LEFT = 6;
    public const POSITION_BOTTOM_CENTER = 7;
    public const POSITION_BOTTOM_RIGHT = 8;

    public $id_homeslider;
    public $title;
    public $url;
    public $legend;
    public $image_url;
    public $image_mobile_url;
    public $video_url = '';
    public $video_mobile_url = '';
    public $active;
    public $position;
    public $id_shop;
    public $active_desktop = 1;
    public $active_mobile = 1;
    public $content_position = self::POSITION_CENTER_CENTER;

    public $date_from = '0000-00-00 00:00:00';
    public $date_to = '0000-00-00 00:00:00';

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'homeslider_slide',
        'primary' => 'id_homeslider_slide',
        'multilang' => true,
        'fields' => [
            'id_homeslider' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'active_desktop' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'active_mobile' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'date_from' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_to' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'video_url' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 255],
            'video_mobile_url' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 255],
            'content_position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],

            // Lang fields
            'url' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isAbsoluteUrl', 'size' => 255],
            'title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'legend' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'image_url' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'size' => 255],
            'image_mobile_url' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'size' => 255],
        ],
    ];

    public function add($auto_date = true, $null_values = false)
    {
        if ($this->position <= 0) {
            $this->position = self::getHigherPosition($this->id_homeslider) + 1;
        }

        return parent::add($auto_date, true);
    }

    public function delete()
    {
        $return = parent::delete();
        $this->cleanPositions($this->id_homeslider);

        return $return;
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function updatePosition($way, $position, $id_object = null): bool
    {
        $primary_key = self::$definition['primary'];
        $table_name = self::$definition['table'];

        $id_parent_object = (int) Db::getInstance()->getValue('
            SELECT `id_homeslider`
            FROM `' . _DB_PREFIX_ . bqSQL($table_name) . '`
            WHERE `' . bqSQL($primary_key) . '` = ' . (int) ($id_object ?: $this->id)
        );

        $res = Db::getInstance()->executeS('
            SELECT `position`, `' . bqSQL($primary_key) . '`
            FROM `' . _DB_PREFIX_ . bqSQL($table_name) . '`
            WHERE `' . bqSQL($primary_key) . '` = ' . (int) ($id_object ?: $this->id) . '
            ORDER BY `position` ASC'
        );

        if (!$res) {
            return false;
        }

        foreach ($res as $object) {
            if ((int) $object[$primary_key] == (int) $this->id) {
                $moved_object = $object;
            }
        }

        if (!isset($moved_object) || !isset($position)) {
            return false;
        }

        $rs = Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . bqSQL($table_name) . '`
            SET `position`= `position` ' . ($way ? '- 1' : '+ 1') . '
            WHERE `id_homeslider` = ' . (int) $id_parent_object . ' AND `position`
            ' . ($way
                ? '> ' . (int) $moved_object['position'] . ' AND `position` <= ' . (int) $position
                : '< ' . (int) $moved_object['position'] . ' AND `position` >= ' . (int) $position)
        );

        $rs &= Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . bqSQL($table_name) . '`
            SET `position` = ' . (int) $position . '
            WHERE `id_homeslider` = ' . (int) $id_parent_object . ' AND `' . bqSQL($primary_key) . '`=' . (int) $moved_object[$primary_key]
        );

        return $rs;
    }

    public static function cleanPositions($id_object): bool
    {
        Db::getInstance()->execute('SET @i = -1', false);
        $sql = 'UPDATE `' . _DB_PREFIX_ . bqSQL(self::$definition['table']) . '` SET `position` = @i:=@i+1 WHERE `id_homeslider` = ' . (int) $id_object . ' ORDER BY `position` ASC';

        return Db::getInstance()->execute($sql);
    }

    public static function getHigherPosition($id_object)
    {
        $sql = 'SELECT MAX(`position`) FROM `' . _DB_PREFIX_ . bqSQL(self::$definition['table']) . '` WHERE `id_homeslider` = ' . (int) $id_object;
        $position = DB::getInstance()->getValue($sql);

        return (is_numeric($position)) ? $position : -1;
    }
}
