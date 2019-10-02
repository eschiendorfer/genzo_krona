/* Update to 2.0.0 */

ALTER TABLE `PREFIX_genzo_krona_player_history`
    ADD `coins` INT NOT NULL AFTER `change`,
    ADD `points` INT NOT NULL AFTER `change`,
    ADD `loyalty_expire_date` DATETIME NULL AFTER `change_loyalty`,
    ADD `loyalty_expired` INT NOT NULL AFTER `change_loyalty`,
    ADD `loyalty_used` INT NOT NULL AFTER `change_loyalty`,
    ADD `viewable` BOOL NOT NULL DEFAULT 1 AFTER `url`,
    ADD `viewed` BOOL NOT NULL DEFAULT 0 AFTER `url`;

/* Not this only works if we use a second ALTER Table */
ALTER TABLE `PREFIX_genzo_krona_player_history`
    CHANGE `change_loyalty` `loyalty` INT(12) NOT NULL;
