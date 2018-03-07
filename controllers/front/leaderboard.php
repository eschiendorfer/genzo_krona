<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;

class Genzo_KronaLeaderboardModuleFrontController extends ModuleFrontController
{

	public function initContent()
	{	
		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = false;

        parent::initContent();

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

        $filters = array(
            'p.`active` = 1',
            'p.`banned` = 0',
        );

        // Pagination
        $items = 50;
        (Tools::getValue('page')) ? $page = Tools::getValue('page') : $page = 1;

        $player_pagination = array(
            'limit' => $items,
            'offset' => ($page-1) * $items,
        );

        $order = array(
            'order_by' => 'points` DESC, `pseudonym', // a bit ugly... consider to go for a multidimensional array
            'order_way' => 'ASC',
        );

        $pages = ceil(Player::getTotalPlayers()/$items);

		$this->context->smarty->assign(array(
            'meta_title' => $game_name.': '.$this->module->l('Leaderboard'),
            'game_name' => $game_name,
            'total_name' => Configuration::get('krona_total_name', $id_lang, $id_shop_group, $id_shop),
            'active' => 'Players',
            'players' => Player::getAllPlayers($filters, $player_pagination, $order),
            'pages' => $pages,
            'page' => $page,
		));

		$this->setTemplate('leaderboard.tpl');
	}

}