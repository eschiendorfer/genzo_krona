<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

use KronaModule\ActionOrder;

class AdminGenzoKronaOrdersController extends ModuleAdminController {

    public $loyalty_name;

    /* @var ActionOrder object */
    protected $object;

    public function __construct() {

        $this->module = 'genzo_krona';
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->className = 'KronaModule\ActionOrder';
        $this->table = 'genzo_krona_action_order';
        $this->identifier = 'id_action_order';
        $this->lang = false;

        Shop::addTableAssociation($this->table, array('type' => 'shop'));

        $this->_select = 'c.name';
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'currency c ON (c.id_currency = a.id_currency) ';

        $fields_list['id_action_order'] = array(
            'title' => 'ID',
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'filter_type' => 'int',
        );

        $fields_list['name']= array(
            'title' => $this->l('Currency'),
            'align' => 'left',
        );

        $fields_list['coins_change'] = array(
            'title' => $this->l('Coins Change'),
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'filter_type' => 'int'
        );

        if (Configuration::get('krona_referral_active')) {
            $fields_list['coins_change_referrer'] = array(
                'title' => $this->l('Ref: Coins Referrer'),
                'align' => 'center',
            );
            $fields_list['coins_change_buyer'] = array(
                'title' => $this->l('Ref: Coins Buyer'),
                'align' => 'center',
            );
        }

        $fields_list['minimum_amount'] = array(
            'title' => $this->l('Minimum Amount'),
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

        $this->fields_list = $fields_list;
        $this->actions = array('edit');
        $this->_defaultOrderBy = 'id_action_order';
        $this->_defaultOrderWay = 'ASC';
        $this->bulk_actions = [];

        parent::__construct();

    }

    public function init() {

        parent::init();

        // Configuration
        $this->loyalty_name = Configuration::get('krona_loyalty_name', $this->context->language->id);
    }

    public function renderList() {

        ActionOrder::checkCurrencies();

        return parent::renderList();
    }

    public function initContent() {

        // Some Basic Display functions
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();

        if ($this->display == 'edit') {
            if (!$this->loadObject(true)) {
                return;
            }
            $this->content = $this->renderForm();
        }
        else {
            $this->content = $this->renderList();
        }

        // This are the real smarty variables
        $this->context->smarty->assign(
            array(
                'content'   => $this->content,
                'tab'       => 'Orders',
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

    public function renderForm() {

        $inputs[] = array(
            'type' => 'hidden',
            'name' => 'id_action_order'
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
            'type'  => 'text',
            'name'  => 'minimum_amount',
            'label' => $this->l('Minimum Amount'),
            'desc'  => sprintf($this->l('Needs there to be a minimum amount of %s to get coins? If not, set it equal to 0.'), $this->object->currency),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->object->currency_iso,
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins_change',
            'label' => $this->l('Coins reward'),
            'desc'  => sprintf($this->l('Example: For every %s spent, the user will get X coins.'),$this->object->currency),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->l('Coins').'/'.$this->object->currency_iso,
        );

        if (Configuration::get('krona_referral_active')) {
            $inputs[] = array(
                'type'  => 'text',
                'name'  => 'coins_change_buyer',
                'label' => $this->l('Referral coins reward buyer'),
                'desc'  => sprintf($this->l('Example: For every %s spent, the buyer will get X coins. This always needs to be higher than basic coins reward above.'),$this->object->currency),
                'class'  => 'input fixed-width-sm',
                'suffix' => $this->l('Coins').'/'.$this->object->currency_iso,
            );

            $inputs[] = array(
                'type'  => 'text',
                'name'  => 'coins_change_referrer',
                'label' => $this->l('Referral coins reward referrer'),
                'desc'  => sprintf($this->l('Example: For every %s spent, the referrer will get X coins.'),$this->object->currency),
                'class'  => 'input fixed-width-sm',
                'suffix' => $this->l('Coins').'/'.$this->object->currency_iso,
            );
        }

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins_change_max',
            'label' => $this->l('Max coins change'),
            'desc'  => $this->l('Do you want set a max value of coins change? If not, set it equal to 0.'),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->l('Coins'),
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins_conversion',
            'label' => $this->l('Loyalty conversion'),
            'desc'  => sprintf($this->l('Example: For every loyalty point a customer has collected, he can generate a voucher with the value of X %s.'),$this->object->currency),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->object->currency_iso.'/'.$this->loyalty_name,
        );

        if (Shop::isFeatureActive()) {
            $inputs[] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association:'),
                'name' => 'checkBoxShopAsso',
            );
        }

        $fields_form = array(
            'legend' => array(
                'title' => $this->l('Edit Order Action:'). ' '. $this->object->currency,
                'icon' => 'icon-cogs'
            ),
            'description' => $this->l('Note: The other settings like order status, title or message can be set globally under "Settings".'),
            'input' => $inputs,
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        // Fix of values since we dont use always same names
        $this->fields_form = $fields_form;

        $this->tpl_form_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return parent::renderForm();
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

}
