<?php

class AdminGenzoKronaSettingsController extends ModuleAdminController
{

    /**
     * @var SettingsGroup object
     */
    protected $object;

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
        $id_lang = $this->context->language->id;
        $loyalty = Configuration::get('krona_loyalty_active', null, $this->id_shop_group, $this->id_shop);
        $gamifaction = Configuration::get('krona_gamification_active', null, $this->id_shop_group, $this->id_shop);

        // Values
        foreach ($ids_lang as $id_lang) {
            $total_name[$id_lang] = Configuration::get('krona_total_name', $id_lang, $this->id_shop_group, $this->id_shop);
            $loyalty_name[$id_lang] = Configuration::get('krona_loyalty_name', $id_lang, $this->id_shop_group, $this->id_shop);
            $game_name[$id_lang] = Configuration::get('krona_game_name', $id_lang, $this->id_shop_group, $this->id_shop);
            $order_title[$id_lang] = Configuration::get('krona_order_title', $id_lang, $this->id_shop_group, $this->id_shop);
            $order_message[$id_lang] = Configuration::get('krona_order_message', $id_lang, $this->id_shop_group, $this->id_shop);
            $order_canceled_title[$id_lang] = Configuration::get('krona_order_canceled_title', $id_lang, $this->id_shop_group, $this->id_shop);
            $order_canceled_message[$id_lang] = Configuration::get('krona_order_canceled_message', $id_lang, $this->id_shop_group, $this->id_shop);
            $home_description[$id_lang] = Configuration::get('krona_description', $id_lang, $this->id_shop_group, $this->id_shop);
        }

        (Tools::getValue('tab_fake')) ? $tab_fake = Tools::getValue('tab_fake') : $tab_fake = '#general';

        $this->fields_value = [
            'total_name'              => $total_name,
            'loyalty_name'            => $loyalty_name,
            'game_name'               => $game_name,
            'order_title'             => $order_title,
            'order_message'           => $order_message,
            'order_canceled_title'    => $order_canceled_title,
            'order_canceled_message'  => $order_canceled_message,
            'home_description'        => $home_description,
            'loyalty_active' => Configuration::get('krona_loyalty_active', null, $this->id_shop_group, $this->id_shop),
            'loyalty_total' => Configuration::get('krona_loyalty_total', null, $this->id_shop_group, $this->id_shop),
            'gamification_active' => Configuration::get('krona_gamification_active', null, $this->id_shop_group, $this->id_shop),
            'gamification_total' => Configuration::get('krona_gamification_total', null, $this->id_shop_group, $this->id_shop),
            'url' => Configuration::get('krona_url', null, $this->id_shop_group, $this->id_shop),
            'customer_active' => Configuration::get('krona_customer_active', null, $this->id_shop_group, $this->id_shop),
            'display_name' => Configuration::get('krona_display_name', null, $this->id_shop_group, $this->id_shop),
            'pseudonym' => Configuration::get('krona_pseudonym', null, $this->id_shop_group, $this->id_shop),
            'loyalty_product_page' => Configuration::get('krona_loyalty_product_page', null, $this->id_shop_group, $this->id_shop),
            'avatar' => Configuration::get('krona_avatar', null, $this->id_shop_group, $this->id_shop),
            'order_amount' => Configuration::get('krona_order_amount', null, $this->id_shop_group, $this->id_shop),
            'order_rounding' => Configuration::get('krona_order_rounding', null, $this->id_shop_group, $this->id_shop),
            'coupon_prefix' => Configuration::get('krona_coupon_prefix', null, $this->id_shop_group, $this->id_shop),
            'tab_fake' => $tab_fake,
        ];

        // Order Status
        $order_state = explode(',', Configuration::get('krona_order_state', null, $this->id_shop_group, $this->id_shop));
        foreach ($order_state as $id) {
            $this->fields_value['order_state_'.$id] = true;
        }
        $order_state_cancel = explode(',', Configuration::get('krona_order_state_cancel', null, $this->id_shop_group, $this->id_shop));
        foreach ($order_state_cancel as $id) {
            $this->fields_value['order_state_cancel_'.$id] = true;
        }


        $tabs = array(
            'general' => $this->l('General'),
            'order' => $this->l('Orders'),
            'loyalty' => $this->l('Loyalty'),
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
            'label' => $this->l('Gamification Activation'),
            'desc' => $this->l('Do you want to use gamifacation with leaderboard, avatar, pseudonym etc.'),
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
            'type' => 'select',
            'label' => $this->l('Rounding'),
            'desc' => $this->l('Will a value of 8.90 become 9 or 8?'),
            'name' => 'order_rounding',
            'options' => array(
                'query' => array(
                    array('value' => 'up', 'name' => $this->l('Up')),
                    array('value' => 'down', 'name' => $this->l('Down')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
            'tab' => 'order',
        );

        $orderStates = OrderState::getOrderStates($id_lang);

        $inputs[] = array(
            'type' => 'checkbox',
            'label' => $this->l('Order State'),
            'desc' => $this->l('On which order state will the order be transformed into coins?'),
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
            'type' => 'checkbox',
            'label' => $this->l('Cancel Order State'),
            'desc' => $this->l('On which order state should the coins be taken back?'),
            'name' => 'order_state_cancel',
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
            'desc' => $this->l('You can use:') . ' {coins} {reference} {amount}',
            'lang' => true,
            'tab' => 'order',
        );
        $inputs[] = array(
            'type' => 'text',
            'name' => 'order_canceled_title',
            'label' => $this->l('Title canceled order'),
            'desc' => $this->l('The user will see title and message in Front Office.'),
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
        }

        // Gamification
        if ($gamifaction) {
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
            $loyalty = Configuration::get('krona_loyalty_active', null, $this->id_shop_group, $this->id_shop);
            $gamification = Configuration::get('krona_gamification_active', null, $this->id_shop_group, $this->id_shop);

            // Settings
            $ids_lang = Language::getIDs();
            $game_names = array();
            $total_names = array();
            $loyalty_names = array();
            $order_titles = array();
            $order_messages = array();
            $order_canceled_titles = array();
            $order_canceled_messages = array();
            $home_descriptions = array();

            // Lang fields
            foreach ($ids_lang as $id_lang) {
                $game_names[$id_lang] = Tools::getValue('game_name_'.$id_lang);
                $total_names[$id_lang] = Tools::getValue('total_name_'.$id_lang);
                $loyalty_names[$id_lang] = Tools::getValue('loyalty_name_'.$id_lang);
                $order_titles[$id_lang] = Tools::getValue('order_title_'.$id_lang);
                $order_messages[$id_lang] = Tools::getValue('order_message_'.$id_lang);
                $order_canceled_titles[$id_lang] = Tools::getValue('order_canceled_title_'.$id_lang);
                $order_canceled_messages[$id_lang] = Tools::getValue('order_canceled_message_'.$id_lang);
                $home_descriptions[$id_lang] = Tools::getValue('home_description_'.$id_lang);
            }

            Configuration::updateValue('krona_game_name', $game_names, false, $this->id_shop_group, $this->id_shop);
            if ($gamification) { Configuration::updateValue('krona_total_name', $total_names, false, $this->id_shop_group, $this->id_shop); }
            if ($loyalty) { Configuration::updateValue('krona_loyalty_name', $loyalty_names, false, $this->id_shop_group, $this->id_shop); }
            Configuration::updateValue('krona_order_title', $order_titles, false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_order_message', $order_messages, false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_order_canceled_title', $order_canceled_titles, false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_order_canceled_message', $order_canceled_messages, false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_description', $home_descriptions, true, $this->id_shop_group, $this->id_shop);

            // Basic Fields
            Configuration::updateValue('krona_loyalty_active', pSQL(Tools::getValue('loyalty_active')), false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_gamification_active', pSQL(Tools::getValue('gamification_active')), false, $this->id_shop_group, $this->id_shop);

            if ($loyalty) {
                Configuration::updateValue('krona_loyalty_total', pSQL(Tools::getValue('loyalty_total')), false, $this->id_shop_group, $this->id_shop);
                Configuration::updateValue('krona_loyalty_product_page', pSQL(Tools::getValue('loyalty_product_page')), false, $this->id_shop_group, $this->id_shop);
            }
            if ($gamification) {
                Configuration::updateValue('krona_gamification_total', pSQL(Tools::getValue('gamification_total')), false, $this->id_shop_group, $this->id_shop);
                Configuration::updateValue('krona_display_name', (int)Tools::getValue('display_name'), false, $this->id_shop_group, $this->id_shop);
                Configuration::updateValue('krona_pseudonym', (bool)Tools::getValue('pseudonym'), false, $this->id_shop_group, $this->id_shop);
                Configuration::updateValue('krona_avatar', (bool)Tools::getValue('avatar'), false, $this->id_shop_group, $this->id_shop);
            }
            Configuration::updateValue('krona_url', pSQL(Tools::getValue('url')), false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_customer_active', (bool)Tools::getValue('customer_active'), false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_order_amount', pSQL(Tools::getValue('order_amount')), false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_order_rounding', pSQL(Tools::getValue('order_rounding')), false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_coupon_prefix', pSQL(Tools::getValue('coupon_prefix')), false, $this->id_shop_group, $this->id_shop);

            // Handling Status
            $orderStates = OrderState::getOrderStates($this->context->language->id);
            $order_state_selected = array();
            $order_state_cancel_selected = array();

            foreach ($orderStates as $orderState) {

                $id_order_state = $orderState['id_order_state'];

                if (Tools::isSubmit('order_state_'.$id_order_state)) {
                    $order_state_selected[] = $id_order_state;
                }
                if (Tools::isSubmit('order_state_cancel_'.$id_order_state)) {
                    $order_state_cancel_selected[] = $id_order_state;
                }

            }
            Configuration::updateValue('krona_order_state', implode(",", $order_state_selected), false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_order_state_cancel', implode(",", $order_state_cancel_selected), false, $this->id_shop_group, $this->id_shop);

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

        $url =  _PS_BASE_URL_._MODULE_DIR_.$this->name."genzo_krona_cron.php?secure_key=".$secureKey;

        if (Configuration::get('PS_SSL_ENABLED')==1) {
            $url = str_replace('http://', 'https://', $url);
        }

        return $url;
    }

}
