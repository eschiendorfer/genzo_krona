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

class ActionOrder extends \ObjectModel {
    public $id;
    public $id_action_order;
    public $id_currency;
    public $currency;
    public $currency_iso;
    public $coins_change;
    public $coins_change_referrer;
    public $coins_change_buyer;
    public $coins_change_max;
    public $coins_conversion;
    public $minimum_amount;
    public $active;

    public static $definition = array(
        'table'     => "genzo_krona_action_order",
        'primary'   => 'id_action_order',
        'multilang' => false,
        'multishop' => true,
        'fields' => array(
            'id_currency'           => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'coins_change'          => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'coins_change_referrer' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'coins_change_buyer'    => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'coins_change_max'      => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'coins_conversion'      => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'minimum_amount'        => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'active'                => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
        )
    );

    public function __construct($id_order_action = null, $id_lang = null, $id_shop = null) {

        parent::__construct($id_order_action, $id_lang, $id_shop);

        \Shop::addTableAssociation(self::$definition['table'], array('type' => 'shop'));

        if ($id_order_action) {
            $currency = \Currency::getCurrency($this->id_currency);
            $this->currency = $currency['name'];
            $this->currency_iso = $currency['iso_code'];
        }
    }

    // Database
    public static function getAllActionOrder($filters = null) {

        $query = new \DbQuery();
        $query->select('o.*, c.name');
        $query->from(self::$definition['table'], 'o');
        $query->innerJoin('currency', 'c', 'o.id_currency = c.id_currency');

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        return \Db::getInstance()->ExecuteS($query);

    }

    public static function getIdActionOrderByCurrency($id_currency) {
        $query = new \DbQuery();
        $query->select('id_action_order');
        $query->from(self::$definition['table']);
        $query->where('id_currency = ' . (int)$id_currency);
        return \Db::getInstance()->getValue($query);
    }


    // Helpers
    public static function checkCurrencies() {

        // This functions checks basically, if all currencies are in the action_order table
        $query = new \DbQuery();
        $query->select('id_currency');
        $query->from(self::$definition['table']);
        $ids_action_order = array_column(\Db::getInstance()->executeS($query), 'id_currency');

        $query = new \DbQuery();
        $query->select('id_currency');
        $query->from('currency');
        $query->where('deleted=0');
        $ids_currency = array_column(\Db::getInstance()->executeS($query), 'id_currency');

        $missing = array_diff($ids_currency, $ids_action_order); // Which currencies are missing in the module
        $redundant = array_diff($ids_action_order, $ids_currency); // Which currencies are redundant in the module

        if (!empty($missing)) {
            foreach ($missing as $id_currency) {
                $actionOrder = new ActionOrder();
                $actionOrder->id_currency = $id_currency;
                $actionOrder->coins_change = 1;
                $actionOrder->minimum_amount = 0;
                $actionOrder->active = 0;
                $actionOrder->add();
            }
        }

        if (!empty($redundant)) {
            foreach ($redundant as $id_currency) {
                $id_action_order = ActionOrder::getIdActionOrderByCurrency($id_currency);
                $actionOrder = new ActionOrder($id_action_order);
                $actionOrder->delete();
            }
        }
    }
}