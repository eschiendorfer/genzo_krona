/* DB Changes after converting change to change_points/change_coins */

ALTER TABLE `PREFIX_genzo_krona_player_history`
    DROP COLUMN `change`;

ALTER TABLE `PREFIX_genzo_krona_player`
    DROP COLUMN `points`,
    DROP COLUMN `coins`,
    DROP COLUMN `loyalty`,
    DROP COLUMN `notification`;