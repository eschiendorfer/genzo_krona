<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Player.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/PlayerHistory.php';

class Action extends \ObjectModel {

    public $id;
    public $id_action;
    public $module;
    public $key;
    public $points_change;
    public $execution_type;
    public $execution_max;
    public $active;
    public $title;
    public $message;

    public static $definition = array(
        'table'     => "genzo_krona_action",
        'primary'   => 'id_action',
        'multilang' => true,
        'multishop' => true,
        'fields' => array(
            'module'         => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'key'            => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'points_change'  => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'execution_type' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'execution_max'  => array('type' => self::TYPE_BOOL, 'validate' => 'isInt'),
            'active'         => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'title'          => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true),
            'message'        => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true),
        )
    );

    public function __construct($id_action = null, $id_lang = null, $id_shop = null) {

        parent::__construct($id_action, $id_lang, $id_shop);

        \Shop::addTableAssociation(self::$definition['table'], array('type' => 'shop'));

    }


    // Queries
    public static function getIdAction($module, $action) {
        $module = pSQL($module);
        $action = pSQL($action);

        $query = new \DbQuery();
        $query->select('id_action');
        $query->from(self::$definition['table']);
        $query->where("`module` = '{$module}'");
        $query->where("`key` = '{$action}'");
        return \Db::getInstance()->getValue($query);
    }

    public static function getAllActions ($filters = null) {

        $id_lang = \Context::getContext()->language->id;

        (\Shop::isFeatureActive()) ? $ids_shop = \Shop::getContextListShopID() : $ids_shop = null;

        $query = new \DbQuery();
        $query->select('*');
        $query->from(self::$definition['table'], 'a');
        $query->innerJoin(self::$definition['table'].'_lang', 'l', 'l.`id_action` = a.`id_action`');
        $query->where('l.`id_lang`='.$id_lang);
        if ($ids_shop) {
            $query->innerJoin(self::$definition['table'] . '_shop', 's', 's.`id_action` = a.`id_action`');
            $query->where('s.`id_shop` IN (' . implode(',', array_map('intval', $ids_shop)) . ')');
        }
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        $query->groupBy('a.`id_action`');

        return \Db::getInstance()->ExecuteS($query);
    }

    public static function checkIfActionIsActive($module_name, $action_name, $id_shop = null) {
        $module_name = pSQL($module_name);
        $action_name = pSQL($action_name);

        if (\Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') AND !$id_shop) {
            $id_shop = \Context::getContext()->shop->id;
        }

        $query = new \DbQuery();
        $query->select('active');
        $query->from(self::$definition['table'], 'a');
        if ($id_shop) {
            $query->innerJoin(self::$definition['table'].'_shop', 's', 's.`id_action` = a.`id_action`');
            $query->where('s.`id_shop` = ' . (int)$id_shop);
        }
        $query->where("`module` = '{$module_name}'");
        $query->where("`key` = '{$action_name}'");
        return \Db::getInstance()->getValue($query);
    }

    /**
     * @var Action $action
     * @param $id_customer
     */
    public static function getPlayerExecutionTimes($action, $id_customer) {

        $execution_times = 0;

        // How many times was the action already executed for the defined time span?

        $endDate = date('Y-m-d 23:59:59');

        if ($action->execution_type == 'per_day') {
            $startDate = date('Y-m-d 00:00:00');
        }
        elseif ($action->execution_type == 'per_month') {
            $startDate = date('Y-m-01 00:00:00');
        }
        elseif ($action->execution_type == 'per_year') {
            $startDate = date('Y-01-01 00:00:00');
        }
        else {
            $startDate = null;
            $endDate = null; // This is max_per_lifetime or unlimited
        }

        $execution_times = PlayerHistory::countActionByPlayer($id_customer, $action->id, $startDate, $endDate);

        return $execution_times;

    }

    // Ajax
    public static function triggerPageVisit($id_customer) {

        $id_customer = (int)$id_customer;

        $hook = array(
            'module_name' => 'genzo_krona',
            'action_name' => 'page_visit',
            'id_customer' => $id_customer,
        );

        \Hook::exec('ActionExecuteKronaAction', $hook);
    }

    // CronJob
    public static function executeNewsletterCron() {

        $id_action = self::getIdAction('genzo_krona', 'newsletter');
        $action = new Action($id_action);

        // Check if there is a need to execute the newsletter action
        if ($action->execution_type == 'unlimited' OR $action->execution_type == 'per_day' OR $action->execution_type == 'per_lifetime') {
            $execution = true;
        }
        elseif ($action->execution_type == 'per_month') {
            (date('j') === '1') ? $execution = true : $execution = false;
        }
        elseif ($action->execution_type == 'per_year') {
            (date('z') === '0') ? $execution = true : $execution = false;
        }
        else {
            $execution = false;
        }

        // Make the execution
        if ($execution) {

            $players = Player::getAllPlayers();

            foreach ($players as $player) {
                $id_customer = $player['id_customer'];
                $customer = new \Customer($id_customer);

                if ($customer->newsletter) {
                    $hook = array(
                        'module_name' => 'genzo_krona',
                        'action_name' => 'newsletter',
                        'id_customer' => $id_customer,
                    );

                    \Hook::exec('ActionExecuteKronaAction', $hook);
                }
            }
        }
    }

}