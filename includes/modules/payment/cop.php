<?php
/**
 * COD Payment Module
 *
 * @package paymentMethod
 * @copyright Copyright 2016 Zen4All
 * @copyright Portions Copyright 2003-2018 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: Zen4All
 */
class cop {

  var $code, $title, $description, $enabled;

// class constructor
  function __construct()
  {
    global $order;

    $this->code = 'cop';
    $this->title = MODULE_PAYMENT_COP_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_COP_TEXT_DESCRIPTION;
    $this->sort_order = defined('MODULE_PAYMENT_COP_SORT_ORDER') ? MODULE_PAYMENT_COP_SORT_ORDER : null;
    $this->enabled = (defined('MODULE_PAYMENT_COP_STATUS') && MODULE_PAYMENT_COP_STATUS == 'True');

    if (defined('MODULE_PAYMENT_COP_ORDER_STATUS_ID') && (int)MODULE_PAYMENT_COP_ORDER_STATUS_ID > 0) {
      $this->order_status = MODULE_PAYMENT_COP_ORDER_STATUS_ID;
    }

    if (is_object($order)) {
      $this->update_status();
    }
  }

// class methods
  function update_status()
  {
    global $order, $db;

    if (stristr($_SESSION['shipping']['id'], 'storepickup') == FALSE) {
      $this->enabled = ((MODULE_PAYMENT_COP_STATUS == 'True') ? true : false);
      $check_flag = false;
      $check = $db->Execute("SELECT zone_id
                             FROM " . TABLE_ZONES_TO_GEO_ZONES . "
                             WHERE geo_zone_id = '" . MODULE_PAYMENT_COP_ZONE . "'
                             AND zone_country_id = " . (int)$order->delivery['country']['id'] . "
                             ORDER BY zone_id");
      foreach ($check as $item) {
        if ($item['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($item['zone_id'] == $order->delivery['zone_id']) {
          $check_flag = true;
          break;
        }
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
    }

// disable the module if the order only contains virtual products
    if ($this->enabled == true) {
      if ($order->content_type != 'physical') {
        $this->enabled = false;
      }
    }

    // other status checks?
    if ($this->enabled) {
      // other checks here
    }
  }

  function javascript_validation()
  {
    return false;
  }

  function selection()
  {
    return array(
      'id' => $this->code,
      'module' => $this->title
    );
  }

  function pre_confirmation_check()
  {
    return false;
  }

  function confirmation()
  {
    return false;
  }

  function process_button()
  {
    return false;
  }

  function before_process()
  {
    return false;
  }

  function after_process()
  {
    return false;
  }

  function get_error()
  {
    return false;
  }

  function check()
  {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("SELECT configuration_value
                                   FROM " . TABLE_CONFIGURATION . "
                                   WHERE configuration_key = 'MODULE_PAYMENT_COP_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    return $this->_check;
  }

  function install()
  {
    global $db, $messageStack;
    if (defined('MODULE_PAYMENT_COP_STATUS')) {
      $messageStack->add_session('COP module already installed.', 'error');
      zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=cop', 'NONSSL'));
      return 'failed';
    }
    $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added)
                  VALUES ('Enable Cash On Delivery Module', 'MODULE_PAYMENT_COP_STATUS', 'True', 'Do you want to accept Cash On Pickup payments?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added)
                  VALUES ('Payment Zone', 'MODULE_PAYMENT_COP_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
    $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
                  VALUES ('Sort order of display.', 'MODULE_PAYMENT_COP_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added)
                  VALUES ('Set Order Status', 'MODULE_PAYMENT_COP_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
  }

  function remove()
  {
    global $db;
    $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')");
  }

  function keys()
  {
    return array('MODULE_PAYMENT_COP_STATUS', 'MODULE_PAYMENT_COP_ZONE', 'MODULE_PAYMENT_COP_ORDER_STATUS_ID', 'MODULE_PAYMENT_COP_SORT_ORDER');
  }

}
