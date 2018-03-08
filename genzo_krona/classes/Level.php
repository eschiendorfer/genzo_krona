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
    public $name;
    public $active;

    public static $definition = array(
        'table' => "genzo_krona_level",
        'primary' => 'id_level',
        'multilang' => true,
        'fields' => array(
            'condition_type'   => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'condition_time'   => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'condition'   => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'id_action'   => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'duration'   => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'reward_type'        => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'id_reward'   => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'achieve_max'   => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'icon'        => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'active'        => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'name'        => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true),
        )
    );

    public static function getAllLevels ($filters = null, $pagination = null, $order = null) {

        $id_lang = (int)\Context::getContext()->language->id;

        (\Shop::isFeatureActive()) ? $ids_shop = \Shop::getContextListShopID() : $ids_shop = null;

        $query = new \DbQuery();
        $query->select('*');
        $query->from(self::$definition['table'], 'l');
        $query->innerJoin(self::$definition['table'].'_lang', 'll', 'll.`id_level` = l.`id_level`');
        $query->where('ll.`id_lang`=' . $id_lang);
        if ($ids_shop) {
            $query->innerJoin(self::$definition['table'].'_shop', 's', 's.`id_level` = l.`id_level`');
            $query->where('s.`id_shop` IN (' . implode(',', array_map('intval', $ids_shop)) . ')');
        }
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        if ($pagination) {
            $limit = (int) $pagination['limit'];
            $offset = (int)$pagination['offset'];
            $query->limit($limit, $offset);
        }

        $query->groupBy('l.`id_level`');
        if ($order) {
            (!empty($order['alias'])) ? $alias = $order['alias'].'.' : $alias = '';
            $query->orderBy("{$alias}`{$order['order_by']}` {$order['order_way']}");
        }

        return \Db::getInstance()->ExecuteS($query);
    }

    public static function getTotalLevels($filters = null) {
        $multishop = \Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');
        $id_lang = (int)\Context::getContext()->language->id;
        if ($multishop) { $shop_ids = \Shop::getContextListShopID(); }

        $query = new \DbQuery();
        $query->select('l.id_level'); // Strangely it doesn't work to Count direct
        $query->from(self::$definition['table'], 'l');
        $query->innerJoin(self::$definition['table'].'_lang', 'll', 'll.`id_level` = l.`id_level`');
        $query->where('ll.`id_lang` = ' . $id_lang);
        if ($multishop) {
            $query->innerJoin(self::$definition['table'] . '_shop', 's', 's.`id_level` = l.`id_level`');
            $query->where('s.`id_shop` IN (' . implode(',', array_map('intval', $shop_ids)) . ')');
        }

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        $query->groupBy('l.`id_level`');
        $rows = \Db::getInstance()->ExecuteS($query);

        return count($rows);
    }

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


}