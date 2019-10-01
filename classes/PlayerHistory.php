<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

class PlayerHistory extends \ObjectModel {

    public $id_history;
    public $id_customer;
    public $id_action;
    public $id_action_order;

    /* @deprecated $change */
    public $change;

    public $change_points;
    public $change_coins;
    public $change_loyalty;
    public $message;
    public $title;
    public $url;
    public $date_add;
    public $date_upd;

    // Dynamic vars

    /* @var $player Player */
    public $player;

    public static $definition = array(
        'table' => "genzo_krona_player_history",
        'primary' => 'id_history',
        'multilang' => true,
        'fields' => array(
            'id_customer'      => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_action'        => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_action_order'  => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'change_points'    => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'change_coins'    => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'change_loyalty'   => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'title'            => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true),
            'message'          => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true),
            'url'              => array('type' => self::TYPE_STRING, 'validate' => 'isString',),
            'date_add'         => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
            'date_upd'         => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
        )
    );

    public function __construct($id_history = null, $playerObj = null, $idLang = null) {

        parent::__construct($id_history, $idLang, null);

        if ($playerObj !== false) {

            if (!$playerObj instanceof Player) {
                $playerObj = new Player($this->id_customer);
            }

            $this->player = $playerObj;
        }
    }

    public function add($autoDate = true, $nullValues = false) {

        $this->player->notification++;

        return parent::add($autoDate, $nullValues);
    }


    public function update($nullValues = false) {

        // Get old object and check if a player update is needed
        $oldPlayerHistory = new PlayerHistory($this->id_history, false);

        if (($this->player instanceof Player) && ($this->change_points!=$oldPlayerHistory->change_points || $this->change_coins!=$oldPlayerHistory->change_coins || $this->change_loyalty!=$oldPlayerHistory->change_loyalty)) {

            $this->player->points += $this->change_points-$oldPlayerHistory->change_points;
            $this->player->coins += $this->change_coins-$oldPlayerHistory->change_coins;
            $this->player->loyalty += $this->change_loyalty-$oldPlayerHistory->change_loyalty;
            $this->player->update();
        }

        return parent::update($nullValues);
    }

    public function delete() {

        if ($this->player instanceof Player) {

            $this->player->points -= $this->change_points;
            $this->player->coins -= $this->change_coins;
            $this->player->loyalty -= $this->change_loyalty;
            $this->player->update();
        }

        return parent::delete();
    }

    public static function getHistoryByPlayer($id_customer, $filters = null, $pagination = null, $order = null) {
        $id_lang = \Context::getContext()->language->id;

        $query = new \DbQuery();
        $query->select('h.id_history, h.id_customer, h.id_action, h.id_action_order, h.url, h.date_add, h.date_upd, h.change+h.change_loyalty AS `change`, l.*');
        $query->from(self::$definition['table'], 'h');
        $query->innerJoin(self::$definition['table'].'_lang', 'l', 'l.`id_history` = h.`id_history`');
        $query->where('`id_customer` = ' . (int)$id_customer);
        $query->where('l.`id_lang` = ' . (int)$id_lang);
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

        return \Db::getInstance()->ExecuteS($query);

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

        return \Db::getInstance()->getValue($query);
    }

    public static function countOrderByPlayer($id_customer, $id_action, $startDate = null, $endDate = null) {
        $query = new \DbQuery();
        $query->select('Count(*)');
        $query->from(self::$definition['table']);
        $query->where('`id_customer` = ' . (int)$id_customer);
        $query->where('`id_action_order` = ' . (int)$id_action);
        if ($startDate && $endDate) {
            $query->where("`date_add` BETWEEN '{$startDate}' AND '{$endDate}'");
        }

        return \Db::getInstance()->getValue($query);
    }

    public static function sumActionPointsByPlayer($id_customer, $condition_type, $startDate = null, $endDate = null) {
        $query = new \DbQuery();
        $query->select('SUM(ph.`change`)');
        $query->from(self::$definition['table'], 'ph');
        $query->where('`id_customer` = ' . (int)$id_customer);
        if ($startDate && $endDate) {
            $query->where("`date_add` BETWEEN '{$startDate}' AND '{$endDate}'");
        }
        if ($condition_type == 'points') {
            $query->where("`id_action_order` = 0"); // We only wanna normal actions
        }
        elseif ($condition_type == 'coins') {
            $query->where("`id_action` = 0"); // We only wanna actionOrders
        }
        return \Db::getInstance()->getValue($query);
    }
}