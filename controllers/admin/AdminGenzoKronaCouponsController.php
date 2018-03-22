<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class AdminGenzoKronaCouponsController extends AdminCartRulesControllerCore
{
    public function __construct() {

        parent::__construct();

    }

    public function initContent() {

        // Some Basic Display functions
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();


        $this->content = $this->renderList();


        // This are the real smarty variables
        $this->context->smarty->assign(
            array(
                'content'   => $this->content,
                'tab'       => 'Coupons',
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

    public function renderList() {

        $id_lang = $this->context->language->id;

        $this->_filter = " AND b.`name` LIKE 'KronaTemplate%'";

        $this->fields_list['levels'] = array(
            'title' => $this->l('In Levels'),
            'align' => 'center',
            'class' => 'fixed-width-md',
        );

        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'genzo_krona_level k ON (k.`id_reward` = a.`id_cart_rule` AND k.`reward_type`=\'coupon\')';
        $this->_join.= 'LEFT JOIN '._DB_PREFIX_.'genzo_krona_level_lang kl ON (kl.`id_level` = k.`id_level` AND kl.`id_lang` = '.$id_lang.')';

        $this->_select = "GROUP_CONCAT(kl.name SEPARATOR ', ') AS levels";
        $this->_group = 'GROUP BY a.`id_cart_rule` ';


        $this->actions = array('edit');
        $this->toolbar_title = $this->l('Krona Template Coupons');
        $this->token = Tools::getAdminTokenLite('AdminCartRules');
        $this->list_simple_header = true;
        AdminController::$currentIndex = $this->context->link->getAdminLink('AdminCartRules', false);

        unset($this->fields_list['active']);

        return parent::renderList();
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
