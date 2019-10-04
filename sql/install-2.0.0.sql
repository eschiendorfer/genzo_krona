ALTER TABLE `PREFIX_genzo_krona_player_history`
    ADD `coins` INT NOT NULL AFTER `change`,
    ADD `points` INT NOT NULL AFTER `change`,
    ADD `force_display` INT NULL DEFAULT NULL AFTER `change`,
    ADD `loyalty_expire_date` DATETIME NULL AFTER `change_loyalty`,
    ADD `loyalty_expired` INT NOT NULL AFTER `change_loyalty`,
    ADD `loyalty_used` INT NOT NULL AFTER `change_loyalty`,
    ADD `viewable` BOOL NOT NULL DEFAULT 1 AFTER `url`,
    ADD `viewed` BOOL NOT NULL DEFAULT 0 AFTER `url`;

ALTER TABLE `PREFIX_genzo_krona_player_history`
    CHANGE `change_loyalty` `loyalty` INT(12) NOT NULL;

ALTER TABLE `PREFIX_genzo_krona_player_history`
    ADD INDEX `id_customer` (`id_customer`),
    ADD INDEX `id_action` (`id_action`),
    ADD INDEX `id_action_order` (`id_action_order`);

ALTER TABLE `PREFIX_genzo_krona_player_level`
    CHANGE `id` `id_player_level` INT(12) AUTO_INCREMENT NOT NULL;