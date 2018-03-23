<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Player.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/PlayerHistory.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/PlayerLevel.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Action.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/ActionOrder.php';

use KronaModule\Player;
use KronaModule\PlayerHistory;
use KronaModule\PlayerLevel;
use KronaModule\Action;
use KronaModule\ActionOrder;

class AdminGenzoKronaPlayersController extends ModuleAdminController
{
    /**
     * @var Player object
     */
    protected $object;

    private $id_shop_group;
    private $id_shop;

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

        $fields_list = array(
            'id_customer' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'alias' => 'c',
                'filter_type' => 'int',
            ),
            'firstname' => array(
                'title' => $this->l('Firstname'),
                'align' => 'left',
                'filter_type' => 'string',
                'filter_key' => 'c!firstname',
            ),
            'lastname' => array(
                'title' => $this->l('Lastname'),
                'align' => 'left',
                'filter_type' => 'string',
                'filter_key' => 'c!lastname',
            ),
            'pseudonym' => array(
                'title' => $this->l('Pseudonym'),
                'align' => 'left',
            ),
            'points' => array(
                'title' => $this->l('Points'),
                'class' => 'fixed-width-xs',
                'align' => 'left',
            ),
            'coins' => array(
                'title' => $this->l('Coins'),
                'class' => 'fixed-width-xs',
                'align' => 'left',
            ),
            'total' => array(
                'title' => $this->total_name,
                'class' => 'fixed-width-xs',
                'align' => 'left',
            ),
            'loyalty' => array(
                'title' => $this->loyalty_name,
                'class' => 'fixed-width-xs',
                'align' => 'left',
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'active' => 'status',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'type'  => 'bool',
                'filter_type' => 'int',
            ),
            'banned' => array(
                'title' => $this->l('Banned'),
                'active' => 'toggleBanned',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'type'  => 'bool',
                'filter_type' => 'int',
            ),
        );

        $this->fields_list = $fields_list;
        $this->actions = array('edit');
        $this->_orderBy = 'id_customer';
        $this->_orderWay = 'ASC';
        $this->bulk_actions = [];

        parent::__construct();

    }

    public function init() {

        parent::init();

        // Configuration
        $id_lang = $this->context->language->id;
        $this->id_shop_group = Context::getContext()->shop->id_shop_group;
        $this->id_shop = Context::getContext()->shop->id_shop;

        $this->is_loyalty = Configuration::get('krona_loyalty_active', null, $this->id_shop_group, $this->id_shop);
        $this->is_gamification = Configuration::get('krona_gamification_active', null, $this->id_shop_group, $this->id_shop);
        $this->loyalty_total = Configuration::get('krona_loyalty_total', null, $this->id_shop_group, $this->id_shop);
        $this->gamification_total = Configuration::get('krona_gamification_total', null, $this->id_shop_group, $this->id_shop);

        $this->total_name = Configuration::get('krona_total_name', $id_lang, $this->id_shop_group, $this->id_shop);
        $this->loyalty_name = Configuration::get('krona_loyalty_name', $id_lang, $this->id_shop_group, $this->id_shop);
    }

    public function initContent() {

        // Some Basic Display functions
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();

        if ($this->display == 'edit' || Tools::getValue('display') == 'formPlayer') {
            if (!$this->loadObject(true)) {
                return;
            }
            $this->content = $this->renderForm();
            $this->content.= $this->generateListPlayerLevels();
            $this->content.= $this->generateListPlayerHistory();
            $deletePlayers = false;
        }
        elseif (Tools::isSubmit('addCustomAction')) {
            $this->content = $this->generateFormCustomAction();
            $deletePlayers = false;
        }
        else {
            $this->content = $this->renderList();
            $deletePlayers = true;
        }

        // This are the real smarty variables
        $this->context->smarty->assign(
            array(
                'content'   => $this->content,
                'tab'       => 'Players',
                'loyalty_name'  => Configuration::get('krona_loyalty_name', $this->context->language->id, $this->id_shop_group, $this->id_shop),
                'import'  => Configuration::get('krona_import_customer', null, $this->id_shop_group, $this->id_shop),
                'dont'    => Configuration::get('krona_dont_import_customer', null, $this->id_shop_group, $this->id_shop),
                'deletePlayers' => $deletePlayers,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            )
        );

        $tpl = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'genzo_krona/views/templates/admin/main.tpl');

        $this->context->smarty->assign(array(
            'content' => $tpl, // This seems to be anything inbuilt. It's just chance that we both use content as an assign variable
        ));

    }

    public function initToolbar() {
        parent::initToolbar();
        unset( $this->toolbar_btn['new'] ); // To remove the add button
    }

    public function renderList() {

        if ($this->gamification_total == 'points_coins') {
            $this->_select .= ', `points`+`coins` as total ';
        }
        elseif ($this->gamification_total == 'points') {
            $this->_select .= ', `points` as total ';
        }
        elseif ($this->gamification_total == 'coins') {
            $this->_select .= ', `coins` as total ';
        }

        $fields_list['id_customer'] = array(
            'title' => 'ID',
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'alias' => 'c',
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
        if ($this->is_gamification && Configuration::get('krona_pseudonym', null, $this->id_shop_group, $this->id_shop)) {
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
        $this->_orderBy = 'id_customer';
        $this->_orderWay = 'ASC';
        $this->bulk_actions = [];

        if (Shop::isFeatureActive()) {
            $ids_shop = Shop::getContextListShopID();
            $this->_filter .= (' AND c.`id_shop` IN (' . implode(',', array_map('intval', $ids_shop)) . ') ');
        }

        return parent::renderList();
    }

    public function renderForm() {

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
                    'label' => $this->l('Yes'),
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No'),
                ),
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
                    'label' => $this->l('Yes'),
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No'),
                ),
            ),
        );

        $inputs[] = array(
            'type' => (Configuration::get('krona_pseudonym', null, $this->id_shop_group, $this->id_shop)) ? 'text' : 'hidden',
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
                'title' => $this->l('Edit Player'),
                'icon' => 'icon-cogs',
            ),
            'input' => $inputs,
            'submit' => array(
                'title' => $this->l('Save Player'),
                'class' => 'btn btn-default pull-right',
            )
        );

        // Fix of values since we dont use always same names
        $this->fields_form = $fields_form;

        $this->tpl_form_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        $this->default_form_language = $this->context->language->id;

        return parent::renderForm();
    }

    public function postProcess() {
        if (Tools::isSubmit('submitAdd'.$this->table)) {
            if (Configuration::get('krona_avatar', null, $this->id_shop_group, $this->id_shop)) {
                $krona = new Genzo_Krona();
                $id_customer = (int)Tools::getValue('id_customer');
                $player = new Player($id_customer);
                $player->avatar = ($krona->uploadAvatar($player->id_customer)) ? $player->id_customer . '.jpg' : $player->avatar;
                $player->update();
            }
        }
        elseif (Tools::isSubmit('saveCustomAction')) {
            $ids_lang = Language::getIDs();

            // Check inputs
            $id_customer = (int)Tools::getValue('id_customer');
            $points_change = Tools::getValue('points_change'); // Dont put these on int since we check it later
            $coins_change = Tools::getValue('coins_change');
            $type = Tools::getValue('action_type');

            $history = new PlayerHistory();
            $history->id_customer = $id_customer;
            ($type == 'action') ? $history->id_action = (int)Tools::getValue('id_action') : $history->id_action = 0;
            ($type == 'order') ? $history->id_action_order = (int)Tools::getValue('id_action_order') : $history->id_action_order = 0;

            $title = array();
            $message = array();

            if ($type == 'custom') {
                foreach ($ids_lang as $id_lang) {

                    $history->title[$id_lang] = pSQL(Tools::getValue('title_' . $id_lang));
                    $history->message[$id_lang] = pSQL(Tools::getValue('message_' . $id_lang));

                    // Now we check if all titles and messages are empty. We have to do it like that otherwise there will be notice error after getting the $data
                    if (Tools::getValue('title_' . $id_lang)) {
                        $title[$id_lang] = 'just for testing if empty';
                    }

                    if (Tools::getValue('message_' . $id_lang)) {
                        $message[$id_lang] = 'just for testing if empty';
                    }
                }
            }
            elseif ($type == 'action') {
                $action = new Action($history->id_action);
                $history->title = $action->title;
                $history->message = $action->message;

                foreach ($history->message as $id_lang => $message) {
                    $history->message[$id_lang] = str_replace('{points}', $points_change, $message);
                }
            }
            elseif ($type == 'order') {

                foreach (Language::getIDs() as $id_lang) {
                    $history->title[$id_lang] = Configuration::get('krona_order_title', $id_lang, $this->id_shop_group, $this->id_shop);

                    $message = Configuration::get('krona_order_message', $id_lang, $this->id_shop_group, $this->id_shop);
                    $history->message[$id_lang] = str_replace('{coins}', $coins_change, $message);
                }
            }

            if ($type == 'custom' AND (empty($title) OR empty($message))) {
                $this->errors[] = $this->l('Please fill in title and message');
            }

            if ($points_change === '' AND $coins_change === '') {
                $this->errors[] = $this->l('Please fill in (at least one) a value for points or coins.');
            }

            if (empty($this->errors)) {

                // Keep in mind both points and coins could change (no else if)
                if ($points_change !== '') {
                    $history->change = $points_change;
                    $history->add();

                    Player::updatePoints($id_customer, $points_change);
                    PlayerLevel::updatePlayerLevel(new Customer($id_customer), 'points', $history->id_action);
                }

                if ($coins_change !== '' ) {
                    $history->change = $coins_change;
                    $history->add();

                    Player::updateCoins($id_customer, $coins_change);
                    PlayerLevel::updatePlayerLevel(new Customer($id_customer), 'coins', $history->id_action);
                }

                $this->confirmations[] = $this->l('The player action was sucessfully saved.');

                return true;
            }
            else {
                $history->action_type = $type;
                if ($points_change != 0) {
                    $history->points_change = $points_change;
                }
                if ($coins_change != 0) {
                    $history->coins_change = $coins_change;
                }

                return $history;
            }
        }
        elseif (Tools::isSubmit('deletePlayerHistory')) {
            // Check inputs
            $id_history = (int)Tools::getValue('id_history');

            $history = new PlayerHistory($id_history);

            // This if is needed, in case of a refresh of the same url
            if ($history->id_customer) {
                Player::updatePoints($history->id_customer, ($history->change * (-1)));
            }
            $history->delete();

            if (empty($this->errors)) {
                $this->confirmations[] = $this->l('The Player History was deleted and the points removed.');
            }

            return true;
        }
        elseif (Tools::isSubmit('deletePlayerLevel')) {
            $id = (int)Tools::getValue('id');

            $playerLevel = new Playerlevel($id);
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
                Configuration::updateValue('krona_import_customer', 1, false, $this->id_shop_group, $id_shop);
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
        elseif (Tools::isSubmit('deleteCustomers')) {

            foreach (Shop::getContextListShopID() as $id_shop) {

                $players = Player::getAllPlayers();

                foreach ($players as $player) {
                    $player = new Player($player['id_customer']);
                    $player->delete();
                }

                $id_shop_group = Shop::getGroupFromShop($id_shop);

                Configuration::updateValue('krona_import_customer', 0, null, $id_shop_group, $id_shop);
            }

            Configuration::updateGlobalValue('krona_import_customer', 0, '');

            $this->confirmations[] = $this->l('Players deleted');
        }
        elseif (Tools::isSubmit('toggleBanned'.$this->table)) {
            $krona = new Genzo_Krona();
            $krona->saveToggle($this->table, 'id_customer', 'banned');
        }

       return parent::postProcess();
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

    // Helper Lists
    private function generateListPlayerLevels() {

        $krona = new Genzo_Krona();
        $id_customer = Tools::getValue('id_customer');

        $fields_list = array(
            'id' => array(
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
                'align' => 'left',
            ),
        );

        $helper = new HelperList();
        $helper->table = 'PlayerLevel';
        $helper->shopLinkType = '';
        $helper->actions = array('delete');
        $helper->identifier = 'id';
        $helper->_pagination = [20,50,100];
        $helper->token = Tools::getAdminTokenLite($this->controller_name);

        // Index is very important for filtering on a sublist. Here we can set paramaters which can be used later with getValue
        $helper->currentIndex = $this->context->link->getAdminLink($this->controller_name, false) . '&id_customer='. $id_customer .'&display=formPlayer';

        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('filter').$helper->table) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $krona->getFiltersFromList($fields_list, $helper->table);
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
            $pagination = $krona->getPagination($helper->table);
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

        $krona = new Genzo_Krona();

        $id_customer = Tools::getValue('id_customer');

        $fields_list = array(
            'id_history' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'alias' => 'h',
                'filter_type' => 'int',
            ),
            'title' => array(
                'title' => $this->l('Action'),
                'align' => 'left',
            ),
            'message' => array(
                'title' => $this->l('Message'),
                'align' => 'left',
            ),
            'change' => array(
                'title' => $this->l('Change'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_type' => 'int'
            ),
            'url' => array(
                'title' => 'Url',
                'align' => 'left',
                'remove_onclick' => true,
            ),
        );

        $helper = new HelperList();
        $helper->table = 'PlayerHistory';
        $helper->shopLinkType = '';
        $helper->actions = array('delete');
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
        $helper->currentIndex = $this->context->link->getAdminLink($this->controller_name, false) . '&display=formPlayer&id_customer='. $id_customer;

        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('filter').$helper->table) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $krona->getFiltersFromList($fields_list, $helper->table);
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
            $pagination = $krona->getPagination($helper->table);
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

    // Helper Forms
    private function generateFormCustomAction($data = null) {
        $id_customer = (int)Tools::getValue('id_customer');

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
            'name'  => 'points_change',
            'label' => $this->l('Change'),
            'desc'  => $this->l('If you want to give a penalty you can set -10 for example.'),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->l('Points'),
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins_change',
            'label' => $this->l('Change'),
            'desc'  => $this->l('If you want to give a penalty you can set -10 for example.'),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->l('Coins'),
        );

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Add Custom Action'),
                    'icon' => 'icon-cogs'
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save Custom Action'),
                    'class' => 'btn btn-default pull-right'
                )
            )
        );

        $helper = new HelperForm();
        $helper->submit_action = 'saveCustomAction';
        $helper->default_form_language = $this->context->language->id;
        $helper->currentIndex = $this->context->link->getAdminLink($this->controller_name, false) . '&display=formPlayer&id_customer='.$id_customer;
        $helper->token = Tools::getAdminTokenLite($this->controller_name);
        $helper->table = 'genzo_krona_custom_action';

        // Get Valuess
        $vars['id_customer'] = $id_customer;

        if ($data) {
            $vars = json_decode(json_encode($data), true);
            if (empty($vars['points_change'])) {$vars['points_change'] = '';}
            if (empty($vars['coins_change'])) {$vars['coins_change'] = '';}
        }
        else {
            foreach (Language::getIDs() as $id_lang) {
                $vars['title'][$id_lang] = '';
                $vars['message'][$id_lang] = '';
            }

            $vars['action_type'] = 'custom';
            $vars['id_action'] = 1;
            $vars['id_action_order'] = 1;
            $vars['points_change'] = '';
            $vars['coins_change'] = '';
        }

        $helper->tpl_vars = array(
            'fields_value' => $vars,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));

    }

}
