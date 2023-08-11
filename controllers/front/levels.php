<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;
use KronaModule\PlayerLevel;

class Genzo_KronaLevelsModuleFrontController extends ModuleFrontController {

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

        // Check if there needs to be a redirection
        if ($playerObj->banned) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'home').'?banned=1');
        }
        elseif (!$playerObj->active) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'customersettings'));
        }

        $game_name = Configuration::get('krona_game_name', $this->context->language->id);
        $levels = PlayerLevel::getAllPlayerLevels($id_customer, array('l.`active`=1 AND (pl.`active`=1 OR l.`hide`=0)'));

		$this->context->smarty->assign(array(
		    'grid' => Configuration::get('krona_levels_grid'),
            'meta_title' => $game_name.': '. $this->module->l('Timeline'),
            'game_name' => $game_name,
            'player_name' => $playerObj->display_name,
            'total_name' => Configuration::get('krona_total_name', $this->context->language->id),
            'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id),
            'active' => 'Levels',
            'levels' => $levels,
            'current_level' => end($levels),
            'next_level' => json_decode(json_encode(PlayerLevel::getNextPlayerLevel($id_customer)), true),
            'gamification' => Configuration::get('krona_gamification_active'),
            'loyalty' => Configuration::get('krona_loyalty_active'),
            'color_scheme' => 'red',
		));

		$this->setTemplate('levels.tpl');
	}

}