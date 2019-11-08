<?php

/**
 * 1_2_0.php
 *
 * @copyright Copyright 2003-2018 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version Author: Zen4All
 */

if (!zen_page_key_exists('configCashOnPickup') && (int)$configuration_group_id > 0) {
  zen_register_admin_page('configCashOnPickup', 'BOX_CONFIGURATION_CASH_ON_PICKUP', 'FILENAME_CONFIGURATION', 'gID=' . $configuration_group_id, 'configuration', 'N', $configuration_group_id);
}