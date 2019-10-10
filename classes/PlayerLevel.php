<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

class PlayerLevel extends \ObjectModel {

    public $id;
    public $id_player_level;
    public $id_customer;
    public $id_level;
    public $active;
    public $active_until;
    public $achieved;
    public $achieved_last;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => "genzo_krona_player_level",
        'primary' => 'id_player_level',
        'fields' => array(
            'id_customer'   => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_level'   => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'active'   => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'active_until'   => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'achieved'   => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'achieved_last'   => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_add'    => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
            'date_upd'    => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
        )
    );

    // Database
    public static function getAllPlayerLevels($id_customer, $filters = null, $pagination = null, $order = null ) {

        // Doesn't need to be multistore, since its customer related
        $id_lang = (int)\Context::getContext()->language->id;
        $id_customer = (int)$id_customer;

        $query = new \DbQuery();
        $query->select('*, pl.active as active'); // otherwise its overwritten with the active from l.
        $query->from(self::$definition['table'], 'pl');
        $query->innerJoin('genzo_krona_level', 'l', 'l.`id_level` = pl.`id_level`');
        $query->innerJoin('genzo_krona_level_lang', 'll', 'll.`id_level` = pl.`id_level` AND ll.`id_lang`= ' . $id_lang);
        $query->where('pl.`id_customer` = ' . $id_customer);
        
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

        $query->groupBy('pl.`id_level`');
        if ($order) {
            (!empty($order['alias'])) ? $alias = $order['alias'].'.' : $alias = '';
            $query->orderBy("{$alias}`{$order['order_by']}` {$order['order_way']}");
        }
        else {
            $query->orderBy('l.`position` ASC');
        }

        return \Db::getInstance()->ExecuteS($query);
    }

    public static function getAllPlayerLevelsTotal($id_customer, $filters = null) {

        // Doesn't need to be multistore, since its customer related
        $id_lang = (int)\Context::getContext()->language->id;
        $id_customer = (int)$id_customer;

        $query = new \DbQuery();
        $query->select('COUNT(id_player_level)');
        $query->from(self::$definition['table'], 'pl');
        $query->innerJoin('genzo_krona_level_lang', 'll', 'll.`id_level` = pl.`id_level` AND ll.`id_lang`= ' . $id_lang);
        $query->where('pl.`id_customer` = ' . $id_customer);

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        $query->groupBy('pl.`id_level`');

        return (int)\Db::getInstance()->getValue($query);
    }

    public static function getLastPlayerLevel($id_customer) {

        $id_lang = \Context::getContext()->language->id;

        $query = new \DbQuery();
        $query->select('id_level');
        $query->from(self::$definition['table']);
        $query->where('`id_customer`='.(int)$id_customer);
        $query->orderby('id_player_level DESC');
        $id_level = \Db::getInstance()->getValue($query);

        $level = ($id_level) ? new Level($id_level, $id_lang) : false;

        return $level;
    }

    public static function getNextPlayerLevel($id_customer) {

        $id_lang = \Context::getContext()->language->id;
        $id_shop = \Context::getContext()->shop->id;

        $query = new \DbQuery();
        $query->select('l.id_level');
        $query->from('genzo_krona_level', 'l');
        $query->innerJoin('genzo_krona_level_shop', 's', 'l.id_level=s.id_level AND s.id_shop='.$id_shop);
        $query->leftJoin('genzo_krona_player_level', 'pl', 'pl.id_level=l.id_level AND pl.id_customer='.$id_customer);
        $query->orderBy('position ASC');
        $query->where('l.active = 1 AND (pl.id_player_level IS NULL OR pl.active=0)');

        $id_next_level = \Db::getInstance()->getValue($query);

        $level = ($id_next_level) ? new Level($id_next_level, $id_lang) : false;

        return $level;
    }

    // Helper
    public static function getPriorityOfGroup($id_group) {
        $query = new \DbQuery();
        $query->select('position');
        $query->from('genzo_krona_settings_group');
        $query->where('`id_group` = ' . (int)$id_group);
        return \Db::getInstance()->getValue($query);
    }

    public static function getHighestPriorityGroup($ids_group) {

        $new_id_group = 0;
        $priority_used = 100000;

        foreach ($ids_group as $id_group) {

            $priority = self::getPriorityOfGroup($id_group);

            if ($priority < $priority_used) {
                $new_id_group = $id_group;
                $priority_used = $priority;
            }
        }

        return $new_id_group;
    }

    // CronJob
    public static function executeCronSetbackLevels() {

        $query = new \DbQuery();
        $query->select('p.id_player_level, p.id_customer, l.id_reward');
        $query->from(self::$definition['table'], 'p');
        $query->innerJoin('genzo_krona_level', 'l', 'l.`id_level` = p.`id_level`');
        $query->where('p.`active` = 1');
        $query->where('p.`active_until` != ""');
        $query->where('p.`active_until` < CURDATE()');
        $query->where("l.`reward_type` = 'group'");

        $levels = \Db::getInstance()->ExecuteS($query);

        foreach ($levels as $level) {

            $id_player_level = $level['id_player_level'];
            $id_customer = $level['id_customer'];
            $id_group = $level['id_reward'];

            // First we undo the reward

            // Check if the customer gets this group by any other level
            if (!self::checkIfStillGroup($id_customer, $id_group)) {

                \Db::getInstance()->delete('customer_group', "id_customer={$id_customer} AND id_group={$id_group}");

                // Set the new default Group
                $customer = new \Customer($id_customer);
                $groups = $customer->getGroups();

                $customer->id_default_group = self::getHighestPriorityGroup($groups);
                $customer->update();
            }

            // Second we deactivate the Player Level
            $playerLevel = new PlayerLevel($id_player_level);
            $playerLevel->active = 0;
            $playerLevel->update();
        }

        return true;

    }

    private static function checkIfStillGroup($id_customer, $id_group) {

        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from(self::$definition['table'], 'p');
        $query->innerJoin('genzo_krona_level', 'l', 'l.`id_level` = p.`id_level`');
        $query->where('p.`id_customer` = ' . (int)$id_customer);
        $query->where('p.`active` = 1');
        $query->where("l.`reward_type` = 'group'");
        $query->where('l.`id_reward` = ' . (int)$id_group);
        $value = \Db::getInstance()->getValue($query);

        return ($value > 1) ? true : false;

    }

}