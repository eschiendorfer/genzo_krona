<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

include_once(dirname(__FILE__).'/classes/Action.php');

use KronaModule\Action;

// Page Visit
if(Tools::getValue('page_visit')) {
    $id_customer = (int)Tools::getValue('page_visit');
    Action::triggerPageVisit($id_customer);
    echo true;
}
