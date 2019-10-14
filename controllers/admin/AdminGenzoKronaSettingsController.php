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

class AdminGenzoKronaSettingsController extends ModuleAdminController {

    public function __construct() {

        $this->module = 'genzo_krona';
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->lang = false;

        parent::__construct();
    }

    public function initContent() {

        // Some Basic Display functions
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();

        if (Shop::isFeatureActive() && (Shop::getContext() == Shop::CONTEXT_GROUP || Shop::getContext() == Shop::CONTEXT_ALL)) {
            $this->errors[] = $this->l('Please chose a specific shop, to save the settings.');
        }
        else {
            $this->content = $this->renderForm();
        }

        // This are the real smarty variables
        $this->context->smarty->assign(
            array(
                'content'   => $this->content,
                'tab'       => 'Settings',
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

    public function renderForm() {

        $ids_lang = Language::getIDs();

        $loyalty = Configuration::get('krona_loyalty_active');
        $referral = Configuration::get('krona_referral_active');
        $gamification = Configuration::get('krona_gamification_active');

        // Values
        foreach ($ids_lang as $id_lang) {
            $total_name[$id_lang] = Configuration::get('krona_total_name', $id_lang);
            $loyalty_name[$id_lang] = Configuration::get('krona_loyalty_name', $id_lang);
            $game_name[$id_lang] = Configuration::get('krona_game_name', $id_lang);
            $order_title[$id_lang] = Configuration::get('krona_order_title', $id_lang);
            $order_message[$id_lang] = Configuration::get('krona_order_message', $id_lang);
            $order_canceled_message[$id_lang] = Configuration::get('krona_order_canceled_message', $id_lang);
            $home_description[$id_lang] = Configuration::get('krona_description', $id_lang);
            $referral_title_referrer[$id_lang] = Configuration::get('krona_referral_title_referrer', $id_lang);
            $referral_text_referrer[$id_lang] = Configuration::get('krona_referral_text_referrer', $id_lang);
        }

        (Tools::getValue('tab_fake')) ? $tab_fake = Tools::getValue('tab_fake') : $tab_fake = '#general';

        $this->fields_value = [
            'total_name'              => $total_name,
            'loyalty_name'            => $loyalty_name,
            'game_name'               => $game_name,
            'order_title'             => $order_title,
            'order_message'           => $order_message,
            'order_canceled_message'  => $order_canceled_message,
            'home_description'        => $home_description,
            'referral_title_referrer'  => $referral_title_referrer,
            'referral_text_referrer'  => $referral_text_referrer,
            'levels_grid' => Configuration::get('krona_levels_grid'),
            'notification' => Configuration::get('krona_notification'),
            'loyalty_active' => Configuration::get('krona_loyalty_active'),
            'loyalty_total' => Configuration::get('krona_loyalty_total'),
            'referral_active' => Configuration::get('krona_referral_active'),
            'gamification_active' => Configuration::get('krona_gamification_active'),
            'gamification_total' => Configuration::get('krona_gamification_total'),
            'url' => Configuration::get('krona_url'),
            'customer_active' => Configuration::get('krona_customer_active'),
            'display_name' => Configuration::get('krona_display_name'),
            'pseudonym' => Configuration::get('krona_pseudonym'),
            'loyalty_product_page' => Configuration::get('krona_loyalty_product_page'),
            'loyalty_cart_page' => Configuration::get('krona_loyalty_cart_page'),
            'loyalty_checkout_conversion' => Configuration::get('krona_loyalty_checkout_conversion'),
            'loyalty_expire_method' => Configuration::get('krona_loyalty_expire_method'),
            'loyalty_expire_days' => Configuration::get('krona_loyalty_expire_days'),
            'avatar' => Configuration::get('krona_avatar'),
            'leaderboard' => Configuration::get('krona_leaderboard'),
            'leaderboard_page' => Configuration::get('krona_leaderboard_page'),
            'hide_players[]' => explode(',', Configuration::get('krona_hide_players')),
            'order_amount' => Configuration::get('krona_order_amount'),
            'order_coupon' => Configuration::get('krona_order_coupon'),
            'order_rounding' => Configuration::get('krona_order_rounding'),
            'coupon_prefix' => Configuration::get('krona_coupon_prefix'),
            'referral_order_nbr' => Configuration::get('krona_referral_order_nbr'),
            'tab_fake' => $tab_fake,
        ];

        // Order Status
        $order_state = explode(',', Configuration::get('krona_order_state'));
        foreach ($order_state as $id) {
            $this->fields_value['order_state_'.$id] = true;
        }

        $tabs = array(
            'general' => $this->l('General'),
            'order' => $this->l('Orders'),
            'loyalty' => $this->l('Loyalty'),
            'referral' => $this->l('Referral'),
            'gamification' => $this->l('Gamification'),
            'coupon' => $this->l('Coupons'),
        );

        // General
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Loyalty Activation'),
            'desc' => $this->l('Do you want to let the customer, convert loyalty points into coupons?'),
            'name' => 'loyalty_active',
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
            'tab' => 'general',
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Referral Activation'),
            'desc' => $this->l('Do you want to use the referral system?'),
            'name' => 'referral_active',
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
            'tab' => 'general',
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Gamification Activation'),
            'desc' => $this->l('Do you want to use gamification with leaderboard, avatar, pseudonym etc.'),
            'name' => 'gamification_active',
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
            'tab' => 'general',
        );
        $inputs[] = array(
            'type' => 'text',
            'name' => 'game_name',
            'label' => $this->l('Name'),
            'desc' => $this->l('What is the name of your loyalty system?'),
            'lang' => true,
            'tab' => 'general',
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'url',
            'label' => $this->l('Url'),
            'desc'  => $this->l('The url in the frontoffice will look like: domain.com/url'),
            'tab' => 'general',
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Customer Activation'),
            'desc' => $this->l('Do you want to set the customer active right after registration?'),
            'name' => 'customer_active',
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
            'tab' => 'general',
        );
        $inputs[] = array(
            'type'  => 'textarea',
            'name'  => 'home_description',
            'label' => $this->l('Home content'),
            'desc' => $this->l('Describe your loyality game here.'),
            'lang'  => true,
            'autoload_rte' => true,
            'tab' => 'general',
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Levels Display Grid'),
            'desc' => $this->l('Should the levels in FrontOffice be displayed as grid (yes) or list (no)'),
            'name' => 'levels_grid',
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
            'tab' => 'general',
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Notification'),
            'desc' => $this->l('You need to add a div with id "krona-notification". 
                                      It will be filled with the number of unseen events.'),
            'name' => 'notification',
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
            'tab' => 'general',
        );

        // Orders
        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Total Amount'),
            'desc' => $this->l('Which total amount should be transformed into coins? It is highly recommended to chose a value WITH tax.'),
            'name' => 'order_amount',
            'options' => array(
                'query' => array(
                    array('value' => 'total_wt', 'name' => $this->l('Products + Shipping with tax')),
                    array('value' => 'total', 'name' => $this->l('Products + Shipping without tax')),
                    array('value' => 'total_products_wt', 'name' => $this->l('Products with tax')),
                    array('value' => 'total_products', 'name' => $this->l('Products without tax')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
            'tab' => 'order',
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Substract Coupon'),
            'desc' => $this->l('Reduce the total value, with the value of the used coupon.'),
            'name' => 'order_coupon',
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
            'tab' => 'order',
        );
        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Rounding'),
            'desc' => $this->l('Will a value of 8.90 become 9 or 8?'),
            'name' => 'order_rounding',
            'options' => array(
                'query' => array(
                    array('value' => 'up', 'name' => $this->l('Up')),
                    array('value' => 'down', 'name' => $this->l('Down')),
                    array('value' => 'nearest', 'name' => $this->l('Nearest')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
            'tab' => 'order',
        );

        $orderStates = OrderState::getOrderStates($this->context->language->id);

        $inputs[] = array(
            'type' => 'checkbox',
            'label' => $this->l('Order State'),
            'desc' => $this->l('Which order states are valid to receive coins? You should select all of them. As the coins will be updated every time the order state changes.'),
            'name' => 'order_state',
            'multiple' => true,
            'values' => [
                'query' => $orderStates,
                'id' => 'id_order_state',
                'name' => 'name',
            ],
            'expand' => (count($orderStates) > 10) ? [
                'print_total' => count($orderStates),
                'default' => 'show',
                'show' => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                'hide' => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
            ] : null,
            'tab' => 'order',
        );
        $inputs[] = array(
            'type' => 'text',
            'name' => 'order_title',
            'label' => $this->l('Title order'),
            'desc' => $this->l('The user will see title and message in Front Office.'),
            'lang' => true,
            'tab' => 'order',
        );
        $inputs[] = array(
            'type' => 'text',
            'name' => 'order_message',
            'label' => $this->l('Message order'),
            'desc' => $this->l('You can use:') . ' {coins} {loyalty_expire_date} {reference} {amount}',
            'lang' => true,
            'tab' => 'order',
        );

        $inputs[] = array(
            'type' => 'text',
            'name' => 'order_canceled_message',
            'label' => $this->l('Message canceled order'),
            'desc' => $this->l('You can use:') . ' {coins} {reference} {amount}',
            'lang' => true,
            'tab' => 'order',
        );

        // Loyalty
        if ($loyalty) {

            $inputs[] = array(
                'type' => 'select',
                'label' => $this->l('Loyalty Total Value'),
                'name' => 'loyalty_total',
                'desc' => $this->l('Points are collected by actions like: newsletter registration, reviews etc. Coins are collected by placing orders.'),
                'options' => array(
                    'query' => array(
                        array('value' => 'points_coins', 'name' => $this->l('Points + Coins')),
                        array('value' => 'points', 'name' => $this->l('Points')),
                        array('value' => 'coins', 'name' => $this->l('Coins')),
                    ),
                    'id' => 'value',
                    'name' => 'name',
                ),
                'tab' => 'loyalty',
            );
            $inputs[] = array(
                'type' => 'text',
                'name' => 'loyalty_name',
                'label' => $this->l('Loyalty Points Name'),
                'desc' => $this->l('Loyalty points are the points, which can be converted directly into coupons.'),
                'lang' => true,
                'tab' => 'loyalty',
            );
            $inputs[] = array(
                'type' => 'switch',
                'label' => $this->l('Loyalty on product page'),
                'desc' => $this->l('Show how many coins a customers receives, if he buys the product.'),
                'name' => 'loyalty_product_page',
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
                'tab' => 'loyalty',
            );

            $inputs[] = array(
                'type' => 'switch',
                'label' => $this->l('Loyalty on cart page'),
                'desc' => $this->l('Show how many coins a customers receives, if he places the order.'),
                'name' => 'loyalty_cart_page',
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
                'tab' => 'loyalty',
            );

            $inputs[] = array(
                'type' => 'switch',
                'label' => $this->l('Loyalty conversion on checkout'),
                'desc' => $this->l('Should the customer been able to convert his points directly at the checkout?'),
                'name' => 'loyalty_checkout_conversion',
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
                'tab' => 'loyalty',
            );

            $inputs[] = array(
                'type' => 'select',
                'label' => $this->l('Expiring Method'),
                'name' => 'loyalty_expire_method',
                'options' => array(
                    'query' => array(
                        array('value' => 'none', 'name' => $this->l('None')),
                        array('value' => 'fixed', 'name' => $this->l('Fixed Expiring Date')),
                        array('value' => 'refreshing', 'name' => $this->l('Refreshing Expiring Date')),
                    ),
                    'id' => 'value',
                    'name' => 'name',
                ),
                'desc' => $this->l('Fixed: the points expire x-days after the customer earned it.').'<br>'.$this->l('Refreshing: when a customer places a new order, the expiring date of all points is refreshing.'),
                'tab' => 'loyalty',
            );

            $inputs[] = array(
                'type' => 'text',
                'label' => $this->l('Expire Loyalty Points'),
                'name' => 'loyalty_expire_days',
                'desc' => $this->l('After how many days should the loyalty points be expired? This option will expire all loyalty points. Note: A new order by the customer will update the expire date.'),
                'suffix' => $this->l('Days'),
                'class' => 'input fixed-width-sm',
                'tab' => 'loyalty',
            );

            $inputs[] = array(
                'type' => 'select',
                'label' => $this->l('Update Loyalty Expire Dates'),
                'name' => 'loyalty_expire_update',
                'options' => array(
                    'query' => array(
                        array('value' => 'none', 'name' => $this->l('None')),
                        array('value' => 'today', 'name' => $this->l('From Today')),
                        array('value' => 'last_order', 'name' => $this->l('From Last Order')),
                    ),
                    'id' => 'value',
                    'name' => 'name',
                ),
                'desc' => $this->l('Do you want to update the expire dates of a customer? You should use this option, if you use the expire function the first time. From Today is recommended. Be careful: If you use From Last Order a lot of customer will lose their loyalty points, when your cron is executed.' ),
                'tab' => 'loyalty',
            );
        }

        // Referral
        if ($referral) {
            $inputs[] = array(
                'type' => 'text',
                'name' => 'referral_order_nbr',
                'label' => $this->l('Number of orders'),
                'desc' => $this->l('On how many orders, should the customers benefit from your custom rules? Most merchants only give the benefits only on the first order...'),
                'suffix' => $this->l('Orders'),
                'class' => 'input fixed-width-sm',
                'tab' => 'referral',
            );
            $inputs[] = array(
                'type' => 'text',
                'lang' => true,
                'name' => 'referral_title_referrer',
                'label' => $this->l('Title on order for referrer'),
                'desc' => $this->l('This will appear on the history of the referrer.'),
                'tab' => 'referral',
            );
            $inputs[] = array(
                'type' => 'text',
                'lang' => true,
                'name' => 'referral_text_referrer',
                'label' => $this->l('Text on order for referrer'),
                'desc' => $this->l('This will appear on the history of the referrer. You can use: {coins} {loyalty_expire_date} {buyer_name}'),
                'tab' => 'referral',
            );
        }

        // Gamification
        if ($gamification) {

            $inputs[] = array(
                'type' => 'select',
                'label' => $this->l('Gamification Total Value'),
                'name' => 'gamification_total',
                'desc' => $this->l('Points are collected by actions like: newsletter registration, reviews etc. Coins are collected by placing orders.'),
                'options' => array(
                    'query' => array(
                        array('value' => 'points_coins', 'name' => $this->l('Points + Coins')),
                        array('value' => 'points', 'name' => $this->l('Points')),
                        array('value' => 'coins', 'name' => $this->l('Coins')),
                    ),
                    'id' => 'value',
                    'name' => 'name',
                ),
                'tab' => 'gamification',
            );

            $inputs[] = array(
                'type' => 'text',
                'name' => 'total_name',
                'label' => $this->l('Gamification Points Name'),
                'desc' => $this->l('How shall the total points be called? You could consider total points as "Lifetime points".'),
                'lang' => true,
                'tab' => 'gamification',
            );

            $inputs[] = array(
                'type' => 'select',
                'label' => $this->l('Default Display Name'),
                'name' => 'display_name',
                'options' => array(
                    'query' => array(
                        array('value' => 1, 'name' => $this->l('John Doe')),
                        array('value' => 2, 'name' => $this->l('John D.')),
                        array('value' => 3, 'name' => $this->l('J. Doe')),
                        array('value' => 4, 'name' => $this->l('J. D.')),
                        array('value' => 5, 'name' => $this->l('John')),
                    ),
                    'id' => 'value',
                    'name' => 'name',
                ),
                'tab' => 'gamification',
            );

            $inputs[] = array(
                'type' => 'switch',
                'label' => $this->l('Pseudonym'),
                'desc' => $this->l('Are customers allowed to set a pseudonym as display name?'),
                'name' => 'pseudonym',
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
                'tab' => 'gamification',
            );

            $inputs[] = array(
                'type' => 'switch',
                'label' => $this->l('Avatar'),
                'desc' => $this->l('Are customers allowed to upload an avatar?'),
                'name' => 'avatar',
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
                'tab' => 'gamification',
            );

            $inputs[] = array(
                'type' => 'text',
                'name' => 'leaderboard',
                'label' => $this->l('Players on Leaderboard'),
                'desc' => $this->l('How many players should be displayed on the leaderboard? Set it to 0 if you want display all.'),
                'suffix' => $this->l('Players'),
                'class' => 'input fixed-width-sm',
                'tab' => 'gamification',
            );

            $inputs[] = array(
                'type' => 'text',
                'name' => 'leaderboard_page',
                'label' => $this->l('Players per Page'),
                'desc' => $this->l('How many players should be displayed on the leaderboard per page?'),
                'suffix' => $this->l('Players'),
                'class' => 'input fixed-width-sm',
                'tab' => 'gamification',
            );

            $players = \KronaModule\Player::getAllPlayers();

            $inputs[] = array(
                'type' => 'select',
                'label' => $this->l('Hide Players in List'),
                'name' => 'hide_players',
                'class' => 'chosen',
                'multiple' => true,
                'options' => array(
                    'query' => $players,
                    'id' => 'id_customer',
                    'name' => 'option_name',
                ),
                'tab' => 'gamification',
                'desc' => $this->l('This will hide players in FO Leaderboard.')
            );
        }

        // Coupons
        $inputs[] = array(
            'type'         => 'text',
            'name'         => 'coupon_prefix',
            'label'     => $this->l('Coupon prefix'),
            'class'  => 'input fixed-width-sm',
            'desc' => $this->l('Prefix is optional. The Coupon will look like: Prefix-Code'),
            'tab' => 'coupon',
        );

        // Fake
        $inputs[] = array(
            'type'         => 'hidden',
            'name'         => 'tab_fake',
        );

        $this->fields_form = [
            'legend'      => [
                'title' => $this->l('Settings'),
                'icon'  => 'icon-config',
            ],
            'tabs'        => $tabs,
            'description' => $this->l('Your Cron Job Url:').' '.$this->getCronJobUrl(),
            'input'       => $inputs,
            'submit'      => [
                'title' => 'Save',
                'name'  => 'saveSettings'
            ],
            'buttons'     => [],
        ];


        return parent::renderForm();
    }

    public function postProcess() {

        if (Tools::isSubmit('saveSettings')) {

            $loyalty = Configuration::get('krona_loyalty_active');
            $referral = Configuration::get('krona_referral_active');
            $gamification = Configuration::get('krona_gamification_active');

            // Settings
            $ids_lang = Language::getIDs();
            $game_names = array();
            $total_names = array();
            $loyalty_names = array();
            $order_titles = array();
            $order_messages = array();
            $order_canceled_messages = array();
            $home_descriptions = array();

            // Lang fields
            foreach ($ids_lang as $id_lang) {
                $game_names[$id_lang] = Tools::getValue('game_name_'.$id_lang);
                $total_names[$id_lang] = Tools::getValue('total_name_'.$id_lang);
                $loyalty_names[$id_lang] = Tools::getValue('loyalty_name_'.$id_lang);
                $order_titles[$id_lang] = Tools::getValue('order_title_'.$id_lang);
                $order_messages[$id_lang] = Tools::getValue('order_message_'.$id_lang);
                $order_canceled_messages[$id_lang] = Tools::getValue('order_canceled_message_'.$id_lang);
                $home_descriptions[$id_lang] = Tools::getValue('home_description_'.$id_lang);
            }

            Configuration::updateValue('krona_game_name', $game_names);
            if ($gamification) { Configuration::updateValue('krona_total_name', $total_names); }
            if ($loyalty) { Configuration::updateValue('krona_loyalty_name', $loyalty_names); }
            Configuration::updateValue('krona_order_title', $order_titles);
            Configuration::updateValue('krona_order_message', $order_messages);
            Configuration::updateValue('krona_order_canceled_message', $order_canceled_messages);
            Configuration::updateValue('krona_description', $home_descriptions, true);

            // Basic Fields
            Configuration::updateValue('krona_loyalty_active', Tools::getValue('loyalty_active'));
            Configuration::updateValue('krona_referral_active', Tools::getValue('referral_active'));
            Configuration::updateValue('krona_gamification_active', Tools::getValue('gamification_active'));

            Configuration::updateValue('krona_levels_grid', (bool)Tools::getValue('levels_grid'));
            Configuration::updateValue('krona_notification', (bool)Tools::getValue('notification'));

            // Loyalty
            if ($loyalty) {
                Configuration::updateValue('krona_loyalty_total', Tools::getValue('loyalty_total'));
                Configuration::updateValue('krona_loyalty_product_page', Tools::getValue('loyalty_product_page'));
                Configuration::updateValue('krona_loyalty_cart_page', Tools::getValue('loyalty_cart_page'));
                Configuration::updateValue('krona_loyalty_checkout_conversion', Tools::getValue('loyalty_checkout_conversion'));

                // Expiration
                Configuration::updateValue('krona_loyalty_expire_method', Tools::getValue('loyalty_expire_method'));
                Configuration::updateValue('krona_loyalty_expire_days', (int)Tools::getValue('loyalty_expire_days'));

                if (Tools::getValue('loyalty_expire_update')!='none') {
                    self::updateAllExpireLoyalty(Tools::getValue('loyalty_expire_update'), Tools::getValue('loyalty_expire_days'));
                }
            }

            // Referral
            if ($referral) {

                // Basic Fields
                Configuration::updateValue('krona_referral_order_nbr', Tools::getValue('referral_order_nbr'));

                // Language Fields
                foreach ($ids_lang as $id_lang) {
                    $referral_title_referrer[$id_lang] = Tools::getValue('referral_title_referrer_'.$id_lang);
                    $referral_text_referrer[$id_lang] = Tools::getValue('referral_text_referrer_'.$id_lang);
                }

                Configuration::updateValue('krona_referral_title_referrer', $referral_title_referrer);
                Configuration::updateValue('krona_referral_text_referrer', $referral_text_referrer);
            }

            // Gamification
            if ($gamification) {
                Configuration::updateValue('krona_gamification_total', Tools::getValue('gamification_total'));
                Configuration::updateValue('krona_display_name', (int)Tools::getValue('display_name'));
                Configuration::updateValue('krona_pseudonym', (bool)Tools::getValue('pseudonym'));
                Configuration::updateValue('krona_avatar', (bool)Tools::getValue('avatar'));
                Configuration::updateValue('krona_leaderboard', (int)Tools::getValue('leaderboard'));
                Configuration::updateValue('krona_leaderboard_page', (int)Tools::getValue('leaderboard_page'));
            }

            Configuration::updateValue('krona_url', Tools::getValue('url'));
            Configuration::updateValue('krona_customer_active', (bool)Tools::getValue('customer_active'));
            Configuration::updateValue('krona_order_amount', Tools::getValue('order_amount'));
            Configuration::updateValue('krona_order_coupon', Tools::getValue('order_coupon'));
            Configuration::updateValue('krona_order_rounding', Tools::getValue('order_rounding'));
            Configuration::updateValue('krona_coupon_prefix', Tools::getValue('coupon_prefix'));

            // Handling Status
            $orderStates = OrderState::getOrderStates($this->context->language->id);
            $order_state_selected = array();

            foreach ($orderStates as $orderState) {

                $id_order_state = $orderState['id_order_state'];

                if (Tools::isSubmit('order_state_'.$id_order_state)) {
                    $order_state_selected[] = $id_order_state;
                }
            }
            Configuration::updateValue('krona_order_state', implode(",", $order_state_selected));

            // Handling hide_players
            $hide_players = Tools::getValue('hide_players');
            $hide_players = (is_array($hide_players)) ? implode(',', $hide_players) : [];
            Configuration::updateValue('krona_hide_players', $hide_players);

            if (empty($this->errors)) {
                $this->confirmations[] = $this->l('Settings were sucessfully saved.');
            }
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

    // Helpers
    private function getCronJobUrl() {

        $secureKey = md5(_COOKIE_KEY_ . Configuration::get('PS_SHOP_NAME'));

        $url =  _PS_BASE_URL_._MODULE_DIR_."genzo_krona/genzo_krona_cron.php?secure_key=".$secureKey;

        if (Configuration::get('PS_SSL_ENABLED')==1) {
            $url = str_replace('http://', 'https://', $url);
        }

        return $url;
    }

    private  function updateAllExpireLoyalty($expire_type, $expire_days) {

        $players = Player::getAllPlayers();

        foreach ($players as $player) {

            if ($expire_type == 'today') {
                $expire_date = date("Y-m-d 23:59:59", strtotime(" + {$expire_days} days"));
            }
            elseif ($expire_type == 'last_order') {

                $query = new \DbQuery();
                $query->select('MAX(date_add)');
                $query->from('orders');
                $query->where('id_customer = ' . $player['id_customer']);
                $query->where('valid = 1');
                $last_order = \Db::getInstance()->getValue($query);

                $expire_date = date("Y-m-d H:i:s", strtotime($last_order." + {$expire_days} days"));
            }
            else {
                $expire_date = date("2099-01-01 23:59:59", strtotime(" + {$expire_days} days"));
            }

            DB::getInstance()->update('genzo_krona_player_history', ['loyalty_expire_date' => $expire_date], 'loyalty-loyalty_used-loyalty_expired >0 AND id_customer='.$player['id_customer']);
        }
    }

}
