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

        $id_customer = 0;

        if ($referral_code = strtoupper(Tools::getValue('referral_code'))) {
            $id_customer = Player::getIdByReferralCode($referral_code);
        }
        else if ($this->context->customer->isLogged()) {
            $id_customer = $this->context->customer->id;
        }

        if (!$id_customer) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'home'));
        }

        $playerObj = new Player($id_customer);

        if ($playerObj->banned) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'home').'?banned=1');
        }
        elseif (!$playerObj->active) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'customersettings'));
        }

        // Handle notification
        Db::getInstance()->update('genzo_krona_player_history', ['viewed' => 1], 'id_customer='.$id_customer);

        $game_name = Configuration::get('krona_game_name', $this->context->language->id);

        $this->context->smarty->assign(array(
            'meta_title' => $game_name . ': ' . $this->module->l('Overview'),
            'nobots' => true, // Player sites shouldn't be indexed as they are anyway a lot of duplicate content
            'game_name' => $game_name,
            'total_name' => Configuration::get('krona_total_name', $this->context->language->id),
            'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id),
            'active' => 'Overview',
            'player' => json_decode(json_encode($playerObj), true),
            'rank' => $playerObj->getRank(),
            'total_players' => Player::getTotalPlayers(),
            'history' => PlayerHistory::getHistoryByPlayer($id_customer, ['viewable=1'], ['limit' => 5, 'offset' => 0]),
            'level' => PlayerLevel::getLastPlayerLevel($id_customer),
            'loyalty' => Configuration::get('krona_loyalty_active'),
            'gamification' => Configuration::get('krona_gamification_active'),
            'referral' => Configuration::get('krona_referral_active'),
            'actions' => Player::getPossibleActions($id_customer),
            'color_scheme' => 'red',
            'own_profile' => $id_customer==$this->context->customer->id,
        ));

        $this->setTemplate('overview.tpl');
	}

}