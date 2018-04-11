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
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Coupon.php';
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
        else {
            $query->orderBy('l.`position` ASC');
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

    public static function getLastPlayerLevel($id_customer) {

        $id_lang = \Context::getContext()->language->id;

        $query = new \DbQuery();
        $query->select('id_level');
        $query->from(self::$definition['table']);
        $query->where('`id_customer`='.(int)$id_customer);
        $query->orderby('id DESC');
        $id_level = \Db::getInstance()->getValue($query);

        $level = ($id_level) ? new Level($id_level, $id_lang) : false;

        return $level;

    }


    public static function getNextPlayerLevel($id_customer) {

        $id_lang = \Context::getContext()->language->id;
        $id_shop = \Context::getContext()->shop->id;

        $last_level = self::getLastPlayerLevel($id_customer);

        $query = new \DbQuery();
        $query->select('l.id_level');
        $query->from('genzo_krona_level', 'l');
        $query->innerJoin('genzo_krona_level_shop', 's', 'l.id_level=s.id_level');
        $query->where('id_shop='.$id_shop);
        $query->where('position = ' . ($last_level->position+1));
        $query->where('active = 1');
        $id_next_level = \Db::getInstance()->getValue($query);

        $level = ($id_next_level) ? new Level($id_next_level, $id_lang) : false;

        return $level;
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


    /**
     * @var \CustomerCore $customer
     * @param $actionType
     * @param $id_action
     * @return bool
     * @throws \PrestaShopException
     */
    public static function updatePlayerLevel($customer, $actionType, $id_action) {

        // Multistore check
        $id_shop = $customer->id_shop;

        // Handling Levels with Thresholds

        $ids_level = self::getLevelsByConditionType($actionType, $id_shop, $id_action);

        foreach ($ids_level as $id_level) {

            $id_level = $id_level['id_level'];

            if (Level::checkIfLevelActive($id_level, $id_shop)) {

                $id = self::getId($customer->id, $id_level); // The customer could have reached this level in the past

                $playerLevel = new PlayerLevel($id);
                $level = new Level($id_level);

                // Check if customer still has the right, to achieve this level
                if ($level->achieve_max > $playerLevel->achieved OR $level->achieve_max == 0) {

                    // How many points did the customer collect in the time condition
                    $dateStart = null;
                    $dateEnd = null;

                    if ($level->condition_time OR $playerLevel->achieved_last) {

                        $dateStartCondition = 0;
                        $dateStartLevel = 0;

                        if ($level->condition_time) {
                            $dateStartCondition = date('Y-m-d 00:00:00', strtotime("-{$level->condition_time} days"));
                        }

                        // If a player has achieved a level, he has to achieve it again from scratch
                        if ($playerLevel->achieved_last > $dateStart) {
                            $dateStartLevel = $playerLevel->achieved_last;
                        }

                        // Take the newer StartDate
                        ($dateStartCondition > $dateStartLevel) ? $dateStart = $dateStartCondition : $dateStart = $dateStartLevel;

                        $dateEnd = date('Y-m-d 23:59:59');
                    }

                    $condition = 0;

                    if ($level->id_action > 0) {

                        if ($level->condition_type == 'action') {
                            $condition = PlayerHistory::countActionByPlayer($customer->id, $id_action, $dateStart, $dateEnd);
                        }
                        else if ($level->condition_type == 'order') {
                            $condition = PlayerHistory::countOrderByPlayer($customer->id, $id_action, $dateStart, $dateEnd);
                        }

                    }
                    else {
                        $condition = PlayerHistory::sumActionPointsByPlayer($customer->id, $level->condition_type, $dateStart, $dateEnd);
                    }

                    // Check if the customer has fulfilled the condition
                    if ($condition >= $level->condition) {

                        $playerLevel->id_customer = $customer->id;
                        $playerLevel->id_level = $id_level;
                        $playerLevel->active = 1;

                        // If duration is not set, it means unlimited
                        ($level->duration > 0) ? $active_until = date('Y-m-d 23:59:59', strtotime("+{$level->duration} days")) : $active_until = '0000-00-00 00:00:00';
                        $playerLevel->active_until = $active_until;

                        $playerLevel->achieved = $playerLevel->achieved + 1;
                        $playerLevel->achieved_last = date("Y-m-d H:i:s", strtotime("+1 second")); // This is securing that the last done action, wont be taken again

                        $playerLevel->save();

                        self::giveTheReward($customer, $level);
                    }
                }
            }
        }

        return true;
    }

    public static function getLevelsByConditionType($condition_type, $id_shop, $id_action) {
        $query = new \DbQuery();
        $query->select('l.id_level');
        $query->from('genzo_krona_level', 'l');
        $query->innerJoin('genzo_krona_level_shop', 's', 's.`id_level` = l.`id_level`');
        $query->where('l.`active` = 1');
        $query->where("`condition_type` LIKE '{$condition_type}' OR `condition_type`='total' OR `id_action`={$id_action}");
        $query->where('`id_shop`=' . (int)$id_shop);
        $query->orderBy('l.`position` ASC');
        return \Db::getInstance()->ExecuteS($query);
    }



    /**
     * @var \CustomerCore $customer
     * @var Level $level
     * @throws
     */
    private static function giveTheReward($customer, $level) {
        $ids_lang = \Language::getIDs();

        if($level->reward_type == 'coupon') {
            $id_cart_rule = $level->id_reward;
            $coupon = new \CartRule($id_cart_rule);

            // Clone the cart rule and override some values
            $coupon->id_customer = $customer->id;

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