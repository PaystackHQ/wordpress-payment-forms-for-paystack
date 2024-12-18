<?php
/*
  Plugin Name:  Payment Forms for Paystack
  Plugin URI:   https://github.com/PaystackHQ/Wordpress-Payment-forms-for-Paystack
  Description:  Payment Forms for Paystack allows you create forms that will be used to bill clients for goods and services via Paystack.
  Version:      4.0.1
  Author:       Paystack
  Author URI:   http://paystack.com
  License:      GPL-2.0+
  License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
*/
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
define( 'PFF_PAYSTACK_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PFF_PAYSTACK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PFF_PAYSTACK_MAIN_FILE', __FILE__ );
define( 'PFF_PAYSTACK_VERSION', '4.0.1' );
define( 'PFF_PAYSTACK_TABLE', 'paystack_forms_payments' );
define( 'PFF_PLUGIN_BASENAME', plugin_basename(__FILE__) );
define( 'PFF_PLUGIN_NAME', 'pff-paystack' );

// Transaction definitions
define( 'PFF_PAYSTACK_PERCENTAGE', 1.5 );
define( 'PFF_PAYSTACK_CROSSOVER_TOTAL', 2500 );
define( 'PFF_PAYSTACK_ADDITIONAL_CHARGE', 100 );
define( 'PFF_PAYSTACK_LOCAL_CAP', 2000 );

include_once PFF_PAYSTACK_PLUGIN_PATH . '/includes/classes/class-paystack-forms.php';

/**
 * Returns an instance of the Paystack Payment forms Object
 *
 * @return object \paystack\payment_forms\Payment_Forms()
 */
function pff_paystack() {
	return \paystack\payment_forms\Payment_Forms::get_instance();
}
$_GLOBAL['pff_paystack'] = pff_paystack();
