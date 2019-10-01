/* Update to 2.0.0 */

ALTER TABLE `PREFIX_genzo_krona_player_history`
    ADD `change_points` INT NOT NULL AFTER `change`,
    ADD `change_coins` INT NOT NULL AFTER `change`;
