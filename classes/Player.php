<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

class Player extends \ObjectModel {
    public $id_customer;
    public $pseudonym;
    public $avatar;
    public $points;
    public $active;
    public $banned;
    public $date_add;
    public $date_upd;



    public static $definition = array(
        'table' => "genzo_krona_player",
        'primary' => 'id_customer',
        'multilang' => false,
        'fields' => array(
            'id_customer'        => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'pseudonym'        => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'avatar'        => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'points'        => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'active'        => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'banned'        => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add'    => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
            'date_upd'    => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
        )
    );

    public static function getAllPlayers($filters = null, $pagination = null, $order = null) {

        // Multistore Handling
        (\Shop::isFeatureActive()) ? $ids_shop = \Shop::getContextListShopID() :$ids_shop = null;

        $query = new \DbQuery();
        if ($ids_shop) {
            $query->select('p.*, c.id_shop');
        }
        else {
            $query->select('p.*');
        }
        $query->from(self::$definition['table'], 'p');
        if ($ids_shop) {
            $query->innerJoin('customer', 'c', 'p.id_customer = c.id_customer');
            $query->where('c.`id_shop` IN (' . implode(',', array_map('intval', $ids_shop)) . ')');
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

        if ($order) {
            (!empty($order['alias'])) ? $alias = $order['alias'].'.' : $alias = '';
            $query->orderBy("{$alias}`{$order['order_by']}` {$order['order_way']}");
        }

        return \Db::getInstance()->ExecuteS($query);
    }

    public static function getTotalPlayers($filters = null) {

        (\Shop::isFeatureActive()) ? $ids_shop = \Shop::getContextListShopID() :$ids_shop = null;

        $query = new \DbQuery();
        $query->select('Count(*)');
        $query->from(self::$definition['table'], 'p');
        if ($ids_shop) {
            $query->innerJoin('customer', 'c', 'p.id_customer = c.id_customer');
            $query->where('c.`id_shop` IN (' . implode(',', array_map('intval', $ids_shop)) . ')');
        }

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        return \Db::getInstance()->getValue($query);
    }

    public static function checkIfPlayerExits($id_customer) {

        $id_customer = (int)$id_customer;

        // Check if customer(!) exits
        if(!\Customer::customerIdExistsStatic($id_customer)) {
            return false;
        }
        else {
            $query = new \DbQuery();
            $query->select('Count(*)');
            $query->from(self::$definition['table']);
            $query->where('`id_customer` = ' . $id_customer);
            return \Db::getInstance()->getValue($query);
        }
    }

    public static function checkIfPlayerIsActive($id_customer) {
        $id_customer = (int)($id_customer);

        $query = new \DbQuery();
        $query->select('active');
        $query->from(self::$definition['table']);
        $query->where('`id_customer` = ' . $id_customer);
        return  \Db::getInstance()->getValue($query);
    }

    public static function checkIfPlayerIsBanned($id_customer) {
        $id_customer = (int)($id_customer);

        $query = new \DbQuery();
        $query->select('banned');
        $query->from(self::$definition['table']);
        $query->where('`id_customer` = ' . $id_customer);
        return  \Db::getInstance()->getValue($query);
    }

    public static function createPlayer($id_customer) {

        $id_customer = (int)$id_customer;

        // Check if customer(!) exits
        if(!\Customer::customerIdExistsStatic($id_customer)) {
            return 'Customer not found!';
        }
        elseif (self::checkIfPlayerExits($id_customer)) {
            return 'Player already exists!';
        }
        else {
            $customer = new \Customer($id_customer);

            $player = new Player();
            $player->id_customer = $id_customer;

            $display_name = \Configuration::get('krona_display_name', null, $customer->id_shop_group, $customer->id_shop);

            \Configuration::updateGlobalValue('mandi_superstar', 'ja_'.$display_name);

            if ($display_name == 1) {
                $player->pseudonym = $customer->firstname . ' ' . $customer->lastname; // John Doe
            }
            elseif ($display_name == 2) {
                $player->pseudonym = $customer->firstname . ' ' . self::shortenWord($customer->lastname); // John D.
            }
            elseif ($display_name == 3) {
                $player->pseudonym = self::shortenWord($customer->firstname) . ' ' . $customer->lastname; // J. Doe
            }
            elseif ($display_name == 4) {
                $player->pseudonym = self::shortenWord($customer->firstname . ' ' . $customer->lastname); // J. D.
            }
            elseif ($display_name == 5) {
                $player->pseudonym = $customer->firstname; // John
            }

            $player->points = 0;
            $player->avatar = 'no-avatar.jpg';
            $customer_active = \Configuration::get('krona_customer_active', null, $customer->id_shop_group, $customer->id_shop);
            $player->active = ($customer_active) ? 1 : 0;
            $player->add();

            // Add History
            $hook = array(
                'module_name' => 'genzo_krona',
                'action_name' => 'account_creation',
                'id_customer' => $id_customer,
            );

            \Hook::exec('ActionExecuteKronaAction', $hook);
        }
        return true;
    }

    private static function shortenWord($string) {
        $words = explode(" ", $string);
        $acronym = "";

        foreach ($words as $w) {
            $acronym .= $w[0].'. ';
        }

        return $acronym;
    }


    public static function updatePoints($id_customer, $points_change) {

        $id_customer = (int)$id_customer;
        $points_change = (int)$points_change;

        $player = new Player($id_customer);
        $player->points = $player->points + $points_change;
        $player->update();
        return true;
    }

    public static function getRank($id_customer) {
        $id_customer = (int)$id_customer;

        $points = self::getPoints($id_customer);

        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from(self::$definition['table']);
        $query->where('points > ' . $points);
        return \Db::getInstance()->getValue($query)+1;

    }

    public static function getPoints($id_customer) {
        $query = new \DbQuery();
        $query->select('points');
        $query->from(self::$definition['table']);
        $query->where('`id_customer` = ' . (int)$id_customer);
        return \Db::getInstance()->getValue($query);
    }

    public static function getAvatar($id_customer) {
        $id_customer = (int)$id_customer;

        $player = new Player($id_customer);
        $image = $player->avatar.'?='.$player->date_upd;

        return _MODULE_DIR_.'genzo_krona/views/img/avatar/'.$image;
    }

    public static function getPseudonym($id_customer) {
        $query = new \DbQuery();
        $query->select('pseudonym');
        $query->from(self::$definition['table']);
        $query->where('`id_customer` = ' . (int)$id_customer);
        return \Db::getInstance()->getValue($query);
    }

}