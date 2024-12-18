<?php
/**
 * A class of helper functions that are used in many places.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Helper class.
 */
class Helpers {

	/**
	 * Holds class isntance
	 *
	 * @var object \paystack\payment_forms\Helpers
	 */
	protected static $instance = null;

	/**
	 * The array of meta keys and their default values.
	 *
	 * @var array
	 */
	protected $defaults = [];

	/**
	 * An array of the allowed HTML tags
	 *
	 * @var array
	 */
	protected $allowed_html = [];

	/**
	 * Construct the class.
	 */
	public function __construct() {
		$this->defaults = [
			'amount'              => 0,
			'paybtn'              => esc_html__( 'Pay', 'pff-paystack' ),
			'successmsg'          => esc_html__( 'Thank you for paying!', 'pff-paystack' ),
			'txncharge'           => 'merchant',
			'loggedin'            => '',
			'currency'            => 'NGN',
			'filelimit'           => 2,
			'redirect'            => '',
			'minimum'             => 0,
			'usevariableamount'   => 0,
			'variableamount'      => 'Please configure your options:0,None:0',
			'hidetitle'           => 0,
			'loggedin'            => 'no',
			'recur'               => 'no',
			'recurplan'           => '',
			'subject'             => esc_html__( 'Thank you for your payment', 'pff-paystack' ),
			'heading'             => esc_html__( 'We\'ve received your payment', 'pff-paystack' ),
			'message'             => esc_html__( 'Your payment was received and we appreciate it.', 'pff-paystack' ),
			'sendreceipt'         => 'yes',
			'sendinvoice'         => 'yes',
			'usequantity'         => 'no',
			'useinventory'        => 'no',
			'inventory'           => 0,
			'sold'                => 0,
			'quantity'            => 10,
			'quantityunit'        => esc_html__( 'Quantity', 'pff-paystack' ),
			'useagreement'        => 'no',
			'agreementlink'       => '',
			'subaccount'          => '',
			'txnbearer'           => 'account',
			'merchantamount'      => '',
			'startdate_days'      => '',
			'startdate_plan_code' => '',
			'startdate_enabled'   => 0,
		];

		$this->allowed_html = array(
			'small' => array(
				'href' => true,
				'target' => true
			),
			'a' => array(
				'href' => true,
				'target' => true
			),
			'p' => array(),
			'input' => array(
				'type' => true,
				'name' => true,
				'value' => true,
				'class' => true,
				'checked' => true
			),
			'br' => array(),
			'label' => array(
				'for' => true
			),
			'code' => array(),
			'select' => array(
				'class' => true,
				'name' => true,
				'id' => true,
				'style' => true
			),
			'option' => array(
				'value' => true,
				'selected' => true
			),
			'textarea' => array(
				'rows' => true,
				'name' => true,
				'class' => true
			)
			);
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object \paystack\payment_forms\Payment_Forms
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	// GETTERS

	/**
	 * Returns the fee settings save or the default values.
	 *
	 * @return array
	 */
	public function get_fees() {
		$ret = [];
		$ret['prc'] = intval( floatval( esc_attr( get_option( 'prc', PFF_PAYSTACK_PERCENTAGE ) ) ) * 100 ) / 10000;
		$ret['ths'] = intval( floatval( esc_attr( get_option( 'ths', PFF_PAYSTACK_CROSSOVER_TOTAL ) ) ) * 100 );
		$ret['adc'] = intval( floatval( esc_attr( get_option( 'adc', PFF_PAYSTACK_ADDITIONAL_CHARGE ) ) ) * 100 );
		$ret['cap'] = intval( floatval( esc_attr( get_option( 'cap', PFF_PAYSTACK_LOCAL_CAP ) ) ) * 100 );
		return $ret;
	}

	/**
	 * Gets the public key from the settings.
	 *
	 * @return string
	 */
	public function get_public_key() {
		$mode =  esc_attr( get_option( 'mode' ) );
		if ( 'test' === $mode ) {
			$key = esc_attr( get_option( 'tpk', '' ) );
		} else {
			$key = esc_attr( get_option( 'lpk', '' ) );
		}
		return $key;
	}

	/**
	 * Fetch an array of the payments by the form ID.
	 *
	 * @param integer $form_id
	 * @param array $args
	 * @return array
	 */
	public function get_payments_by_id( $form_id = 0, $args = array() ) {
        global $wpdb;
		$results = array();
		if ( 0 === $form_id ) {
			return $results;
		}

		$defaults = array(
			'paid'     => '1', 
			'order'    => 'desc',
			'orderby'  => 'created_at',
		);
		$args  = wp_parse_args( $args, $defaults );
        $table = esc_sql( $wpdb->prefix . PFF_PAYSTACK_TABLE );
		$order = strtoupper( $args['order'] );

		$current_version = get_bloginfo('version');
		if ( version_compare( '6.2', $current_version, '<=' ) ) {

			// phpcs:disable WordPress.DB -- Start ignoring
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * 
					FROM %i 
					WHERE post_id = %d 
					AND paid = %s
					ORDER BY %i $order",
					$table,
					$form_id,
					$args['paid'],
					$args['orderby'],
				)
			);
			// phpcs:enable -- Stop ignoring

		} else {

			// phpcs:disable WordPress.DB -- Start ignoring
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * 
					FROM `%s` 
					WHERE post_id = '%d'
					AND paid = '%s'
					ORDER BY '%s' $order",
					$table,
					$form_id,
					$args['paid'],
					$args['orderby'],
				)
			);
			// phpcs:enable -- Stop ignoring
		}

		return $results;
	}

	/**
	 * Gets the payments count for the current form.
	 *
	 * @param int|string $form_id
	 * @return int
	 */
	public function get_payments_count( $form_id ) {
		global $wpdb;
		$table = $wpdb->prefix . PFF_PAYSTACK_TABLE;
		$num   = wp_cache_get( 'form_payments_' . $form_id, 'pff_paystack' );
		if ( false === $num ) {

			$current_version = get_bloginfo('version');
			if ( version_compare( '6.2', $current_version, '<=' ) ) {
	
				// phpcs:disable WordPress.DB -- Start ignoring
				$num = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*)
						FROM %i
						WHERE post_id = %d
						AND paid = '1'",
						$table,
						$form_id
					)
				);
				// phpcs:enable -- Stop ignoring
			} else {
				// phpcs:disable WordPress.DB -- Start ignoring
				$num = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*)
						FROM `%s`
						WHERE post_id = '%d'
						AND paid = '1'",
						$table,
						$form_id
					)
				);
				// phpcs:enable -- Stop ignoring
			}

			wp_cache_set( 'form_payments_' . $form_id, $num, 'pff_paystack', 60*5 );
		}
		return $num;
	}

	/**
	 * Returns an array | string of the countries
	 *
	 * @param boolean $implode
	 * @return array|string
	 */
	public function get_countries( $implode = false ) {
		$countries = [
			esc_html__( "Afghanistan", 'pff-paystack' ),
			esc_html__( "Albania", 'pff-paystack' ),
			esc_html__( "Algeria", 'pff-paystack' ),
			esc_html__( "American Samoa", 'pff-paystack' ),
			esc_html__( "Andorra", 'pff-paystack' ),
			esc_html__( "Angola", 'pff-paystack' ),
			esc_html__( "Anguilla", 'pff-paystack' ),
			esc_html__( "Antarctica", 'pff-paystack' ),
			esc_html__( "Antigua and Barbuda", 'pff-paystack' ),
			esc_html__( "Argentina", 'pff-paystack' ),
			esc_html__( "Armenia", 'pff-paystack' ),
			esc_html__( "Aruba", 'pff-paystack' ),
			esc_html__( "Australia", 'pff-paystack' ),
			esc_html__( "Austria", 'pff-paystack' ),
			esc_html__( "Azerbaijan", 'pff-paystack' ),
			esc_html__( "Bahamas", 'pff-paystack' ),
			esc_html__( "Bahrain", 'pff-paystack' ),
			esc_html__( "Bangladesh", 'pff-paystack' ),
			esc_html__( "Barbados", 'pff-paystack' ),
			esc_html__( "Belarus", 'pff-paystack' ),
			esc_html__( "Belgium", 'pff-paystack' ),
			esc_html__( "Belize", 'pff-paystack' ),
			esc_html__( "Benin", 'pff-paystack' ),
			esc_html__( "Bermuda", 'pff-paystack' ),
			esc_html__( "Bhutan", 'pff-paystack' ),
			esc_html__( "Bolivia", 'pff-paystack' ),
			esc_html__( "Bosnia and Herzegovina", 'pff-paystack' ),
			esc_html__( "Botswana", 'pff-paystack' ),
			esc_html__( "Bouvet Island", 'pff-paystack' ),
			esc_html__( "Brazil", 'pff-paystack' ),
			esc_html__( "British Indian Ocean Territory", 'pff-paystack' ),
			esc_html__( "Brunei Darussalam", 'pff-paystack' ),
			esc_html__( "Bulgaria", 'pff-paystack' ),
			esc_html__( "Burkina Faso", 'pff-paystack' ),
			esc_html__( "Burundi", 'pff-paystack' ),
			esc_html__( "Cambodia", 'pff-paystack' ),
			esc_html__( "Cameroon", 'pff-paystack' ),
			esc_html__( "Canada", 'pff-paystack' ),
			esc_html__( "Cape Verde", 'pff-paystack' ),
			esc_html__( "Cayman Islands", 'pff-paystack' ),
			esc_html__( "Central African Republic", 'pff-paystack' ),
			esc_html__( "Chad", 'pff-paystack' ),
			esc_html__( "Chile", 'pff-paystack' ),
			esc_html__( "China", 'pff-paystack' ),
			esc_html__( "Christmas Island", 'pff-paystack' ),
			esc_html__( "Cocos (Keeling) Islands", 'pff-paystack' ),
			esc_html__( "Colombia", 'pff-paystack' ),
			esc_html__( "Comoros", 'pff-paystack' ),
			esc_html__( "Congo", 'pff-paystack' ),
			esc_html__( "Congo, The Democratic Republic of The", 'pff-paystack' ),
			esc_html__( "Cook Islands", 'pff-paystack' ),
			esc_html__( "Costa Rica", 'pff-paystack' ),
			esc_html__( "Cote D'ivoire", 'pff-paystack' ),
			esc_html__( "Croatia", 'pff-paystack' ),
			esc_html__( "Cuba", 'pff-paystack' ),
			esc_html__( "Cyprus", 'pff-paystack' ),
			esc_html__( "Czech Republic", 'pff-paystack' ),
			esc_html__( "Denmark", 'pff-paystack' ),
			esc_html__( "Djibouti", 'pff-paystack' ),
			esc_html__( "Dominica", 'pff-paystack' ),
			esc_html__( "Dominican Republic", 'pff-paystack' ),
			esc_html__( "Ecuador", 'pff-paystack' ),
			esc_html__( "Egypt", 'pff-paystack' ),
			esc_html__( "El Salvador", 'pff-paystack' ),
			esc_html__( "Equatorial Guinea", 'pff-paystack' ),
			esc_html__( "Eritrea", 'pff-paystack' ),
			esc_html__( "Estonia", 'pff-paystack' ),
			esc_html__( "Ethiopia", 'pff-paystack' ),
			esc_html__( "Falkland Islands (Malvinas)", 'pff-paystack' ),
			esc_html__( "Faroe Islands", 'pff-paystack' ),
			esc_html__( "Fiji", 'pff-paystack' ),
			esc_html__( "Finland", 'pff-paystack' ),
			esc_html__( "France", 'pff-paystack' ),
			esc_html__( "French Guiana", 'pff-paystack' ),
			esc_html__( "French Polynesia", 'pff-paystack' ),
			esc_html__( "French Southern Territories", 'pff-paystack' ),
			esc_html__( "Gabon", 'pff-paystack' ),
			esc_html__( "Gambia", 'pff-paystack' ),
			esc_html__( "Georgia", 'pff-paystack' ),
			esc_html__( "Germany", 'pff-paystack' ),
			esc_html__( "Ghana", 'pff-paystack' ),
			esc_html__( "Gibraltar", 'pff-paystack' ),
			esc_html__( "Greece", 'pff-paystack' ),
			esc_html__( "Greenland", 'pff-paystack' ),
			esc_html__( "Grenada", 'pff-paystack' ),
			esc_html__( "Guadeloupe", 'pff-paystack' ),
			esc_html__( "Guam", 'pff-paystack' ),
			esc_html__( "Guatemala", 'pff-paystack' ),
			esc_html__( "Guinea", 'pff-paystack' ),
			esc_html__( "Guinea-bissau", 'pff-paystack' ),
			esc_html__( "Guyana", 'pff-paystack' ),
			esc_html__( "Haiti", 'pff-paystack' ),
			esc_html__( "Heard Island and Mcdonald Islands", 'pff-paystack' ),
			esc_html__( "Holy See (Vatican City State)", 'pff-paystack' ),
			esc_html__( "Honduras", 'pff-paystack' ),
			esc_html__( "Hong Kong", 'pff-paystack' ),
			esc_html__( "Hungary", 'pff-paystack' ),
			esc_html__( "Iceland", 'pff-paystack' ),
			esc_html__( "India", 'pff-paystack' ),
			esc_html__( "Indonesia", 'pff-paystack' ),
			esc_html__( "Iran, Islamic Republic of", 'pff-paystack' ),
			esc_html__( "Iraq", 'pff-paystack' ),
			esc_html__( "Ireland", 'pff-paystack' ),
			esc_html__( "Israel", 'pff-paystack' ),
			esc_html__( "Italy", 'pff-paystack' ),
			esc_html__( "Jamaica", 'pff-paystack' ),
			esc_html__( "Japan", 'pff-paystack' ),
			esc_html__( "Jordan", 'pff-paystack' ),
			esc_html__( "Kazakhstan", 'pff-paystack' ),
			esc_html__( "Kenya", 'pff-paystack' ),
			esc_html__( "Kiribati", 'pff-paystack' ),
			esc_html__( "Korea, Democratic People's Republic of", 'pff-paystack' ),
			esc_html__( "Korea, Republic of", 'pff-paystack' ),
			esc_html__( "Kuwait", 'pff-paystack' ),
			esc_html__( "Kyrgyzstan", 'pff-paystack' ),
			esc_html__( "Lao People's Democratic Republic", 'pff-paystack' ),
			esc_html__( "Latvia", 'pff-paystack' ),
			esc_html__( "Lebanon", 'pff-paystack' ),
			esc_html__( "Lesotho", 'pff-paystack' ),
			esc_html__( "Liberia", 'pff-paystack' ),
			esc_html__( "Libyan Arab Jamahiriya", 'pff-paystack' ),
			esc_html__( "Liechtenstein", 'pff-paystack' ),
			esc_html__( "Lithuania", 'pff-paystack' ),
			esc_html__( "Luxembourg", 'pff-paystack' ),
			esc_html__( "Macao", 'pff-paystack' ),
			esc_html__( "Macedonia, The Former Yugoslav Republic of", 'pff-paystack' ),
			esc_html__( "Madagascar", 'pff-paystack' ),
			esc_html__( "Malawi", 'pff-paystack' ),
			esc_html__( "Malaysia", 'pff-paystack' ),
			esc_html__( "Maldives", 'pff-paystack' ),
			esc_html__( "Mali", 'pff-paystack' ),
			esc_html__( "Malta", 'pff-paystack' ),
			esc_html__( "Marshall Islands", 'pff-paystack' ),
			esc_html__( "Martinique", 'pff-paystack' ),
			esc_html__( "Mauritania", 'pff-paystack' ),
			esc_html__( "Mauritius", 'pff-paystack' ),
			esc_html__( "Mayotte", 'pff-paystack' ),
			esc_html__( "Mexico", 'pff-paystack' ),
			esc_html__( "Micronesia, Federated States of", 'pff-paystack' ),
			esc_html__( "Moldova, Republic of", 'pff-paystack' ),
			esc_html__( "Monaco", 'pff-paystack' ),
			esc_html__( "Mongolia", 'pff-paystack' ),
			esc_html__( "Montserrat", 'pff-paystack' ),
			esc_html__( "Morocco", 'pff-paystack' ),
			esc_html__( "Mozambique", 'pff-paystack' ),
			esc_html__( "Myanmar", 'pff-paystack' ),
			esc_html__( "Namibia", 'pff-paystack' ),
			esc_html__( "Nauru", 'pff-paystack' ),
			esc_html__( "Nepal", 'pff-paystack' ),
			esc_html__( "Netherlands", 'pff-paystack' ),
			esc_html__( "Netherlands Antilles", 'pff-paystack' ),
			esc_html__( "New Caledonia", 'pff-paystack' ),
			esc_html__( "New Zealand", 'pff-paystack' ),
			esc_html__( "Nicaragua", 'pff-paystack' ),
			esc_html__( "Niger", 'pff-paystack' ),
			esc_html__( "Nigeria", 'pff-paystack' ),
			esc_html__( "Niue", 'pff-paystack' ),
			esc_html__( "Norfolk Island", 'pff-paystack' ),
			esc_html__( "Northern Mariana Islands", 'pff-paystack' ),
			esc_html__( "Norway", 'pff-paystack' ),
			esc_html__( "Oman", 'pff-paystack' ),
			esc_html__( "Pakistan", 'pff-paystack' ),
			esc_html__( "Palau", 'pff-paystack' ),
			esc_html__( "Palestinian Territory, Occupied", 'pff-paystack' ),
			esc_html__( "Panama", 'pff-paystack' ),
			esc_html__( "Papua New Guinea", 'pff-paystack' ),
			esc_html__( "Paraguay", 'pff-paystack' ),
			esc_html__( "Peru", 'pff-paystack' ),
			esc_html__( "Philippines", 'pff-paystack' ),
			esc_html__( "Pitcairn", 'pff-paystack' ),
			esc_html__( "Poland", 'pff-paystack' ),
			esc_html__( "Portugal", 'pff-paystack' ),
			esc_html__( "Puerto Rico", 'pff-paystack' ),
			esc_html__( "Qatar", 'pff-paystack' ),
			esc_html__( "Reunion", 'pff-paystack' ),
			esc_html__( "Romania", 'pff-paystack' ),
			esc_html__( "Russian Federation", 'pff-paystack' ),
			esc_html__( "Rwanda", 'pff-paystack' ),
			esc_html__( "Saint Helena", 'pff-paystack' ),
			esc_html__( "Saint Kitts and Nevis", 'pff-paystack' ),
			esc_html__( "Saint Lucia", 'pff-paystack' ),
			esc_html__( "Saint Pierre and Miquelon", 'pff-paystack' ),
			esc_html__( "Saint Vincent and The Grenadines", 'pff-paystack' ),
			esc_html__( "Samoa", 'pff-paystack' ),
			esc_html__( "San Marino", 'pff-paystack' ),
			esc_html__( "Sao Tome and Principe", 'pff-paystack' ),
			esc_html__( "Saudi Arabia", 'pff-paystack' ),
			esc_html__( "Senegal", 'pff-paystack' ),
			esc_html__( "Serbia and Montenegro", 'pff-paystack' ),
			esc_html__( "Seychelles", 'pff-paystack' ),
			esc_html__( "Sierra Leone", 'pff-paystack' ),
			esc_html__( "Singapore", 'pff-paystack' ),
			esc_html__( "Slovakia", 'pff-paystack' ),
			esc_html__( "Slovenia", 'pff-paystack' ),
			esc_html__( "Solomon Islands", 'pff-paystack' ),
			esc_html__( "Somalia", 'pff-paystack' ),
			esc_html__( "South Africa", 'pff-paystack' ),
			esc_html__( "South Georgia and The South Sandwich Islands", 'pff-paystack' ),
			esc_html__( "Spain", 'pff-paystack' ),
			esc_html__( "Sri Lanka", 'pff-paystack' ),
			esc_html__( "Sudan", 'pff-paystack' ),
			esc_html__( "Suriname", 'pff-paystack' ),
			esc_html__( "Svalbard and Jan Mayen", 'pff-paystack' ),
			esc_html__( "Swaziland", 'pff-paystack' ),
			esc_html__( "Sweden", 'pff-paystack' ),
			esc_html__( "Switzerland", 'pff-paystack' ),
			esc_html__( "Syrian Arab Republic", 'pff-paystack' ),
			esc_html__( "Taiwan, Province of China", 'pff-paystack' ),
			esc_html__( "Tajikistan", 'pff-paystack' ),
			esc_html__( "Tanzania, United Republic of", 'pff-paystack' ),
			esc_html__( "Thailand", 'pff-paystack' ),
			esc_html__( "Timor-leste", 'pff-paystack' ),
			esc_html__( "Togo", 'pff-paystack' ),
			esc_html__( "Tokelau", 'pff-paystack' ),
			esc_html__( "Tonga", 'pff-paystack' ),
			esc_html__( "Trinidad and Tobago", 'pff-paystack' ),
			esc_html__( "Tunisia", 'pff-paystack' ),
			esc_html__( "Turkey", 'pff-paystack' ),
			esc_html__( "Turkmenistan", 'pff-paystack' ),
			esc_html__( "Turks and Caicos Islands", 'pff-paystack' ),
			esc_html__( "Tuvalu", 'pff-paystack' ),
			esc_html__( "Uganda", 'pff-paystack' ),
			esc_html__( "Ukraine", 'pff-paystack' ),
			esc_html__( "United Arab Emirates", 'pff-paystack' ),
			esc_html__( "United Kingdom", 'pff-paystack' ),
			esc_html__( "United States", 'pff-paystack' ),
			esc_html__( "United States Minor Outlying Islands", 'pff-paystack' ),
			esc_html__( "Uruguay", 'pff-paystack' ),
			esc_html__( "Uzbekistan", 'pff-paystack' ),
			esc_html__( "Vanuatu", 'pff-paystack' ),
			esc_html__( "Venezuela", 'pff-paystack' ),
			esc_html__( "Viet Nam", 'pff-paystack' ),
			esc_html__( "Virgin Islands; British", 'pff-paystack' ),
			esc_html__( "Virgin Islands; U.S.", 'pff-paystack' ),
			esc_html__( "Wallis and Futuna", 'pff-paystack' ),
			esc_html__( "Western Sahara", 'pff-paystack' ),
			esc_html__( "Yemen", 'pff-paystack' ),
			esc_html__( "Zambia", 'pff-paystack' ),
			esc_html__( "Zimbabwe", 'pff-paystack' ),
		];	
		if ( $implode ) {
			$countries = implode( ',', $countries );
		}
		return $countries;
	}

	/**
	 * Returns the states available.
	 *
	 * @param boolean $implode
	 * @return array|string
	 */
	public function get_states( $implode = false ) {
		$states = [
			esc_html__( 'Abia', 'pff-paystack' ),
			esc_html__( 'Adamawa', 'pff-paystack' ),
			esc_html__( 'Akwa Ibom', 'pff-paystack' ),
			esc_html__( 'Anambra', 'pff-paystack' ),
			esc_html__( 'Bauchi', 'pff-paystack' ),
			esc_html__( 'Bayelsa', 'pff-paystack' ),
			esc_html__( 'Benue', 'pff-paystack' ),
			esc_html__( 'Borno', 'pff-paystack' ),
			esc_html__( 'Cross River', 'pff-paystack' ),
			esc_html__( 'Delta', 'pff-paystack' ),
			esc_html__( 'Ebonyi', 'pff-paystack' ),
			esc_html__( 'Edo', 'pff-paystack' ),
			esc_html__( 'Ekiti', 'pff-paystack' ),
			esc_html__( 'Enugu', 'pff-paystack' ),
			esc_html__( 'FCT', 'pff-paystack' ),
			esc_html__( 'Gombe', 'pff-paystack' ),
			esc_html__( 'Imo', 'pff-paystack' ),
			esc_html__( 'Jigawa', 'pff-paystack' ),
			esc_html__( 'Kaduna', 'pff-paystack' ),
			esc_html__( 'Kano', 'pff-paystack' ),
			esc_html__( 'Katsina', 'pff-paystack' ),
			esc_html__( 'Kebbi', 'pff-paystack' ),
			esc_html__( 'Kogi', 'pff-paystack' ),
			esc_html__( 'Kwara', 'pff-paystack' ),
			esc_html__( 'Lagos', 'pff-paystack' ),
			esc_html__( 'Nasarawa', 'pff-paystack' ),
			esc_html__( 'Niger', 'pff-paystack' ),
			esc_html__( 'Ogun', 'pff-paystack' ),
			esc_html__( 'Ondo', 'pff-paystack' ),
			esc_html__( 'Osun', 'pff-paystack' ),
			esc_html__( 'Oyo', 'pff-paystack' ),
			esc_html__( 'Plateau', 'pff-paystack' ),
			esc_html__( 'Rivers', 'pff-paystack' ),
			esc_html__( 'Sokoto', 'pff-paystack' ),
			esc_html__( 'Taraba', 'pff-paystack' ),
			esc_html__( 'Yobe', 'pff-paystack' ),
			esc_html__( 'Zamfara', 'pff-paystack' ),
		];
		if ( $implode ) {
			$states = implode( ',', $states );
		}
		return $states;
	}

	/**
	 * Returns the meta fields and their default values.
	 *
	 * @return array
	 */
	public function get_meta_defaults() {
		return $this->defaults;
	}

	/**
	 * Returns the allowed HTML for wp_kses()
	 *
	 * @return array
	 */
	public function get_allowed_html() {
		return $this->allowed_html;
	}

	/**
	 * Retrieve the user's IP address.
	 *
	 * @return string User's IP address.
	 */
	public function get_the_user_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}
	
	/**
	 * Get the DB records by the transaction code supplied.
	 *
	 * @param string $code
	 * @return object
	 */
	public function get_db_record( $code, $column = 'txn_code' ) {
		global $wpdb;
		$return = false;
		$table  = esc_sql( $wpdb->prefix . PFF_PAYSTACK_TABLE );

		$current_version = get_bloginfo('version');
		if ( version_compare( '6.2', $current_version, '<=' ) ) {
			// phpcs:disable WordPress.DB -- Start ignoring
			$record = $wpdb->get_results(
				$wpdb->prepare(
						"SELECT * 
						FROM %i 
						WHERE %i = %s"
					,
					$table,
					$column,
					$code
				), 'OBJECT' );
			// phpcs:enable -- Stop ignoring
		} else {
			// phpcs:disable WordPress.DB -- Start ignoring
			$record = $wpdb->get_results(
				$wpdb->prepare(
						"SELECT * 
						FROM `%s`
						WHERE '%s' = '%s'"
					,
					$table,
					$column,
					$code
				), 'OBJECT' );
			// phpcs:enable -- Stop ignoring
		}

		if ( ! empty( $record ) && isset( $record[0] ) ) {
			$return = $record[0];
		}
		return $return;
	}

	// FUNCTIONS

	/**
	 * Gets the current forms meta fields values and set the defaults if needed.
	 *
	 * @param WP_Post $post
	 * @return array
	 */
	public function parse_meta_values( $post ) {
		$new_values = [];
		foreach ( $this->defaults as $key => $default ) {
			$value = get_post_meta( $post->ID, '_' . $key, true );
			if ( false !== $value && ! empty( $value ) ) {
				$new_values[ $key ] = $value;
			}
		}

		$meta = wp_parse_args( $new_values, $this->defaults );
		if ( '' === $meta['inventory'] || '0' === $meta['inventory'] ) {
			if ( '' !== $meta['sold'] ) {
				$meta['inventory'] = $meta['sold'];
			} else {
				$meta['inventory'] = '1';
			}
		}

		// Strip any text from the variable amount field.
		if ( isset( $meta['usevariableamount'] ) && is_string( $meta['usevariableamount'] ) ) {
			$meta['usevariableamount'] = (int) $meta['usevariableamount'];
		}

		$meta['minimum']   = (int) $meta['minimum'];
		//$meta['txncharge'] = floatval( $meta['txncharge'] );
		return $meta;
	}

	/**
	 * Take an array of the submitted form values and formats it for a paystack request.
	 *
	 * @param array $metadata
	 * @return void
	 */
	public function format_meta_as_custom_fields( $metadata ) {
		$fields = array();

		foreach ( $metadata as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			switch ( $key ) {
				case 'pf-fname':
					$fields[] = array(
						'display_name'  => esc_html__( 'Full Name', 'pff-paystack' ),
						'variable_name' => 'Full_Name',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				case 'pf-plancode':
					$fields[] = array(
						'display_name'  => esc_html__( 'Plan', 'pff-paystack' ),
						'variable_name' => 'Plan',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				case 'pf-vname':
					$fields[] = array(
						'display_name'  => esc_html__( 'Payment Option', 'pff-paystack' ),
						'variable_name' => 'Payment Option',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				case 'pf-interval':
					$fields[] = array(
						'display_name'  => esc_html__( 'Plan Interval', 'pff-paystack' ),
						'variable_name' => 'Plan Interval',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				case 'pf-quantity':
					$fields[] = array(
						'display_name'  => esc_html__( 'Quantity', 'pff-paystack' ),
						'variable_name' => 'Quantity',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				default:
					$display_name = ucwords( str_replace( array( '_', '-', 'pf' ), ' ', $key ) );
					$fields[] = array(
						'display_name'  => $display_name,
						'variable_name' => $key,
						'type'          => 'text',
						'value'         => (string) $value,
					);
					break;
			}
		}
		return $fields;
	}

	/**
	 * Formats the metadata for output on the retry form page.
	 *
	 * @param string $data
	 * @return string
	 */
	public function format_meta_as_display_fields( $data ) {
		$new  = json_decode( $data );
		$text = '';
		
		if ( is_array( $new ) && array_key_exists( 0, $new ) ) {
			foreach ( $new as $item ) {
				if ( 'text' === $item->type ) {
					$text .= sprintf(
						'<div class="span12 unit">
							<label class="label inline">%s:</label>
							<strong>%s</strong>
						</div>',
						esc_html( $item->display_name ),
						esc_html( $item->value )
					);
				} else {
					$text .= sprintf(
						'<div class="span12 unit">
							<label class="label inline">%s:</label>
							<strong><a target="_blank" href="%s">%s</a></strong>
						</div>',
						esc_html( $item->display_name ),
						esc_url( $item->value ),
						esc_html__( 'link', 'pff-paystack' )
					);
				}
			}
		} elseif ( is_object( $new ) ) {
			if ( count( get_object_vars( $new ) ) > 0 ) {
				foreach ( $new as $key => $item ) {
					$text .= sprintf(
						'<div class="span12 unit">
							<label class="label inline">%s:</label>
							<strong>%s</strong>
						</div>',
						esc_html( $key ),
						esc_html( $item )
					);
				}
			}
		}
		return $text;
	}

	/**
	 * Generate a new Paystack code.
	 *
	 * @param int $length Length of the code to generate. Default 10.
	 * @return string Generated code.
	 */
	public function generate_new_code( $length = 10 ) {
		$characters        = '06EFGHI9KL' . time() . 'MNOPJRSUVW01YZ923234' . time() . 'ABCD5678QXT';
		$characters_length = strlen( $characters );
		$random_string     = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$random_string .= $characters[ wp_rand( 0, $characters_length - 1 ) ];
		}

		return time() . '_' . $random_string;
	}

	/**
	 * Check if the given code exists in the database.
	 *
	 * @param string $code The code to check.
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return bool True if the code exists, false otherwise.
	 */
	public function check_code( $code ) {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . PFF_PAYSTACK_TABLE );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$current_version = get_bloginfo('version');
		if ( version_compare( '6.2', $current_version, '<=' ) ) {
			// phpcs:disable WordPress.DB -- Start ignoring
			$o_exist = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM %i WHERE txn_code = %s",
					$table,
					$code
				)
			);
			// phpcs:enable -- Stop ignoring
		} else {
			// phpcs:disable WordPress.DB -- Start ignoring
			$o_exist = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `%s` WHERE txn_code = %s",
					$table,
					$code
				)
			);
			// phpcs:enable -- Stop ignoring
		}

		return ( count( $o_exist ) > 0 );
	}


	/**
	 * Takes the amount and processes the "transactional" fees.
	 *
	 * @param integer $amount
	 * @return integer
	 */
	public function process_transaction_fees( $amount ) {
		$fees = $this->get_fees();
		$pc   = new Transaction_Fee(
			$fees['prc'],
			$fees['adc'],
			$fees['ths'],
			$fees['cap']
		);
		return $pc->add_for_ngn( $amount );
	}
}