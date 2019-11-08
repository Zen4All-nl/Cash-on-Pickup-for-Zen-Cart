<?php

/*
 * init_cash_on_pickup.php
 *
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version Author: Erik Kerkhoven 1-3-2019
 * 
 */

if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}

$module_constant = 'CASH_ON_PICKUP_VERSION';
$module_installer_directory = DIR_FS_ADMIN . 'includes/installers/cash_on_pickup';
$module_name = 'Cash on Pickup';
$module_file_for_version_check = '';
$zencart_com_plugin_id = 1242;

$configuration_group_id = '';
if (defined($module_constant)) {
  $current_version = constant($module_constant);
} else {
  $current_version = '0.0.0';
  $db->Execute("INSERT INTO " . TABLE_CONFIGURATION_GROUP . " (configuration_group_title, configuration_group_description, sort_order, visible)
                VALUES ('" . $module_name . "', '" . $module_name . " Settings', '1', '0');");
  $configuration_group_id = $db->Insert_ID();

  $db->Execute("UPDATE " . TABLE_CONFIGURATION_GROUP . "
                SET sort_order = " . $configuration_group_id . "
                WHERE configuration_group_id = " . $configuration_group_id . ";");

  $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, last_modified, date_added, set_function)
                VALUES ('Version', '" . $module_constant . "', '0.0.0', 'Version installed:', " . $configuration_group_id . ", 0, NOW(), NOW(), 'trim(');");
}
if ($configuration_group_id == '') {
  $config = $db->Execute("SELECT configuration_group_id
                          FROM " . TABLE_CONFIGURATION . "
                          WHERE configuration_key= '" . $module_constant . "'");
  $configuration_group_id = $config->fields['configuration_group_id'];
}

$installers = scandir($module_installer_directory, 1);

$newest_version = $installers[0];
$newest_version = substr($newest_version, 0, -4);

sort($installers);
if (version_compare($newest_version, $current_version) > 0) {
  foreach ($installers as $installer) {
    if (version_compare($newest_version, substr($installer, 0, -4)) >= 0 && version_compare($current_version, substr($installer, 0, -4)) < 0) {
      include($module_installer_directory . '/' . $installer);
      $current_version = str_replace("_", ".", substr($installer, 0, -4));
      $db->Execute("UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_value = '" . $current_version . "'
                    WHERE configuration_key = '" . $module_constant . "'
                    LIMIT 1;");
      $messageStack->add("Installed " . $module_name . " v" . $current_version, 'success');
    }
  }
}

// Version Checking
$module_file_for_version_check = ($module_file_for_version_check != '') ? DIR_FS_ADMIN . $module_file_for_version_check : '';
if ($zencart_com_plugin_id != 0 && $module_file_for_version_check != '' && $_SERVER["PHP_SELF"] == $module_file_for_version_check) {
  $new_version_details = plugin_version_check_for_updates($zencart_com_plugin_id, $current_version);
  if ($_GET['gID'] == $configuration_group_id && $new_version_details != false) {
    $messageStack->add("Version " . $new_version_details['latest_plugin_version'] . " of " . $new_version_details['title'] . ' is available at <a href="' . $new_version_details['link'] . '" target="_blank">[Details]</a>', 'caution');
  }
}

if (!function_exists('plugin_version_check_for_updates')) {

  function plugin_version_check_for_updates($plugin_file_id = 0, $version_string_to_compare = '') {
    if ($plugin_file_id == 0) {
      return false;
    }
    $new_version_available = false;
    $lookup_index = 0;
    $url = 'https://www.zen-cart.com/downloads.php?do=versioncheck' . '&id=' . (int)$plugin_file_id;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Plugin Version Check [' . (int)$plugin_file_id . '] ' . HTTP_SERVER);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    if ($error > 0) {
      curl_setopt($ch, CURLOPT_URL, str_replace('tps:', 'tp:', $url));
      $response = curl_exec($ch);
      $error = curl_error($ch);
    }
    curl_close($ch);
    if ($error > 0 || $response == '') {
      $response = file_get_contents($url);
    }
    if ($response === false) {
      $response = file_get_contents(str_replace('tps:', 'tp:', $url));
    }
    if ($response === false) {
      return false;
    }
    $data = json_decode($response, true);
    if (!$data || !is_array($data)) {
      return false;
    }
    // compare versions
    if (strcmp($data[$lookup_index]['latest_plugin_version'], $version_string_to_compare) > 0) {
      $new_version_available = true;
    }
    // check whether present ZC version is compatible with the latest available plugin version
    if (!in_array('v' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR, $data[$lookup_index]['zcversions'])) {
      $new_version_available = false;
    }

    return ($new_version_available) ? $data[$lookup_index] : false;
  }

}
