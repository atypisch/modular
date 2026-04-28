-- =============================================================================
-- Verwijder overbodige tabellen (legacy payment/subscription/payout)
-- Copy-paste in phpMyAdmin → SQL. Veilig: IF EXISTS, FK uit tijdens drop.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `simuze_order_items`;
DROP TABLE IF EXISTS `simuze_download_tokens`;
DROP TABLE IF EXISTS `simuze_orders`;
DROP TABLE IF EXISTS `simuze_discounts`;
DROP TABLE IF EXISTS `simuze_transactions`;
DROP TABLE IF EXISTS `simuze_seller_accounts`;
DROP TABLE IF EXISTS `simuze_payouts`;
DROP TABLE IF EXISTS `simuze_group_payments`;
DROP TABLE IF EXISTS `simuze_subscriptions`;
DROP TABLE IF EXISTS `simuze_plays_subscription`;
DROP TABLE IF EXISTS `simuze_plays_total`;
DROP TABLE IF EXISTS `simuze_artist_payouts`;

SET FOREIGN_KEY_CHECKS = 1;
