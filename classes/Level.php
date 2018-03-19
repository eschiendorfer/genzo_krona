<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

class Level extends \ObjectModel {
    public $id;
    public $id_level;
    public $condition_type;
    public $condition_time;
    public $condition;
    public $id_action;
    public $duration;
    public $reward_type;
    public $id_reward;
    public $achieve_max;
    public $icon;
    public $hide;
    public $name;
    public $active;
    public $position;

    public static $definition = array(
        'table' => "genzo_krona_level",
        'primary' => 'id_level',
        'multilang' => true,
        'fields' => array(
            'id_level'          => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'condition_type'    => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'condition_time'    => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'condition'         => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'id_action'         => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'duration'          => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'reward_type'       => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'id_reward'         => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'achieve_max'       => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'icon'              => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'active'            => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'hide'              => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'position'          => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'name'              => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true),
        )
    );

    public static function checkIfLevelActive ($id_level, $id_shop = null) {

        $id_level = (int)$id_level;

        if (\Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') AND !$id_shop) {
            $id_shop = \Context::getContext()->shop->id;
        }

        $query = new \DbQuery();
        $query->select('active');
        $query->from(self::$definition['table'], 'l');
        if ($id_shop) {
            $query->innerJoin(self::$definition['table'].'_shop', 's', 's.`id_level` = l.`id_level`');
            $query->where('s.`id_shop` = ' . (int)$id_shop);
        }
        $query->where("l.`id_level` = '{$id_level}'");
        return \Db::getInstance()->getValue($query);
    }

    public function updatePosition($way, $position) {

        if (!$res = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new \DbQuery())
                ->select('`id_level`, `position`')
                ->from(self::$definition['table'])
                ->orderBy('`position` ASC')
        )) {
            return false;
        }

        foreach ($res as $level) {
            if ((int) $level['id_level'] == (int) $this->id) {
                $movedLevel = $level;
            }
        }

        if (!isset($movedLevel) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        return \Db::getInstance()->update(
                self::$definition['table'],
                [
                    'position' => ['type' => 'sql', 'value' => '`position` '.($way ? '- 1' : '+ 1')],
                ],
                '`position` '.($way ? '> '.(int) $movedLevel['position'].' AND `position` <= '.(int) $position : '< '.(int) $movedLevel['position'].' AND `position` >= '.(int) $position)
            ) && \Db::getInstance()->update(
                self::$definition['table'],
                [
                    'position' => (int) $position,
                ],
                '`id_level` = '.(int) $movedLevel['id_level']
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
        return \Db::getInstance()->getValue($query);

    }

}