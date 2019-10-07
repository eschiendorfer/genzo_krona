/* Install */

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_player` (
  `id_customer` INT(12) NOT NULL,
  `pseudonym` VARCHAR(40) NOT NULL,
  `avatar` VARCHAR(200) DEFAULT NULL,
  `active` BOOL DEFAULT 1 NOT NULL,
  `banned` BOOL DEFAULT 0 NOT NULL,
  `date_add` DATETIME NULL,
  `date_upd` DATETIME NULL,
  PRIMARY KEY ( `id_customer` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_player_level` (
  `id` INT(12) NOT NULL AUTO_INCREMENT,
  `id_customer` INT(12) NOT NULL,
  `id_level` INT(12) NOT NULL,
  `active` BOOL DEFAULT 1 NOT NULL,
  `active_until` DATETIME NOT NULL,
  `achieved` INT(12) NOT NULL,
  `achieved_last` DATETIME NOT NULL,
  `date_add` DATETIME NULL,
  `date_upd` DATETIME NULL,
  PRIMARY KEY ( `id` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_player_history` (
  `id_history` INT(12) NOT NULL AUTO_INCREMENT,
  `id_customer` INT(12) NOT NULL,
  `id_action` INT(12) NOT NULL,
  `id_action_order` INT(12) NOT NULL,
  `id_order` INT(12) NOT NULL,
  `force_display` INT(12) NULL,
  `points` INT(12) NOT NULL,
  `coins` INT(12) NOT NULL,
  `loyalty` INT(12) NOT NULL,
  `loyalty_used` INT(12) NOT NULL,
  `loyalty_expired` INT(12) NOT NULL,
  `loyalty_expire_date` DATETIME NOT NULL,
  `url` VARCHAR(200) NULL,
  `viewable` BOOL NOT NULL DEFAULT 1,
  `viewed` BOOL NOT NULL DEFAULT 0,
  `date_add` DATETIME NULL,
  `date_upd` DATETIME NULL,
  PRIMARY KEY (`id_history`),
  INDEX id_customer (`id_customer`),
  INDEX id_action (`id_action`),
  INDEX id_action_order (`id_action_order`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_player_history_lang` (
  `id_history` INT(12) NOT NULL,
  `id_lang` INT(12) NOT NULL,
  `title` VARCHAR(200) NULL,
  `message` VARCHAR(2000) NULL,
  `comment` VARCHAR(2000) NULL,
  PRIMARY KEY ( `id_history`, `id_lang` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_level` (
  `id_level` INT(12) NOT NULL AUTO_INCREMENT,
  `achieve_max` INT(12) NOT NULL,
  `condition_type` VARCHAR(200) NOT NULL,
  `condition_time` INT(12) NOT NULL,
  `condition` INT(12) NOT NULL,
  `id_action` INT(12) NULL,
  `duration` INT(12) DEFAULT 0 NOT NULL,
  `reward_type` VARCHAR(200) NOT NULL,
  `id_reward` INT(12) DEFAULT 0 NOT NULL,
  `icon` VARCHAR(200) NULL,
  `active` INT(12) DEFAULT 0 NOT NULL,
  `position` INT(12) DEFAULT 0 NOT NULL,
  `hide` BOOL DEFAULT 0 NOT NULL,
  PRIMARY KEY ( `id_level` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_level_lang` (
  `id_level` INT(12) NOT NULL AUTO_INCREMENT,
  `id_lang` INT(12) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  PRIMARY KEY ( `id_level`, `id_lang` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_level_shop` (
  `id_level` INT(12) NOT NULL,
  `id_shop` INT(12) NOT NULL,
  PRIMARY KEY ( `id_level`, `id_shop` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_action` (
  `id_action` INT(12) NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(200) NOT NULL,
  `key` VARCHAR(100) NOT NULL,
  `points_change` INT(12) NOT NULL,
  `execution_type` VARCHAR(100) NOT NULL,
  `execution_max` INT(12) DEFAULT 0 NOT NULL,
  `active` BOOL DEFAULT 0 NOT NULL,
  PRIMARY KEY ( `id_action` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_action_order` (
  `id_action_order` INT(12) NOT NULL AUTO_INCREMENT,
  `id_currency` INT(12) NOT NULL,
  `coins_change` INT(12) NOT NULL,
  `coins_conversion` FLOAT(12,5) NOT NULL,
  `minimum_amount` INT(12) NOT NULL,
  `active` BOOL DEFAULT 0 NOT NULL,
  PRIMARY KEY ( `id_action_order` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_action_lang` (
  `id_action` INT(12) NOT NULL,
  `id_lang` INT(12) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` VARCHAR(2000) NULL,
  PRIMARY KEY ( `id_action`, `id_lang` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_action_shop` (
  `id_action` INT(12) NOT NULL,
  `id_shop` INT(12) NOT NULL,
  PRIMARY KEY ( `id_action`, `id_shop` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_action_order_shop` (
  `id_action_order` INT(12) NOT NULL,
  `id_shop` INT(12) NOT NULL,
  PRIMARY KEY ( `id_action_order`, `id_shop` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_genzo_krona_settings_group` (
  `id_group` INT(12) NOT NULL,
  `position` INT(12) NOT NULL,
  PRIMARY KEY ( `id_group` )
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;

