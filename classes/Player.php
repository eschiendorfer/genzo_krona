<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

require_once _PS_MODULE_DIR_ . 'genzo_krona/genzo_krona.php';

class Player extends \ObjectModel {

    public $id_customer;
    public $pseudonym;
    public $display_name;
    public $avatar;
    public $avatar_full;
    public $points;
    public $coins;
    public $total;
    public $loyalty;
    public $loyalty_expire;
    public $active;
    public $banned;
    public $notification;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => "genzo_krona_player",
        'primary' => 'id_customer',
        'multilang' => false,
        'fields' => array(
            'id_customer'       => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'pseudonym'         => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'avatar'            => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'points'            => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'coins'             => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'loyalty'           => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'loyalty_expire'    => array('type' => self::TYPE_DATE),
            'active'            => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'banned'            => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'notification'      => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'date_add'          => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
            'date_upd'          => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
        )
    );

    public function __construct($id_customer = null) {

        parent::__construct($id_customer);

        if ($id_customer) {

            $context = \Context::getContext();

            if (\Configuration::get('krona_gamification_active', null, $context->shop->id_shop_group, $context->shop->id)) {

                $total = \Configuration::get('krona_gamification_total', null, $context->shop->id_shop_group, $context->shop->id);

                if ($total == 'points_coins') {
                    $this->total = $this->points + $this->coins;
                } elseif ($total == 'points') {
                    $this->total = $this->points;
                } elseif ($total == 'coins') {
                    $this->total = $this->coins;
                }

                $id_shop_group = \Context::getContext()->shop->id_shop_group;
                $id_shop = \Context::getContext()->shop->id_shop;

                if (\Configuration::get('krona_pseudonym', null, $id_shop_group, $id_shop) && $this->pseudonym) {
                    $this->display_name = $this->pseudonym;
                }
                else {
                    $this->display_name = self::getDisplayName($this->id_customer);
                }

                if (\Configuration::get('krona_avatar', null, $id_shop_group, $id_shop)) {
                    $this->avatar_full = _MODULE_DIR_ . 'genzo_krona/views/img/avatar/' . $this->avatar . '?=' . strtotime($this->date_upd);
                }

            } else {
                $this->total = 0;
            }
        }
    }

    public function delete() {

        parent::delete();

        $histories = PlayerHistory::getHistoryByPlayer($this->id);
        foreach ($histories as $history) {
            $his = new PlayerHistory($history['id_history']);
            $his->delete();
        }

        \Db::getInstance()->delete(self::$definition['table'].'_history', 'id_customer='.$this->id);
        \Db::getInstance()->delete(self::$definition['table'].'_level', 'id_customer='.$this->id);
    }

    public static function getAllPlayers($filters = null, $pagination = null, $order = null) {

        $context = \Context::getContext();

        // Multistore Handling
        (\Shop::isFeatureActive()) ? $ids_shop = \Shop::getContextListShopID() : $ids_shop = null;

        // Gamification Total
        if (\Configuration::get('krona_gamification_active', null, $context->shop->id_shop_group, $context->shop->id)) {
            $total = \Configuration::get('krona_gamification_total', null, $context->shop->id_shop_group, $context->shop->id);
        }
        else {
            $total = null;
        }

        $query = new \DbQuery();
        $query->select('p.*');
        if ($ids_shop) {
            $query->select('c.id_shop');
        }
        if ($total == 'points_coins') {
            $query->select('p.points+p.coins AS total');
        }
        elseif ($total == 'points') {
            $query->select('p.points AS total');
        }
        elseif ($total == 'coins') {
            $query->select('p.coins AS total');
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

        $players = \Db::getInstance()->ExecuteS($query);

        foreach ($players as &$player) {
            if (\Configuration::get('krona_pseudonym') AND $player['pseudonym']) {
                $player['display_name'] = $player['pseudonym'];
            }
            else {
                $player['display_name'] = self::getDisplayName($player['id_customer']);
            };
        }

        return $players;

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

            $player = new Player($id_customer);
            $player->id_customer = $id_customer;
            $player->points = 0;
            $player->coins = 0;
            $player->loyalty = 0;
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

    public static function importPlayer($id_customer) {

        $id_customer = (int)$id_customer;

        Player::createPlayer($id_customer);

        $import_points = (int)\Tools::getValue('import_points');
        $import_orders = (bool)\Tools::getValue('import_orders');

        // Handling Core Loyalty Points
        if ($import_points > 0) {
            if (\Module::isInstalled('loyalty')) {

                include_once _PS_MODULE_DIR_ . 'loyalty/classes/LoyaltyModule.php';
                include_once _PS_MODULE_DIR_ . 'loyalty/classes/LoyaltyStateModule.php';

                $points = \LoyaltyModule\LoyaltyModule::getPointsByCustomer($id_customer);
                $coins_change = ceil($points * $import_points);

                Player::updateCoins($id_customer, $coins_change);
            }
        }

        // Handling old orders
        if ($import_orders) {

            $orders = \Order::getCustomerOrders($id_customer);
            $orders = array_reverse($orders);

            if (!empty($orders)) {

                $customer = new \Customer($id_customer);

                foreach ($orders as $order) {

                    if (!$order['id_order_state']) { break; }
                    $orderState = new \OrderState($order['id_order_state']);

                    if ($orderState->paid) {

                        // Check ActionOrder -> This is basically checking the currency
                        $id_actionOrder = ActionOrder::getIdActionOrderByCurrency($order['id_currency']);
                        $actionOrder = new ActionOrder($id_actionOrder);

                        // Get Total amount of the order
                        $order_amount = \Configuration::get('krona_order_amount', null, $customer->id_shop_group, $customer->id_shop);

                        if ($order_amount == 'total_wt') {
                            $total = $order['total_paid']; // Total with taxes
                        } elseif ($order_amount == 'total') {
                            $total = $order['total_paid_tax_excl'];
                        } elseif ($order_amount == 'total_products_wt') {
                            $total = $order['total_products_wt'];
                        } elseif ($order_amount == 'total_products') {
                            $total = $order['total_products'];
                        } else {
                            $total = $order['total_paid']; // Standard if nothing is set
                        }

                        // Check if coupons should be substracted (in total they are already substracted)
                        if (\Configuration::get('krona_order_coupon', null, $customer->id_shop_group, $customer->id_shop)) {
                            if ($order_amount == 'total_products_wt') {
                                $total = $total - $order['total_discounts_tax_incl'];
                            }
                            elseif ($order_amount == 'total_products') {
                                $total = $total - $order['total_discounts_tax_excl'];
                            }
                        }

                        // Check the rounding method -> near is standard
                        $order_rounding = \Configuration::get('krona_order_rounding', null, $customer->id_shop_group, $customer->id_shop);
                        if ($order_rounding == 'down') {
                            $coins_change = floor($total * $actionOrder->coins_change);
                        }
                        elseif ($order_rounding == 'up') {
                            $coins_change = ceil($total * $actionOrder->coins_change);
                        }
                        else {
                            $coins_change = round($total * $actionOrder->coins_change);
                        }

                        Player::updateCoins($id_customer, $coins_change);

                        $history = new PlayerHistory();
                        $history->id_customer = $id_customer;
                        $history->id_action_order = $id_actionOrder;
                        $history->url = \Context::getContext()->link->getPageLink('history');
                        $history->change = $coins_change;
                        $history->date_add = $order['date_add'];

                        // Handling lang fields for Player History
                        $ids_lang = \Language::getIDs();
                        $title = array();
                        $message = array();

                        foreach ($ids_lang as $id_lang) {

                            $title[$id_lang] = \Configuration::get('krona_order_title', $id_lang, $customer->id_shop_group, $customer->id_shop);
                            $message[$id_lang] = \Configuration::get('krona_order_message', $id_lang, $customer->id_shop_group, $customer->id_shop);

                            // Replace message variables
                            $search = array('{points}', '{reference}', '{amount}');

                            $total_currency = \Tools::displayPrice(\Tools::convertPrice($total, $order['id_currency']));

                            $replace = array($coins_change, $order['reference'], $total_currency);
                            $message[$id_lang] = str_replace($search, $replace, $message[$id_lang]);

                            $history->message[$id_lang] = pSQL($message[$id_lang]);
                            $history->title[$id_lang] = pSQL($title[$id_lang]);
                        }

                        $history->add(false);
                    }

                }
                PlayerLevel::updatePlayerLevel($customer, 'coins', 0);
            }
        }

    }

    private static function shortenWord($string) {
        $words = explode(" ", $string);
        $acronym = "";

        foreach ($words as $w) {
            if (!empty($w[0])) {
                $acronym .= $w[0] . '. ';
            }
        }

        return $acronym;
    }


    public static function updatePoints($id_customer, $points_change) {

        $id_customer = (int)$id_customer;
        $points_change = (int)$points_change;

        $player = new Player($id_customer);
        $player->points = $player->points + $points_change;

        $context = \Context::getContext();

        if (\Configuration::get('krona_loyalty_active', null, $context->shop->id_shop_group, $context->shop->id)) {

            $total = \Configuration::get('krona_loyalty_total', null, $context->shop->id_shop_group, $context->shop->id);

            if ($total == 'points_coins' OR $total == 'points') {
                $player->loyalty = $player->loyalty + $points_change;
            }
        }

        $player->notification = $player->notification + 1;

        $player->update();
        return true;
    }

    public static function updateCoins($id_customer, $coins_change) {

        $id_customer = (int)$id_customer;
        $coins_change = (int)$coins_change;

        $player = new Player($id_customer);
        $player->coins = $player->coins + $coins_change;

        $context = \Context::getContext();

        if (\Configuration::get('krona_loyalty_active', null, $context->shop->id_shop_group, $context->shop->id)) {

            $total = \Configuration::get('krona_loyalty_total', null, $context->shop->id_shop_group, $context->shop->id);

            if ($total == 'points_coins' OR $total == 'coins') {
                $player->loyalty = $player->loyalty + $coins_change;
            }
        }

        $player->notification = $player->notification + 1;

        $player->update();
        return true;

    }

    // expire type can be today or last_order
    public static function updateExpireLoyalty($expire_type, $expire_days) {
        if ($expire_type == 'today') {
            $sql = 'UPDATE '._DB_PREFIX_.self::$definition['table'].'
                    SET loyalty_expire = NOW() + INTERVAL '.$expire_days.' DAY';
            \Db::getInstance()->execute($sql);
        }
        elseif ($expire_type == 'last_order') {
            $players = self::getAllPlayers();

            foreach ($players as $player) {
                $playerObj = new Player($player['id_customer']);
                $playerObj->loyalty_expire = self::getExpireDateByLastOrder($player['id_customer'], $expire_days);
                $playerObj->update();
            }
        }
    }

    private static function getExpireDateByLastOrder($id_customer, $expire_days) {
        $query = new \DbQuery();
        $query->select('MAX(date_add)');
        $query->from('orders');
        $query->where('id_customer = ' . $id_customer);
        $query->where('valid = 1');
        $last_order = \Db::getInstance()->getValue($query);

        $expire_date = date("Y-m-d H:i:s", strtotime($last_order." + {$expire_days} days"));

        return $expire_date;
    }

    public static function cronExpireLoyalty() {

        foreach (\Shop::getCompleteListOfShopsID() as $id_shop) {
            $id_shop_group = \Shop::getGroupFromShop($id_shop);
            if (\Configuration::get('krona_loyalty_expire', null, $id_shop_group, $id_shop)) {
                $sql = 'UPDATE '._DB_PREFIX_.self::$definition['table'].' AS p
                        INNER JOIN '._DB_PREFIX_.'customer AS c ON c.id_customer=p.id_customer
                        SET p.loyalty = 0
                        WHERE loyalty_expire < NOW() AND c.id_shop='.$id_shop;

                \Db::getInstance()->execute($sql);
            }
        }


    }

    public static function getRank($id_customer) {

        $id_customer = (int)$id_customer;

        $context = \Context::getContext();

        (\Shop::isFeatureActive()) ? $id_shop = $context->shop->id_shop :$id_shop = null;

        $method = \Configuration::get('krona_gamification_total', null, $context->shop->id_shop_group, $context->shop->id);;

        $query = new \DbQuery();

        if ($method == 'points_coins') {
            $query->select('points+coins AS total');
        } elseif ($method == 'points') {
            $query->select('points');
        } elseif ($method == 'coins') {
            $query->select('coins');
        }

        $query->select('points');
        $query->from(self::$definition['table']);
        $query->where('`id_customer` = ' . (int)$id_customer);
        $code = \Db::getInstance()->getValue($query);

        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from(self::$definition['table'], 'p');
        if ($id_shop) {
            $query->innerJoin('customer', 'c', 'c.id_customer = p.id_customer');
            $query->where('id_shop='.$id_shop);
        }
        if ($method == 'points_coins') {
            $query->where('points+coins > ' . $code);
        } elseif ($method == 'points') {
            $query->where('points > ' . $code);
        } elseif ($method == 'coins') {
            $query->where('coins > ' . $code);
        }
        return \Db::getInstance()->getValue($query)+1;

    }

    public static function getPseudonym($id_customer) {

        $pseudonym = '';

        $customer = new \Customer($id_customer);

        if (\Configuration::get('krona_pseudonym', null, $customer->id_shop_group, $customer->id_shop)) {
            $query = new \DbQuery();
            $query->select('pseudonym');
            $query->from(self::$definition['table']);
            $query->where('`id_customer` = ' . (int)$id_customer);
            $pseudonym = \Db::getInstance()->getValue($query);
        }

        if (!$pseudonym) {
            $pseudonym = self::getDisplayName($id_customer);
        }
        return $pseudonym;

    }

    public static function getDisplayName($id_customer) {

        $customer = new \Customer($id_customer);

        $display_name =  \Configuration::get('krona_display_name', null, $customer->id_shop_group, $customer->id_shop);


        if ($display_name == 1) {
            $pseudonym = $customer->firstname . ' ' . $customer->lastname; // John Doe
        }
        elseif ($display_name == 2) {
            $pseudonym = $customer->firstname . ' ' . self::shortenWord($customer->lastname); // John D.
        }
        elseif ($display_name == 3) {
            $pseudonym = self::shortenWord($customer->firstname) . ' ' . $customer->lastname; // J. Doe
        }
        elseif ($display_name == 4) {
            $pseudonym = self::shortenWord($customer->firstname . ' ' . $customer->lastname); // J. D.
        }
        elseif ($display_name == 5) {
            $pseudonym = $customer->firstname; // John
        }
        else {
            $pseudonym = 'No name';
        }

        return $pseudonym;

    }

}