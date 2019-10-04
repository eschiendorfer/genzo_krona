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
use KronaModule\PlayerLevel;

class Genzo_KronaOverviewModuleFrontController extends ModuleFrontController
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

        $playerObj = new Player($id_customer);

        // Check if there needs to be a redirction
        if (!$this->context->customer->isLogged()) {
            $krona_url = $this->context->link->getModuleLink('genzo_krona', 'home');
            Tools::redirect($krona_url);
        }
        elseif ($playerObj->banned) {
            $krona_url = $this->context->link->getModuleLink('genzo_krona', 'home').'?banned=1';
            Tools::redirect($krona_url);
        }
        elseif (!$playerObj->active) {
            $settings_url = $this->context->link->getModuleLink('genzo_krona', 'customersettings');
            Tools::redirect($settings_url);
        }

        // Handle notification
        Db::getInstance()->update('genzo_krona_player_history', ['viewed' => 1], 'id_customer='.$id_customer);

        $game_name = Configuration::get('krona_game_name', $id_lang, $id_shop_group, $id_shop);

        $player = json_decode(json_encode($playerObj), true); // Turns an object into an array

        $history_pagination = array(
            'limit' => 5,
            'offset' => 0,
        );

        $this->context->smarty->assign(array(
            'meta_title' => $game_name . ': ' . $this->module->l('Overview'),
            'game_name' => $game_name,
            'total_name' => Configuration::get('krona_total_name', $id_lang, $id_shop_group, $id_shop),
            'loyalty_name' => Configuration::get('krona_loyalty_name', $id_lang, $id_shop_group, $id_shop),
            'confirmation' => $this->confirmation,
            'errors' => $this->errors,
            'active' => 'Overview',
            'player' => $player,
            'rank' => $playerObj->getRank(),
            'history' => PlayerHistory::getHistoryByPlayer($id_customer, ['viewable=1'], $history_pagination),
            'level' => PlayerLevel::getLastPlayerLevel($id_customer),
            'loyalty' => Configuration::get('krona_loyalty_active', null, $id_shop_group, $id_shop),
            'gamification' => Configuration::get('krona_gamification_active', null, $id_shop_group, $id_shop),
            'actions' => Player::getPossibleActions($id_customer),
        ));

        $this->setTemplate('overview.tpl');


	}

}