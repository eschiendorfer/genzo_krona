<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\PlayerHistory;
use KronaModule\Player;
use KronaModule\PlayerLevel;

class Genzo_KronaHomeModuleFrontController extends ModuleFrontController {

	public function initContent() {

		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = false;

        parent::initContent();

        $game_name = Configuration::get('krona_game_name', $this->context->language->id);

        $this->context->smarty->assign(array(
            'meta_title' => $game_name,
            'game_name' => $game_name,
            'points_name' => Configuration::get('krona_points_name', $this->context->language->id),
            'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id),
            'description' => Configuration::get('krona_description', $this->context->language->id),
            'active' => 'Home',
            'nav' => $this->context->customer->isLogged() ? true : false,
            'loyalty' => Configuration::get('krona_loyalty_active', null),
            'banned' => Tools::getValue('banned') ? true : false,
        ));

        $this->setTemplate('home.tpl');

	}

}