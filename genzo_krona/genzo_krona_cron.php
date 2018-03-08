<?php
/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
include(dirname(__FILE__).'/../../config/config.inc.php');

include_once(dirname(__FILE__).'/classes/PlayerLevel.php');
include_once(dirname(__FILE__).'/classes/Action.php');

use \KronaModule\PlayerLevel;
use \KronaModule\Action;

if (isset($_GET['secure_key'])) {

    $secureKey = md5(_COOKIE_KEY_ . Configuration::get('PS_SHOP_NAME'));

    if (!empty($secureKey) && $secureKey === $_GET['secure_key']) {
        // Todo: Remove 'bla' in live
        if (Configuration::get('krona_newsletter_cron', null, 0, 0) != date('Y-m-d'.'bla')) {

            // Executions
            PlayerLevel::executeCronSetbackLevels();
            Action::executeNewsletterCron();

            // Update the date, so that the cron is only executed once a day
            Configuration::updateGlobalValue('krona_newsletter_cron', date('Y-m-d'));

            echo "executed";
            return true;
        }
    }
}



