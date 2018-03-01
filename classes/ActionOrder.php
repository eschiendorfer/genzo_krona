<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

class ActionOrder extends \ObjectModel {
    public $id;
    public $id_action_order;
    public $id_currency;
    public $currency;
    public $currency_iso;
    public $points_change;
    public $minimum_amount;
    public $active;

    public static $definition = array(
        'table'     => "genzo_krona_action_order",
        'primary'   => 'id_action_order',
        'multilang' => false,
        'fields' => array(
            'id_currency'  => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'points_change'  => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'minimum_amount'  => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'active'         => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
        )
    );


    public function __construct($id_order_action = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id_order_action, $id_lang, $id_shop);

        if ($id_order_action) {
            $currency = \Currency::getCurrency($this->id_currency);
            $this->currency = $currency['name'];
            $this->currency_iso = $currency['iso_code'];
        }

    }

    public static function getAllActionOrder($filters = null, $pagination = null, $order = null) {

        $query = new \DbQuery();
        $query->select('o.*, c.name');
        $query->from(self::$definition['table'], 'o');
        $query->innerJoin('currency', 'c', 'o.id_currency = c.id_currency');

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
            $query->orderBy('id_currency ASC');
        }

        return \Db::getInstance()->ExecuteS($query);

    }

    public static function getTotalActionOrder($filters = null) {

        $query = new \DbQuery();
        $query->select('o.id_action_order');
        $query->from(self::$definition['table'], 'o');
        $query->innerJoin('currency', 'c', 'o.id_currency = c.id_currency');

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        $actionOrders = \Db::getInstance()->ExecuteS($query);

        return count($actionOrders);

    }

    public static function getIdActionOrderByCurrency($id_currency) {
        $query = new \DbQuery();
        $query->select('id_action_order');
        $query->from(self::$definition['table']);
        $query->where('id_currency = ' . (int)$id_currency);
        return \Db::getInstance()->getValue($query);
    }




}