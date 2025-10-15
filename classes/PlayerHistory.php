<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

use CoreExtension\CacheService;

require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

class PlayerHistory extends \ObjectModel {

    public $id_history;
    public $id_customer;
    public $id_action;
    public $id_action_order;
    public $id_order;

    public $force_display; // If we want to force a number in the timeline
    public $points;
    public $coins;

    public $loyalty;
    public $loyalty_used;
    public $loyalty_expired;

    public $loyalty_expire_date;
    public $message;
    public $comment;
    public $title;
    public $url;
    public $viewable = true; // Should this appear in FO?
    public $viewed;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => "genzo_krona_player_history",
        'primary' => 'id_history',
        'multilang' => true,
        'fields' => array(
            'id_customer'           => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_action'             => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_action_order'       => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_order'              => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'force_display'         => array('type' => self::TYPE_NOTHING),
            'points'                => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'coins'                 => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'loyalty'               => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'loyalty_used'          => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'loyalty_expired'       => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'loyalty_expire_date'   => array('type' => self::TYPE_DATE),
            'title'                 => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true),
            'message'               => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true),
            'comment'               => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true),
            'url'                   => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'viewable'              => array('type' => self::TYPE_BOOL),
            'viewed'                => array('type' => self::TYPE_BOOL),
            'date_add'              => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
            'date_upd'              => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
        )
    );

    public function save($force_loyalty = false, $nullValues = true, $autoDate = true) {
        return (int) $this->id > 0 ? $this->update($force_loyalty, $nullValues) : $this->add($force_loyalty, $autoDate, $nullValues);
    }

    public function update($force_loyalty = false, $nullValues = true) {

        if (!$force_loyalty) {
            $this->setLoyalty();
        }

        CacheService::deleteCacheByTriggerEntityObject($this);

        // We need to have the null value in force_display
        return parent::update($nullValues);
    }

    public function add($force_loyalty = false, $autoDate = true, $nullValues = true) {

        if (!$force_loyalty) {
            $this->setLoyalty();
        }

        // Expiring
        if (\Configuration::get('loyalty_expire_method')!='none' && $days = \Configuration::get('krona_loyalty_expire_days')) {
            $this->loyalty_expire_date = date("Y-m-d H:i:s", strtotime("+{$days} days"));
        };

        CacheService::deleteCacheByTriggerEntityObject($this);

        return parent::add($autoDate, $nullValues);
    }

    private function setLoyalty() {
        // Always remember there is no static loyalty. The customer just collects points and coins. The merchant defines what loyalty is in BO.
        if (\Configuration::get('krona_loyalty_active')) {

            $total_mode_loyalty = \Configuration::get('krona_loyalty_total');

            if ($total_mode_loyalty == 'points_coins') {
                $this->loyalty = $this->points + $this->coins;
            }
            elseif ($total_mode_loyalty == 'points') {
                $this->loyalty = $this->points;
            }
            elseif ($total_mode_loyalty == 'coins') {
                $this->loyalty = $this->coins;
            }
        }
    }

    // Database
    public static function getIdHistoryByIdOrder($id_customer, $id_order) {
        $query = new \DbQuery();
        $query->select('id_history');
        $query->from(self::$definition['table']);
        $query->where('id_customer = ' . $id_customer);
        $query->where('id_order = ' . $id_order);
        return (int)\Db::getInstance()->getValue($query);
    }

    public static function getHistoryByPlayer($id_customer, $filters = null, $pagination = null, $order = null) {

        $id_lang = \Context::getContext()->language->id;

        $query = new \DbQuery();
        $query->select('h.id_history, h.id_customer, h.id_action, h.id_action_order, h.url, h.date_add, h.date_upd, IFNULL(h.force_display, (h.points+h.coins)) AS `change`, h.loyalty, h.viewed, l.*');
        $query->from(self::$definition['table'], 'h');
        $query->innerJoin(self::$definition['table'].'_lang', 'l', 'l.`id_history` = h.`id_history` AND l.`id_lang` = ' . (int)$id_lang);
        $query->where('`id_customer` = ' . (int)$id_customer);

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

        if ($order) {
            (!empty($order['alias'])) ? $alias = $order['alias'].'.' : $alias = '';
            $query->orderBy("{$alias}`{$order['order_by']}` {$order['order_way']}");
        }
        else {
            $query->orderBy('h.`id_history` DESC');
        }

        $histories = \Db::getInstance()->ExecuteS($query);

        // Replace pseudonym shortcodes with real pseudonymes
        // Note: we can't do that while saving, as pseudonyms can change over time
        foreach ($histories as &$history) {
            $pattern = '/\{pseudonym\s+id_customer="(\d+)"\}/';
            preg_match_all($pattern, $history['message'], $matches);

            if ($matches && !empty($matches[0])) {
                foreach ($matches[0] as $pseudonym) {
                    $patternPseudonym = '/\{pseudonym\s+id_customer="(\d+)"\}/';
                    preg_match($patternPseudonym, $pseudonym, $matchesCustomer);

                    if (!empty($matchesCustomer[1])) {
                        if ($id_customer = (int)$matchesCustomer[1]) {
                            $kronaCustomer = \Hook::exec('displayKronaCustomer', array('id_customer' => $id_customer), null, true, false);
                            $history['message'] = str_replace($pseudonym, $kronaCustomer['genzo_krona']['pseudonym'], $history['message']);
                        }
                    }
                }

            }
        }

        return $histories;
    }

    public static function getTotalHistoryByPlayer($id_customer, $filters = null) {

        $id_lang = \Context::getContext()->language->id;

        $query = new \DbQuery();
        $query->select('h.id_history');
        $query->from(self::$definition['table'], 'h');
        $query->innerJoin(self::$definition['table'].'_lang', 'l', 'l.`id_history` = h.`id_history`');
        $query->where('`id_customer` = ' . (int)$id_customer);
        $query->where('l.`id_lang` = ' . (int)$id_lang);

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        $rows = \Db::getInstance()->ExecuteS($query);

        return count($rows);
    }

    public static function countActionByPlayer($id_customer, $id_action, $startDate = null, $endDate = null) {

        $query = new \DbQuery();
        $query->select('Count(*)');
        $query->from(self::$definition['table']);
        $query->where('`id_customer` = ' . (int)$id_customer);
        $query->where('`id_action` = ' . (int)$id_action);

        if ($startDate && $endDate) {
            $query->where("`date_add` BETWEEN '{$startDate}' AND '{$endDate}'");
        }
        elseif ($startDate) {
            $query->where("`date_add` >= '{$startDate}' ");
        }

        return (int)\Db::getInstance()->getValue($query);
    }

    /* $mode can be: order, ref_referrer, ref_buyer */
    public static function countOrderByPlayer($id_customer, $id_action, $mode = 'order', $startDate = null, $endDate = null) {

        $in_states = \Configuration::get('krona_order_state');

        $query = new \DbQuery();
        $query->select('Count(*)');
        $query->from(self::$definition['table'], 'ph');
        $query->innerJoin('orders', 'o', 'ph.id_order=o.id_order');
        $query->where('ph.`id_customer` = ' . (int)$id_customer);
        $query->where('ph.`id_action_order` = ' . (int)$id_action);
        $query->where("o.current_state IN ({$in_states})");

        if ($mode=='order' || $mode=='ref_buyer') {
            $query->where('o.id_customer='.(int)$id_customer);
        }
        elseif ($mode == 'ref_referrer') {
            $query->where('o.id_customer!='.(int)$id_customer);
        }

        if ($mode == 'ref_buyer') {
            $query->innerJoin('genzo_krona_player', 'p', 'p.id_customer=ph.id_customer');
            $query->where ('p.id_customer_referrer > 0'); // This is the only difference between was_referred and normal order level
        }

        if ($startDate && $endDate) {
            $query->where("ph.`date_add` BETWEEN '{$startDate}' AND '{$endDate}'");
        }
        elseif ($startDate) {
            $query->where("ph.`date_add` >= '{$startDate}' ");
        }

        return (int)\Db::getInstance()->getValue($query);
    }

    public static function sumActionPointsByPlayer($id_customer, $condition_type, $startDate = null, $endDate = null) {

        $query = new \DbQuery();
        $query->select('SUM(ph.`points`+ph.`coins`)');
        $query->from(self::$definition['table'], 'ph');
        $query->where('`id_customer` = ' . (int)$id_customer);

        if ($startDate && $endDate) {
            $query->where("`date_add` BETWEEN '{$startDate}' AND '{$endDate}'");
        }
        elseif ($startDate) {
            $query->where("`date_add` > '{$startDate}' ");
        }

        if ($condition_type == 'points') {
            $query->where("`id_action_order` = 0"); // We only wanna normal actions
        }
        elseif ($condition_type == 'coins') {
            $query->where("`id_action` = 0"); // We only wanna actionOrders
        }

        return (int)\Db::getInstance()->getValue($query);
    }

    public static function getNotificationValue($id_customer) {
        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from('genzo_krona_player_history');
        $query->where('viewed=0 AND viewable=1 AND id_customer = ' . $id_customer);

        // It doesn't make much sense to show page_visits as the customer would always see a notification, when he starts a customer journey
        $query->where('id_action!=2');

        return (int)\Db::getInstance()->getValue($query);
    }

}