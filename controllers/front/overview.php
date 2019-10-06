<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;
use KronaModule\PlayerHistory;
use KronaModule\PlayerLevel;

class Genzo_KronaOverviewModuleFrontController extends ModuleFrontController {

	public function initContent() {
		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = false;

        parent::initContent();

        // Check if there needs to be a redirection
        if (!$this->context->customer->isLogged() || !$id_customer = $this->context->customer->id) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'home'));
        }

        $playerObj = new Player($id_customer);

        if ($playerObj->banned) {
            Tools::redirect($krona_url = $this->context->link->getModuleLink('genzo_krona', 'home').'?banned=1');
        }
        elseif (!$playerObj->active) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'customersettings'));
        }

        // Handle notification
        Db::getInstance()->update('genzo_krona_player_history', ['viewed' => 1], 'id_customer='.$id_customer);

        $game_name = Configuration::get('krona_game_name', $this->context->language->id);

        $this->context->smarty->assign(array(
            'meta_title' => $game_name . ': ' . $this->module->l('Overview'),
            'game_name' => $game_name,
            'total_name' => Configuration::get('krona_total_name', $this->context->language->id),
            'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id),
            'active' => 'Overview',
            'player' => json_decode(json_encode($playerObj), true),
            'rank' => $playerObj->getRank(),
            'history' => PlayerHistory::getHistoryByPlayer($id_customer, ['viewable=1'], ['limit' => 5, 'offset' => 0]),
            'level' => PlayerLevel::getLastPlayerLevel($id_customer),
            'loyalty' => Configuration::get('krona_loyalty_active'),
            'gamification' => Configuration::get('krona_gamification_active'),
            'actions' => Player::getPossibleActions($id_customer),
        ));

        $this->setTemplate('overview.tpl');
	}

}