<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

use KronaModule\Player;
use KronaModule\PlayerHistory;
use KronaModule\PlayerLevel;
use KronaModule\Action;
use KronaModule\ActionOrder;

class AdminGenzoKronaPlayersController extends ModuleAdminController {

    /* @var $module Genzo_Krona */
    public $module;

    /* @var $object Player */
    protected $object;

    private $is_loyalty;
    private $is_gamification;
    private $loyalty_total;
    private $gamification_total;
    private $total_name;
    private $loyalty_name;

    public function __construct() {

        $this->module = 'genzo_krona';
        $this->bootstrap = true;
        $this->className = 'KronaModule\Player';
        $this->table = 'genzo_krona_player';
        $this->identifier = 'id_customer';
        $this->lang = false;
        $this->allow_export = true;

        $this->_select = 'c.`firstname`, c.`lastname` ';
        $this->_join = 'INNER JOIN '._DB_PREFIX_.'customer AS c ON c.id_customer = a.id_customer';

        $fields_list['id_customer'] = array(
            'title' => 'ID',
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'filter_type' => 'int',
        );

        $fields_list['firstname'] = array(
            'title' => $this->l('Firstname'),
            'align' => 'left',
            'filter_type' => 'string',
            'filter_key' => 'c!firstname'
        );

        $fields_list['lastname'] = array(
            'title' => $this->l('Lastname'),
            'align' => 'left',
            'filter_type' => 'string',
            'filter_key' => 'c!lastname'
        );

            $fields_list['pseudonym'] = array(
                'title' => $this->l('Pseudonym'),
                'align' => 'left',
            );

        $fields_list['points'] = array(
            'title' => $this->l('Points'),
            'class' => 'fixed-width-xs',
            'align' => 'left',
        );

        $fields_list['coins'] = array(
            'title' => $this->l('Coins'),
            'class' => 'fixed-width-xs',
            'align' => 'left',
        );

        $fields_list['total'] = array(
            'title' => $this->total_name,
            'class' => 'fixed-width-xs',
            'align' => 'left',
        );

        $fields_list['loyalty'] = array(
            'title' => $this->loyalty_name,
            'class' => 'fixed-width-xs',
            'align' => 'left',
        );

        $fields_list['active'] = array(
            'title' => $this->l('Active'),
            'active' => 'status',
            'class' => 'fixed-width-xs',
            'align' => 'center',
            'type'  => 'bool',
            'filter_type' => 'int',
        );
        $fields_list['banned'] = array(
            'title' => $this->l('Banned'),
            'active' => 'toggleBanned',
            'class' => 'fixed-width-xs',
            'align' => 'center',
            'type'  => 'bool',
            'filter_type' => 'int',
        );

        $this->fields_list = $fields_list;
        $this->actions = array('edit');
        $this->_defaultOrderBy = 'total';
        $this->_orderWay = 'DESC';
        $this->bulk_actions = [];

        parent::__construct();

    }

    public function init() {

        parent::init();

        // Configuration
        $this->is_loyalty = Configuration::get('krona_loyalty_active');
        $this->is_gamification = Configuration::get('krona_gamification_active');
        $this->loyalty_total = Configuration::get('krona_loyalty_total');
        $this->gamification_total = Configuration::get('krona_gamification_total');

        $this->total_name = Configuration::get('krona_total_name', $this->context->language->id);
        $this->loyalty_name = Configuration::get('krona_loyalty_name', $this->context->language->id);
    }

    public function initContent() {

        // Some Basic Display functions
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();

        // Optional Display
        $stats = false;

        if (Shop::isFeatureActive() && (Shop::getContext() == Shop::CONTEXT_GROUP || Shop::getContext() == Shop::CONTEXT_ALL)) {
            $this->errors[] = $this->l('Please chose a specific shop, to show players.');
        }
        else {
            if (Tools::isSubmit('updatePlayerHistory') || $this->display=='updatePlayerHistory') {
                $this->content = $this->renderPlayerHistoryForm();
            }
            elseif (Tools::isSubmit('addCustomAction') || $this->display=='customActionForm') {
                $this->content = $this->generateFormCustomAction();
            }
            elseif (
                $this->display=='edit' ||
                Tools::isSubmit('deletePlayerLevel') ||
                Tools::isSubmit('deletePlayerHistory') ||
                Tools::isSubmit('savePlayerHistory') ||
                Tools::isSubmit('saveCustomAction')
            ) {
                $this->identifier = 'id_customer';
                $this->className = 'KronaModule\Player';

                if (!$this->loadObject()) {
                    return false;
                }

                $this->content = $this->renderPlayerForm();
                $this->content.= $this->generateListPlayerLevels();
                $this->content.= $this->generateListPlayerHistory();
            }
            else {
                $this->content = $this->renderPlayersList();
                $stats = $this->getStats();
            }
        }

        // This are the real smarty variables
        $this->context->smarty->assign(
            array(
                'stats'     => $stats,
                'content'   => $this->content,
                'tab'       => 'Players',
                'gamification_active'  => $this->is_gamification,
                'loyalty_active'  => $this->is_loyalty,
                'loyalty_name'  => $this->loyalty_name,
                'import'  => Configuration::get('krona_import_customer'),
                'dont'    => Configuration::get('krona_dont_import_customer'),
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            )
        );

        $tpl = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'genzo_krona/views/templates/admin/main.tpl');

        $this->context->smarty->assign(array(
            'content' => $tpl, // This seems to be anything inbuilt. It's just chance that we both use content as an assign variable
        ));

        return true;

    }

    public function initToolbar() {
        parent::initToolbar();
        unset( $this->toolbar_btn['new'] ); // To remove the add button
    }

    public function setMedia() {

        parent::setMedia();

        $this->addJS(array(
            _MODULE_DIR_.'genzo_krona/views/js/admin-krona.js',
        ));

        $this->addCSS(array(
            _MODULE_DIR_.'genzo_krona/views/css/admin-krona.css',
        ));

    }

    public function postProcess() {

        // To delete multiple players with bulk select
        if (Tools::isSubmit('submitBulkdeletegenzo_krona_player')) {
            $ids_customer = Tools::getValue('genzo_krona_playerBox');

            foreach ($ids_customer as $id_customer) {
                $player = new Player($id_customer);
                $player->delete();
            }
        }

        if (Tools::isSubmit('savePlayer')) {
            if (Configuration::get('krona_avatar')) {
                $id_customer = (int)Tools::getValue('id_customer');
                $player = new Player($id_customer);
                $player->active = Tools::getValue('active');
                $player->banned = Tools::getValue('banned');
                $player->pseudonym = Tools::getValue('pseudonym');
                $player->avatar = ($this->module->uploadAvatar($player->id_customer)) ? $player->id_customer . '.jpg' : $player->avatar;
                $player->update();
            }
        }
        elseif (Tools::isSubmit('saveCustomAction')) {

            $ids_lang = Language::getIDs();

            // Check inputs
            $id_customer = (int)Tools::getValue('id_customer');
            $type = Tools::getValue('action_type');

            $history = new PlayerHistory();
            $history->id_customer = $id_customer;
            $history->id_action = ($type == 'action') ? (int)Tools::getValue('id_action') : 0;
            $history->id_action_order = ($type == 'order') ? (int)Tools::getValue('id_action_order') : 0;
            $history->points = (int)Tools::getValue('points');
            $history->coins = (int)Tools::getValue('coins');
            $history->loyalty = (int)Tools::getValue('loyalty');

            if ($type == 'custom') {
                foreach ($ids_lang as $id_lang) {

                    // We need to check if there is really a title
                    if (Tools::getValue('title_' . $id_lang)!='') {
                        $history->title[$id_lang] = pSQL(Tools::getValue('title_' . $id_lang));
                    }

                    // We need to check if there is really a message
                    if (Tools::getValue('message_' . $id_lang)!='') {
                        $history->message[$id_lang] = pSQL(Tools::getValue('message_' . $id_lang));
                    }
                }
            }
            elseif ($type == 'action') {
                $action = new Action($history->id_action);
                $history->title = $action->title;
                $history->message = $action->message;

                foreach ($history->message as $id_lang => $message) {
                    $history->message[$id_lang] = str_replace('{points}', $history->points, $message);
                }
            }
            elseif ($type == 'order') {

                foreach ($ids_lang as $id_lang) {
                    $history->title[$id_lang] = Configuration::get('krona_order_title');

                    $message = Configuration::get('krona_order_message');
                    $history->message[$id_lang] = str_replace('{coins}', $history->coins, $message);
                }
            }

            if ($type == 'custom' && empty($history->title)) {
                $this->errors[] = $this->l('Please fill in title');
            }

            if ($type == 'custom' && empty($history->message)) {
                $this->errors[] = $this->l('Please fill in message');
            }

            if (!$history->points && !$history->coins && !$history->loyalty) {
                $this->errors[] = $this->l('Please fill in (at least one) a value for points, coins or loyalty.');
            }

            if (empty($this->errors)) {

                // Check if it's a penalty
                if ($history->loyalty < 0) {
                    $this->module->convertLoyaltyPointsToCoupon($id_customer, abs($history->loyalty), true);
                    $history->force_display = $history->loyalty;
                    $history->loyalty = 0;
                }

                $history->add(true);

                Player::updatePlayerLevels($id_customer);

                $this->confirmations[] = $this->l('The player action was sucessfully saved.');

                return true;
            }
            else {

                $this->display = 'customActionForm';

                $this->fields_value = array(
                    'id_customer' => $id_customer,
                    'title' => $history->title,
                    'message' => $history->message,
                    'action_type' => $type,
                    'points' => $history->points,
                    'coins' => $history->coins,
                    'loyalty' => $history->loyalty,
                );

                return $history;
            }
        }
        elseif (Tools::isSubmit('deletePlayerHistory') || Tools::isSubmit('savePlayerHistory')) {
            // Check inputs
            $id_history = (int)Tools::getValue('id_history');
            $history = new PlayerHistory($id_history);

            if (Tools::isSubmit('savePlayerHistory')) {

                foreach (Language::getIDs() as $id_lang) {
                    $history->title[$id_lang] = Tools::getValue('title_'.$id_lang);
                    $history->message[$id_lang] = Tools::getValue('message_'.$id_lang);
                }

                $history->url = Tools::getValue('url');
                $history->points = (int)Tools::getValue('points');
                $history->coins = (int)Tools::getValue('coins');
                $history->loyalty = (int)Tools::getValue('loyalty');
                $history->loyalty_used = (int)Tools::getValue('loyalty_used');
                $history->loyalty_expired = (int)Tools::getValue('loyalty_expired');

                if ($history->loyalty < ($history->loyalty_used+$history->loyalty_expired)) {
                    $this->errors[] = $this->l('Loyalty used + Loyalty expired must not exceed Loyalty');
                    $this->fields_value = json_decode(json_encode($history), true);
                }
                elseif ($history->update()) {
                    $this->confirmations[] = $this->l('The player history was changed!');
                }

                if (!empty($this->errors)) {
                    $this->display = 'updatePlayerHistory'; // Make sure the form stays
                }
            }
            elseif (Tools::isSubmit('deletePlayerHistory')) {
                if ($history->delete()) {
                    $this->confirmations[] = $this->l('The player history was deleted!');
                }
            }

            return true;
        }
        elseif (Tools::isSubmit('deletePlayerLevel')) {

            $id_player_level = (int)Tools::getValue('id_player_level');
            $playerLevel = new Playerlevel($id_player_level);
            $playerLevel->delete();

            if (empty($this->errors)) {
                $this->confirmations[] = $this->l('The Player Level was deleted. Keep in my mind, that you have to remove any kind of reward manually.');
            }
        }
        elseif (Tools::isSubmit('importCustomers')) {

            $customers = Customer::getCustomers(true);

            foreach ($customers as $customer) {
                Player::importPlayer($customer['id_customer']);
            }
            // Multistore handling
            foreach (Shop::getContextListShopID() as $id_shop) {
                Configuration::updateValue('krona_import_customer', 1, false, Shop::getGroupFromShop($id_shop), $id_shop);
            }

            $this->confirmations[] = $this->l('Player were sucessfully imported.');
        }
        elseif (Tools::isSubmit('dontImportCustomers')) {

            // No multistore handling
            foreach (Shop::getShops() as $shop) {
                Configuration::updateValue('krona_dont_import_customer', 1, false, $shop['id_shop_group'], $shop['id_shop']);
            }
            Configuration::updateGlobalValue('krona_dont_import_customer', 1);
            $this->confirmations[] = $this->l('You won\'t see this tab again.');
        }
        elseif (Tools::isSubmit('toggleBanned'.$this->table)) {
            $this->module->saveToggle($this->table, 'id_customer', 'banned');
            $redirect = $this->context->link->getAdminLink('AdminGenzoKronaPlayers', true).'&conf=5';
            Tools::redirectAdmin($redirect); // We have to redirect - otherwise the toggle happens on every new page hit
        }
        elseif (Tools::isSubmit('toggleActivePlayerLevel')) {
            $this->module->saveToggle('genzo_krona_player_level', 'id_player_level', 'active');
            $redirect = $this->context->link->getAdminLink('AdminGenzoKronaPlayers', true).'&conf=5&updategenzo_krona_player&id_customer='.Tools::getValue('id_customer');
            Tools::redirectAdmin($redirect); // We have to redirect - otherwise the toggle happens on every new page hit
        }

        return parent::postProcess();
    }


    // Lists
    public function renderPlayersList() {

        if ($this->gamification_total == 'points_coins') {
            $this->_select .= ', SUM(pl.`points`+pl.`coins`) AS total ';
        }
        elseif ($this->gamification_total == 'points') {
            $this->_select .= ', SUM(pl.`points`) AS total ';
        }
        elseif ($this->gamification_total == 'coins') {
            $this->_select .= ', SUM(pl.`coins`) AS total ';
        }

        $this->_select .= ', SUM(pl.points) AS points, SUM(pl.coins) AS coins, SUM(pl.loyalty-pl.loyalty_used-pl.loyalty_expired) AS loyalty';
        $this->_join .= ' LEFT JOIN '._DB_PREFIX_.'genzo_krona_player_history AS pl ON pl.id_customer=a.id_customer';
        $this->_group = ' GROUP BY a.id_customer ';

        $fields_list['id_customer'] = array(
            'title' => 'ID',
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'filter_type' => 'int',
        );

        $fields_list['firstname'] = array(
            'title' => $this->l('Firstname'),
            'align' => 'left',
            'filter_type' => 'string',
            'filter_key' => 'c!firstname'
        );

        $fields_list['lastname'] = array(
            'title' => $this->l('Lastname'),
            'align' => 'left',
            'filter_type' => 'string',
            'filter_key' => 'c!lastname'
        );

        if ($this->is_gamification && Configuration::get('krona_pseudonym')) {
            $fields_list['pseudonym'] = array(
                'title' => $this->l('Pseudonym'),
                'align' => 'left',
            );
        }

        if (($this->is_loyalty AND $this->loyalty_total!='coins') OR ($this->is_gamification AND $this->gamification_total!='coins')) {
            $fields_list['points'] = array(
                'title' => $this->l('Points'),
                'class' => 'fixed-width-xs',
                'align' => 'left',
            );
        }

        if (($this->is_loyalty AND $this->loyalty_total!='points') OR ($this->is_gamification AND $this->gamification_total!='points')) {
            $fields_list['coins'] = array(
                'title' => $this->l('Coins'),
                'class' => 'fixed-width-xs',
                'align' => 'left',
            );
        }

        if ($this->is_gamification) {
            $fields_list['total'] = array(
                'title' => $this->total_name,
                'class' => 'fixed-width-xs',
                'align' => 'left',
                'search' => false,
            );
        }

        if ($this->is_loyalty) {
            $fields_list['loyalty'] = array(
                'title' => $this->loyalty_name,
                'class' => 'fixed-width-xs',
                'align' => 'left',
            );
        }

        $fields_list['active'] = array(
            'title' => $this->l('Active'),
            'active' => 'status',
            'class' => 'fixed-width-xs',
            'align' => 'center',
            'type'  => 'bool',
            'filter_type' => 'int',
        );

        $fields_list['banned'] = array(
            'title' => $this->l('Banned'),
            'active' => 'toggleBanned',
            'class' => 'fixed-width-xs',
            'align' => 'center',
            'type'  => 'bool',
            'filter_type' => 'int',
        );

        $this->fields_list = $fields_list;
        $this->actions = array('edit');
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            ),
        );
        $this->_defaultOrderBy = 'total';
        $this->_orderWay = 'DESC';

        if (Shop::isFeatureActive()) {
            $ids_shop = Shop::getContextListShopID();
            $this->_filter .= (' AND c.`id_shop` IN (' . implode(',', $ids_shop) . ') ');
        }

        return parent::renderList();
    }

    private function generateListPlayerLevels() {

        $id_customer = Tools::getValue('id_customer');

        $fields_list = array(
            'id_player_level' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'alias' => 'h',
                'filter_type' => 'int',
            ),
            'name' => array(
                'title' => $this->l('Level'),
                'align' => 'left',
            ),
            'active_until' => array(
                'title' => $this->l('Active until'),
                'align' => 'left',
            ),
            'achieved_last' => array(
                'title' => $this->l('Last achieved'),
                'align' => 'left',
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'active' => 'toggleActive',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'type'  => 'bool',
                'filter_type' => 'int',
            ),
        );

        $helper = new HelperList();
        $helper->table = 'PlayerLevel';
        $helper->shopLinkType = '';
        $helper->actions = array('delete');
        $helper->identifier = 'id_player_level';
        $helper->_pagination = [20,50,100];
        $helper->token = Tools::getAdminTokenLite($this->controller_name);

        // Index is very important for filtering on a sublist. Here we can set paramaters which can be used later with getValue
        $helper->currentIndex = $this->context->link->getAdminLink($this->controller_name, false) . '&id_customer='. $id_customer;

        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('filter').$helper->table) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $this->module->getFiltersFromList($fields_list, $helper->table);
        }

        if (Tools::isSubmit('submitReset'.$helper->table)) {
            foreach ($fields_list as $fieldName => $field) {
                unset($_POST[$helper->table.'Filter_'.$fieldName]);
                unset($_GET[$helper->table.'Filter_'.$fieldName]);
            }
            $filters = null;
        }

        // Pagination
        if ($pagination_used) {
            $pagination = $this->module->getPagination($helper->table);
        }
        elseif ($helper->_default_pagination) {
            $pagination['limit'] = $helper->_default_pagination;
            $pagination['offset'] = 0;
        }
        else {
            $pagination = null;
        }

        // OrderBy
        $order = array();
        if ($order_by_used AND Tools::getValue($helper->table.'Orderway')) {
            $order_by = Tools::getValue($helper->table.'Orderby');

            $order['order_by']  = $order_by;
            $order['order_way'] = Tools::getValue($helper->table.'Orderway');
            if (!empty($fields_list[$order_by]['alias'])) {
                $order['alias'] = $fields_list[$order_by]['alias'];
            }
        }

        // Set Final Settings
        $helper->listTotal = PlayerLevel::getAllPlayerLevelsTotal($id_customer, $filters);
        $helper->title = $this->l('Achieved Levels');

        $values = PlayerLevel::getAllPlayerLevels($id_customer, $filters, $pagination, $order);

        return $helper->generateList($values, $fields_list);
    }

    private function generateListPlayerHistory() {

        $id_customer = Tools::getValue('id_customer');

        $fields_list = array(
            'id_history' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'alias' => 'h',
                'filter_type' => 'int',
            ),
            'date_add' => array(
                'title' => $this->l('Date'),
                'align' => 'left',
                'type'  => 'date'
            ),
            'title' => array(
                'title' => $this->l('Action'),
                'align' => 'left',
            ),
            'change' => array(
                'title' => $this->total_name,
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_type' => 'int'
            ),
            'loyalty' => array(
                'title' => $this->loyalty_name,
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_type' => 'int'
            ),
            'url' => array(
                'title' => $this->l('Url'),
                'align' => 'left',
                'remove_onclick' => true,
            ),
        );

        $helper = new HelperList();
        $helper->table = 'PlayerHistory';
        $helper->shopLinkType = '';
        $helper->actions = array('edit', 'delete');
        $helper->identifier = 'id_history';
        $helper->_pagination = [20,50,100];
        $helper->token = Tools::getAdminTokenLite($this->controller_name);
        $helper->toolbar_btn = array(
            'new' =>
                array(
                    'desc' => $this->l('New Entry'),
                    'href' => $this->context->link->getAdminLink($this->controller_name, true) . '&addCustomAction' . '&id_customer='.$id_customer,
                ),
        );

        // Index is very important for filtering on a sublist. Here we can set paramaters which can be used later with getValues
        $helper->currentIndex = $this->context->link->getAdminLink($this->controller_name, false) . '&id_customer='. $id_customer;

        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('filter').$helper->table) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $this->module->getFiltersFromList($fields_list, $helper->table);
        }

        if (Tools::isSubmit('submitReset'.$helper->table)) {
            foreach ($fields_list as $fieldName => $field) {
                unset($_POST[$helper->table.'Filter_'.$fieldName]);
                unset($_GET[$helper->table.'Filter_'.$fieldName]);
            }
            $filters = null;
        }

        // Pagination
        if ($pagination_used) {
            $pagination = $this->module->getPagination($helper->table);
        }
        elseif ($helper->_default_pagination) {
            $pagination['limit'] = $helper->_default_pagination;
            $pagination['offset'] = 0;
        }
        else {
            $pagination = null;
        }

        // OrderBy
        $order = array();
        if ($order_by_used AND Tools::getValue($helper->table.'Orderway')) {
            $order_by = Tools::getValue($helper->table.'Orderby');

            $order['order_by']  = $order_by;
            $order['order_way'] = Tools::getValue($helper->table.'Orderway');
            if (!empty($fields_list[$order_by]['alias'])) {
                $order['alias'] = $fields_list[$order_by]['alias'];
            }
        }

        // Set Final Settings
        $helper->listTotal = PlayerHistory::getTotalHistoryByPlayer($id_customer, $filters);
        $helper->title = $this->l('Player History');

        $values = PlayerHistory::getHistoryByPlayer($id_customer, $filters, $pagination, $order);

        return $helper->generateList($values, $fields_list);
    }


    // Forms
    private function renderPlayerForm() {

        $inputs[] = array(
            'type' => 'hidden',
            'name' => 'id_customer'
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Active'),
            'name' => 'active',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Banned'),
            'name' => 'banned',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );

        $inputs[] = array(
            'type' => (Configuration::get('krona_pseudonym')) ? 'text' : 'hidden',
            'name' => 'pseudonym',
            'label' => $this->l('Pseudonym'),
        );

        $inputs[] = array(
            'type'         => 'html',
            'name'         => 'html_avatar',
            'html_content' => "<img src='{$this->object->avatar_full}' width='70' height='70' />",
        );

        $inputs[] = array(
            'type'  => 'file',
            'label' => 'Avatar',
            'name'  => 'avatar',
        );

        // We shouldn't change points this way, since it will not generate any history for the player. This will cause troubles, when checking points in levels.
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'points',
            'readonly' => true,
            'desc' => $this->l('If you want to change points, please add a custom action below.'),
            'label' => $this->l('Points'),
            'class'  => 'input fixed-width-sm',
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins',
            'readonly' => true,
            'desc' => $this->l('If you want to change coins, please add a custom action below.'),
            'label' => $this->l('Coins'),
            'class'  => 'input fixed-width-sm',
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'total',
            'readonly' => true,
            'desc' => $this->l('If you want to change total, please add a custom action below.'),
            'label' => $this->total_name,
            'class'  => 'input fixed-width-sm',
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'loyalty',
            'readonly' => true,
            'desc' => $this->l('If you want to change loyalty, please add a custom action below.'),
            'label' => $this->loyalty_name,
            'class'  => 'input fixed-width-sm',
        );

        $fields_form = array(
            'legend' => array(
                'title' => $this->l('Edit Player:') . ' ' . $this->object->display_name,
                'icon' => 'icon-cogs',
            ),
            'input' => $inputs,
            'submit' => array(
                'title' => $this->l('Save Player'),
                'class' => 'btn btn-default pull-right',
                'name'  => 'savePlayer',
            )
        );

        $this->submit_action = 'savePlayer';
        $this->fields_form = $fields_form;

        $this->fields_value = json_decode(json_encode($this->object), true);

        $this->tpl_form_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return parent::renderForm();
    }

    private function renderPlayerHistoryForm() {

        $inputs[] = array(
            'type' => 'hidden',
            'name' => 'id_history'
        );

        // We shouldn't change points this way, since it will not generate any history for the player. This will cause troubles, when checking points in levels.
        $inputs[] = array(
            'type'  => 'text',
            'lang'  => true,
            'name'  => 'title',
            'label' => $this->l('Title'),
        );
        $inputs[] = array(
            'type'  => 'textarea',
            'lang'  => true,
            'name'  => 'message',
            'label' => $this->l('Message'),
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'url',
            'label' => $this->l('Url'),
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'points',
            'suffix' => $this->l('Points'),
            'class'  => 'input fixed-width-sm',
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins',
            'suffix' => $this->l('Coins'),
            'class'  => 'input fixed-width-sm',
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'loyalty',
            'suffix' => $this->loyalty_name,
            'class'  => 'input fixed-width-sm',
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'loyalty_used',
            'suffix' => $this->loyalty_name.' '.$this->l('used'),
            'class'  => 'input fixed-width-sm',
        );

        if ($this->is_loyalty) {
            $inputs[] = array(
                'type' => 'text',
                'name' => 'loyalty_expired',
                'suffix' => $this->loyalty_name . ' ' . $this->l('expired'),
                'class' => 'input fixed-width-sm',
            );

            $fields_form = array(
                'legend' => array(
                    'title' => $this->l('Edit Action'),
                    'icon' => 'icon-cogs',
                ),
                'input' => $inputs,
                'submit' => array(
                    'name' => 'savePlayerHistory',
                    'title' => $this->l('Save Player History'),
                    'class' => 'btn btn-default pull-right',
                ),
            );
        }

        // Make sure the form is redirected to the player page
        self::$currentIndex .= '&id_customer='.Tools::getValue('id_customer');

        $this->submit_action = 'savePlayerHistory';

        $this->fields_form = $fields_form;

        // Fields value can already be filled, if the form is submitted with errors
        if (empty($this->fields_value)) {
            $this->fields_value = json_decode(json_encode(new PlayerHistory(Tools::getValue('id_history'))), true);
        }

        $this->tpl_form_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return parent::renderForm();
    }

    private function generateFormCustomAction() {

        $inputs[] = array(
            'type' => 'hidden',
            'name' => 'id_customer'
        );

        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Type'),
            'name' => 'action_type',
            'options' => array(
                'query' => array(
                    array('value' => 'custom', 'name' => $this->l('Custom')),
                    array('value' => 'action', 'name' => $this->l('Action')),
                    array('value' => 'order', 'name' => $this->l('Order')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'title',
            'label' => $this->l('Title'),
            'lang'  => true,
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->l('Message'),
            'name' => 'message',
            'lang' => true,
        );

        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Action'),
            'name' => 'id_action',
            'class' => 'chosen',
            'options' => array(
                'query' => Action::getAllActions(),
                'id' => 'id_action',
                'name' => 'title',
            ),
        );
        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Order'),
            'name' => 'id_action_order',
            'class' => 'chosen',
            'options' => array(
                'query' => ActionOrder::getAllActionOrder(),
                'id' => 'id_action_order',
                'name' => 'name',
            ),
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'points',
            'label' => $this->l('Change'),
            'desc'  => $this->l('If you want to give a penalty you can set -10 for example.'),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->l('Points'),
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins',
            'label' => $this->l('Change'),
            'desc'  => $this->l('If you want to give a penalty you can set -10 for example.'),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->l('Coins'),
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'loyalty',
            'label' => $this->l('Change'),
            'desc'  => $this->l('If you want to give a penalty you can set -10 for example.'),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->loyalty_name,
        );

        $fields_form = array(
            'legend' => array(
                'title' => $this->l('Add Custom Action'),
                'icon' => 'icon-cogs'
            ),
            'input' => $inputs,
            'submit' => array(
                'title' => $this->l('Save Custom Action'),
                'class' => 'btn btn-default pull-right'
            )
        );

        // Make sure the form is redirected to the player page
        self::$currentIndex .= '&id_customer='.Tools::getValue('id_customer');

        $this->submit_action = 'saveCustomAction';

        $this->fields_form = $fields_form;
        $this->table = 'genzo_krona_custom_action';

        // Set default values - it can be already filled if form is submitted an has errors
        if (empty($this->fields_value)) {
            $this->fields_value = json_decode(json_encode(new PlayerHistory()), true);
            $this->fields_value['action_type'] = '';
        }

        $this->tpl_form_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return parent::renderForm();

    }


    // Helper
    private function getStats() {
        $query = new DbQuery();
        $query->select('SUM(loyalty-loyalty_used-loyalty_expired) as loyalty');
        $query->from('genzo_krona_player_history');
        $values = Db::getInstance()->getRow($query);

        // Calculate value of loyalty points
        $id_action_order = ActionOrder::getIdActionOrderByCurrency($this->context->currency->id);
        $actionOrder = new ActionOrder($id_action_order);
        $values['loyalty'] = Tools::displayPrice($values['loyalty']*$actionOrder->coins_conversion);

        return $values;
    }

}
