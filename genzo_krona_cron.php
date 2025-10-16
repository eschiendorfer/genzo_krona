<?php
/**
 * Copyright (C) 2025 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2025 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}

include_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

use \KronaModule\PlayerLevel;
use \KronaModule\Action;
use \KronaModule\Player;

if (isset($_GET['secure_key'])) {

    $secureKey = md5(_COOKIE_KEY_ . Configuration::get('PS_SHOP_NAME'));

    if (!empty($secureKey) && $secureKey === $_GET['secure_key']) {

        if (Configuration::get('krona_newsletter_cron', null, 0, 0) != date('Y-m-d')) {

            // Executions
            PlayerLevel::executeCronSetbackLevels();
            Action::executeNewsletterCron();
            Player::cronExpireLoyalty();

            // Update the date, so that the cron is only executed once a day
            Configuration::updateGlobalValue('krona_newsletter_cron', date('Y-m-d'));

            echo "executed";
            return true;
        }
    }
}



