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
class HomeSlider extends ObjectModel
{
    public $name;
    public $active;
    public $position;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'homeslider',
        'primary' => 'id_homeslider',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        Shop::addTableAssociation(self::$definition['table'], ['type' => 'shop']);
        parent::__construct($id, $id_lang, $id_shop, $translator);
    }

    public function add($auto_date = true, $null_values = false)
    {
        if ($this->position <= 0) {
            $this->position = self::getHigherPosition() + 1;
        }

        return parent::add($auto_date, true);
    }

    public function delete()
    {
        $return = parent::delete();
        $this->cleanPositions();

        return $return;
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function updatePosition($way, $position, $id_object = null): bool
    {
        $primary_key = self::$definition['primary'];
        $table_name = self::$definition['table'];

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
            WHERE `position`
            ' . ($way
                ? '> ' . (int) $moved_object['position'] . ' AND `position` <= ' . (int) $position
                : '< ' . (int) $moved_object['position'] . ' AND `position` >= ' . (int) $position)
        );

        $rs &= Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . bqSQL($table_name) . '`
            SET `position` = ' . (int) $position . '
            WHERE `' . bqSQL($primary_key) . '`=' . (int) $moved_object[$primary_key]
        );

        return $rs;
    }

    public static function cleanPositions(): bool
    {
        Db::getInstance()->execute('SET @i = -1', false);
        $sql = 'UPDATE `' . _DB_PREFIX_ . bqSQL(self::$definition['table']) . '` SET `position` = @i:=@i+1 ORDER BY `position` ASC';

        return Db::getInstance()->execute($sql);
    }

    public static function getHigherPosition()
    {
        $sql = 'SELECT MAX(`position`) FROM `' . _DB_PREFIX_ . bqSQL(self::$definition['table']) . '`';
        $position = DB::getInstance()->getValue($sql);

        return (is_numeric($position)) ? $position : -1;
    }
}
