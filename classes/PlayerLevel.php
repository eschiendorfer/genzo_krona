<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Level.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/PlayerHistory.php';

class PlayerLevel extends \ObjectModel {

    public $id;
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
        'primary' => 'id',
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


    public static function getAllPlayerLevels ($id_customer, $filters = null, $pagination = null, $order = null ) {

        // Doesn't need to be multistore, since its customer related
        $id_lang = (int)\Context::getContext()->language->id;
        $id_customer = (int)$id_customer;

        $query = new \DbQuery();
        $query->select('*');
        $query->from(self::$definition['table'], 'pl');
        $query->innerJoin('genzo_krona_level', 'l', 'l.`id_level` = pl.`id_level`');
        $query->innerJoin('genzo_krona_level_lang', 'll', 'll.`id_level` = pl.`id_level`');
        $query->where('pl.`id_customer` = ' . $id_customer);
        $query->where('ll.`id_lang`= ' . $id_lang);
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

        return \Db::getInstance()->ExecuteS($query);
    }

    public static function getAllPlayerLevelsTotal ($id_customer, $filters = null) {

        // Doesn't need to be multistore, since its customer related
        $id_lang = (int)\Context::getContext()->language->id;
        $id_customer = (int)$id_customer;

        $query = new \DbQuery();
        $query->select('pl.id_level');
        $query->from(self::$definition['table'], 'pl');
        $query->innerJoin('genzo_krona_level_lang', 'll', 'll.`id_level` = pl.`id_level`');
        $query->where('pl.`id_customer` = ' . $id_customer);
        $query->where('ll.`id_lang`= ' . $id_lang);
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        $query->groupBy('pl.`id_level`');

        $player_levels =  \Db::getInstance()->ExecuteS($query);

        return count($player_levels);
    }

    public static function getId($id_customer, $id_level, $only_active = false) {
        $query = new \DbQuery();
        $query->select('id');
        $query->from(self::$definition['table']);
        $query->where('`id_customer` = ' . (int)$id_customer);
        $query->where('`id_level` = ' . (int)$id_level);
        if ($only_active) {
            $query->where('`active_until` < CURRENT_DATE()');
        }
        return \Db::getInstance()->getValue($query);
    }

    public static function updatePlayerLevel($id_customer, $id_action, $actionOrder = null) {

        // Multistore check
        if (\Shop::isFeatureActive()) {
            $customer = new \Customer($id_customer);
            $id_shop = $customer->id_shop;
        } else {
            $id_shop = null;
        }

        // Handling Levels with condition_type = points
        if ($actionOrder === true) {
            $ids_level_points = self::getLevelsByConditionType('pointsOrder', null, $id_shop);
        }
        elseif ($actionOrder === false) {
            $ids_level_points = self::getLevelsByConditionType('pointsAction', null, $id_shop);
        }
        else {
            $ids_level_points = self::getLevelsByConditionType('points', null, $id_shop);
        }

        foreach ($ids_level_points as $id_level) {

            $id_level = $id_level['id_level'];

            if (Level::checkIfLevelActive($id_level, $id_shop)) {

                $id = self::getId($id_customer, $id_level); // The customer could have reached this level in the past

                $playerLevel = new PlayerLevel($id);
                $level = new Level($id_level);

                // Check if customer still has the right, to achieve this level
                if ($level->achieve_max > $playerLevel->achieved) {

                    // How many points did the customer collect in the time condition
                    $dateStart = null;
                    $dateEnd = null;

                    if ($level->condition_time OR $playerLevel->achieved_last) {

                        if ($level->condition_time) {
                            $dateStart = date('Y-m-d 00:00:00', strtotime("-{$level->condition_time} days"));
                        } else {
                            $dateStart = date('2000-01-01'); // This basically means unlimited...
                        }

                        // If a player has achieved a level, he has to achieve it again from scratch
                        if ($playerLevel->achieved_last > $dateStart) {
                            $dateStart = $playerLevel->achieved_last;
                        }

                        $dateEnd = date('Y-m-d 23:59:59');
                    }

                    $reached_points = PlayerHistory::sumActionPointsByPlayer($id_customer, $level->condition_type, $dateStart, $dateEnd);

                    // Check if the customer has fulfilled the condition
                    if ($reached_points >= $level->condition) {

                        $playerLevel->id_customer = $id_customer;
                        $playerLevel->id_level = $id_level;
                        $playerLevel->active = 1;

                        // If duration is not set, it means unlimited
                        ($level->duration > 0) ? $active_until = date('Y-m-d 23:59:59', strtotime("+{$level->duration} days")) : $active_until = '0000-00-00 00:00:00';
                        $playerLevel->active_until = $active_until;

                        $playerLevel->achieved = $playerLevel->achieved + 1;
                        $playerLevel->achieved_last = date("Y-m-d H:i:s", strtotime("+1 second")); // This is securing that the last done action, wont be taken again

                        $playerLevel->save();

                        self::giveTheReward($id_customer, $level);
                    }
                }
            }

        }

        // Destroy objects for a fresh start
        unset($level);
        unset($levelPlayer);

        // Handling levels with condition type action
        $ids_level_action = self::getLevelsByConditionType('action', $id_action, $id_shop);

        foreach ($ids_level_action as $id_level) {

            $id_level = $id_level['id_level'];

            if (Level::checkIfLevelActive($id_level, $id_shop)) {

                $id = self::getId($id_customer, $id_level); // The customer could have reached this level in the past

                $level = new Level($id_level);
                $playerLevel = new PlayerLevel($id);

                // Check if customer still has the right, to achieve this level
                if ($level->achieve_max > $playerLevel->achieved) {

                    $dateStart = null;
                    $dateEnd = null;

                    if ($level->condition_time OR $playerLevel->achieved_last) {

                        if ($level->condition_time) {
                            $dateStart = date('Y-m-d 00:00:00', strtotime("-{$level->condition_time} days"));
                        } else {
                            $dateStart = date('2000-01-01'); // This basically means unlimited...
                        }

                        // If a player has achieved a level, he has to achieve it again from scratch
                        if ($playerLevel->achieved_last > $dateStart) {
                            $dateStart = $playerLevel->achieved_last;
                        }

                        $dateEnd = date('Y-m-d 23:59:59');
                    }

                    $executions = PlayerHistory::countActionByPlayer($id_customer, $id_action, $dateStart, $dateEnd);

                    if ($executions >= $level->condition) {
                        $playerLevel->id_customer = $id_customer;
                        $playerLevel->id_level = $id_level;
                        $playerLevel->active = 1;

                        // If duration is not set, it means unlimited
                        ($level->duration > 0) ? $active_until = date('Y-m-d 23:59:59', strtotime("+{$level->duration} days")) : $active_until = '0000-00-00 00:00:00';
                        $playerLevel->active_until = $active_until;

                        $playerLevel->achieved = $playerLevel->achieved + 1;
                        $playerLevel->achieved_last = date("Y-m-d H:i:s", strtotime("+1 second")); // This is securing that the last done action, wont be taken again

                        $playerLevel->save();

                        self::giveTheReward($id_customer, $level);
                    }
                }
            }
        }

        return true;
    }

    private static function getLevelsByConditionType($condition_type, $id_action = null, $id_shop = null) {

        if (\Shop::isFeatureActive()) {
            ($id_shop) ? $id_shop = (int)$id_shop : $id_shop = \Context::getContext()->shop->id;
        }

        $query = new \DbQuery();
        $query->select('l.id_level');
        $query->from('genzo_krona_level', 'l');
        $query->innerJoin('genzo_krona_level_shop', 's', 's.`id_level` = l.`id_level`');
        $query->where('l.`active` = 1');
        if ($condition_type == 'points') {
            $query->where("`condition_type` LIKE 'points%'");
        }
        elseif ($condition_type == 'pointsOrder' OR $condition_type == 'pointsAction') {
            $query->where("`condition_type` = '{$condition_type}' OR `condition_type` = 'points'");
        }
        else {
            $query->where("`condition_type` = '{$condition_type}'"); // For Actions
        }

        if ($id_shop) {
            $query->where('`id_shop`=' . (int)$id_shop);
        }

        if ($id_action) {
            $query->where('`id_action`='.(int)$id_action);
        }
        return \Db::getInstance()->ExecuteS($query);
    }

    private static function giveTheReward($id_customer, $level) {
        $ids_lang = \Language::getIDs();

        if($level->reward_type == 'coupon') {
            $id_cart_rule = $level->id_reward;
            $coupon = new \CartRule($id_cart_rule);

            // Clone the cart rule and override some values
            $coupon->id_customer = (int)$id_customer;

            // Merchant can set date in cart rule, we need the difference between the dates
            if ($coupon->date_from && $coupon->date_to) {
                $validity = strtotime($coupon->date_to) - strtotime($coupon->date_from);
                $coupon->date_to = date("Y-m-d 23:59:59", strtotime("+{$validity} seconds"));
            }
            else {
                $coupon->date_to = date("Y-m-d 23:59:59", strtotime("+{$level->duration} days"));
            }
            $coupon->date_from = date("Y-m-d H:i:s");


            foreach ($ids_lang as $id_lang) {
                $coupon->name[$id_lang] = Coupon::getCouponName($coupon->name[$id_lang]);
            }

            $customer = new \Customer($id_customer);

            $prefix = \Configuration::get('krona_coupon_prefix', null, $customer->id_shop_group, $customer->id_shop);
            $code = strtoupper(\Tools::passwdGen(6));

            $coupon->code = ($prefix) ? $prefix.'-'.$code : $code;
            $coupon->active = true;
            $coupon->add();

            \CartRule::copyConditions($id_cart_rule, $coupon->id);
        }
        elseif ($level->reward_type == 'group') {
            $id_group = $level->id_reward;
            $groups[] = $id_group;

            $customer = new \Customer($id_customer);
            $customer->addGroups($groups);

            // Smaller means higher priority
            if (self::getPriorityOfGroup($id_group) < self::getPriorityOfGroup($customer->id_default_group)) {
                $customer->id_default_group = $id_group;
                $customer->update();
            }

        }

    }

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
        $query->select('p.id, p.id_customer, l.id_reward');
        $query->from(self::$definition['table'], 'p');
        $query->innerJoin('genzo_krona_level', 'l', 'l.`id_level` = p.`id_level`');
        $query->where('p.`active` = 1');
        $query->where('p.`active_until` != ""');
        $query->where('p.`active_until` < CURDATE()');
        $query->where("l.`reward_type` = 'group'");
        $ids_level = \Db::getInstance()->ExecuteS($query);

        foreach ($ids_level as $id_level) {

            $id = $id_level['id'];
            $id_customer = $id_level['id_customer'];
            $id_group = $id_level['id_reward'];

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
            $playerLevel = new PlayerLevel($id);
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