<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   All rights reserved.
 */

// Note never add cronjob file here, this breaks whole FO
include_once(dirname(__FILE__).'/genzo_krona.php');

include_once(dirname(__FILE__).'/classes/Action.php');
include_once(dirname(__FILE__).'/classes/ActionOrder.php');
include_once(dirname(__FILE__).'/classes/Coupon.php');
include_once(dirname(__FILE__).'/classes/Group.php');
include_once(dirname(__FILE__).'/classes/Level.php');
include_once(dirname(__FILE__).'/classes/Player.php');
include_once(dirname(__FILE__).'/classes/PlayerHistory.php');
include_once(dirname(__FILE__).'/classes/PlayerLevel.php');