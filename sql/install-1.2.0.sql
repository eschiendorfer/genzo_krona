/* Update to 1.2.0 */

ALTER TABLE `PREFIX_genzo_krona_player`
  ADD `loyalty_expire` DATETIME NOT NULL AFTER `loyalty`;

ALTER TABLE `PREFIX_genzo_krona_action`
  ADD `url` VARCHAR(300) NOT NULL AFTER `key`;