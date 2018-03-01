<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;
use KronaModule\PlayerHistory;

class Genzo_KronaTimelineModuleFrontController extends ModuleFrontController
{
    public $errors;

	public function initContent()
	{	
		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = true;

        parent::initContent();

        if (!$this->context->customer->isLogged()) {
            $krona_url = $this->context->link->getModuleLink('genzo_krona', 'home');
            Tools::redirect($krona_url);
        }

        $id_lang = $this->context->language->id;
        $id_shop_group = $this->context->shop->id_shop_group;
        $id_shop = $this->context->shop->id_shop;
        $id_customer = $this->context->customer->id;

        // Check if there needs to be a redirction
        if (!$this->context->customer->isLogged()) {
            $krona_url = $this->context->link->getModuleLink('genzo_krona', 'home');
            Tools::redirect($krona_url);
        }
        elseif (!Player::checkIfPlayerIsActive($id_customer)) {
            $settings_url = $this->context->link->getModuleLink('genzo_krona', 'customersettings');
            Tools::redirect($settings_url);
        }

        $game_name = Configuration::get('krona_game_name', $id_lang, $id_shop_group, $id_shop);

        // Pagination
        $items = 10;
        (Tools::getValue('page')) ? $page = Tools::getValue('page') : $page = 1;

        $history_pagination = array(
            'limit' => $items,
            'offset' => ($page-1) * $items,
        );

        $pages = ceil(PlayerHistory::getTotalHistoryByPlayer($id_customer, null)/$items);


		$this->context->smarty->assign(array(
            'meta_title' => $game_name.': '. $this->module->l('Timeline'),
            'game_name' => $game_name,
            'slack' => Configuration::get('krona_url', null, $id_shop_group, $id_shop),
            'confirmation' => $this->confirmation,
            'errors' => $this->errors,
            'active' => 'Timeline',
            'history' => PlayerHistory::getHistoryByPlayer($id_customer, null, $history_pagination),
            'pages' => $pages,
            'page' => $page,
		));

		$this->setTemplate('timeline.tpl');
	}


}