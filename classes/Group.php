<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

class Group extends \ObjectModel {

    public $id;
    public $id_group;
    public $position;

    public static $definition = array(
        'table'     => "genzo_krona_settings_group",
        'primary'   => 'id_group',
        'multilang' => false,
        'fields' => array(
            'id_group'          => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'position'          => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
        )
    );

    public function updatePosition($way, $position) {

        if (!$res = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new \DbQuery())
                ->select('`id_group`, `position`')
                ->from(self::$definition['table'])
                ->orderBy('`position` ASC')
        )) {
            return false;
        }

        foreach ($res as $group) {
            if ((int) $group['id_group'] == (int) $this->id) {
                $movedGroup = $group;
            }
        }

        if (!isset($movedGroup) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        return \Db::getInstance()->update(
                self::$definition['table'],
                [
                    'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
                ],
                '`position` '.($way ? '> '.(int) $movedGroup['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedGroup['position'].' AND `position` >= '.(int) $position)
            ) && \Db::getInstance()->update(
                self::$definition['table'],
                [
                    'position' => (int) $position,
                ],
                '`id_group` = '.(int) $movedGroup['id_group']
            );
    }

    public static function cleanPositions() {

        \Db::getInstance()->execute('SET @i = -1', false);
        $sql = 'UPDATE `'._DB_PREFIX_.self::$definition['table'].'` SET `position` = @i:=@i+1 ORDER BY `position` ASC';

        return (bool) \Db::getInstance()->execute($sql);
    }

    public static function getHighestPosition() {
        $query = new \DbQuery();
        $query->select('position');
        $query->from(self::$definition['table']);
        $query->orderby('position DESC');
        $position = \Db::getInstance()->getValue($query);

        return max($position, 1);

    }

}