<?php
/**
 * The functions to handle the form submission, this will trigger the payment requests to paystack.
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Form Submit Class
 */
class Retry_Submit {

	/**
	 * The helpers class.
	 *
	 * @var \paystack\payment_forms\Helpers
	 */
	public $helpers;

	/**
	 * Holds the current form meta
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Holds the current retry meta from the DB
	 *
	 * @var object
	 */
	protected $retry_meta;

	/**
	 * Holds the current form id
	 *
	 * @var array
	 */
	protected $form_id = 0;

	/**
	 * Holds the current transaction code.
	 *
	 * @var string
	 */
	public $code = '';

	/**
	 * Holds the new code to use.
	 *
	 * @var string
	 */
	public $new_code = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_pff_paystack_retry_action', [ $this, 'retry_action' ] );
		add_action( 'wp_ajax_nopriv_pff_paystack_retry_action', [ $this, 'retry_action' ] );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	protected function setup_data() {
		$this->helpers   = new Helpers();
		$this->new_code  = $this->generate_code() . '_2';
		$retry_record    = $this->helpers->get_db_record( $this->code );
		if ( false !== $retry_record ) {
			$this->retry_meta = $retry_record;
			$this->form_id    = $this->retry_meta->post_id;
			$this->meta       = $this->helpers->parse_meta_values( get_post( $this->form_id ) );
		}
	}

	/**
	 * The action for the retry form.
	 *
	 * @return void
	 */
	public function retry_action() {
		if ( ! isset( $_POST['pf-nonce'] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pf-nonce'] ) ), 'pff-paystack-retry' ) ) {
			$response = array(
				'result'  => 'failed',
				'message' => esc_html__( 'Nonce verification is required.', 'pff-paystack' ),
			);
			// Exit here, for not processing further because of the error.
			exit( wp_json_encode( $response ) );	
		}

		// False positive, we are using isset() to verify it exists before sanitization.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( isset( $_POST['code'] ) && '' !== trim( wp_unslash( $_POST['code'] ) ) ) {
			$this->code = sanitize_text_field( wp_unslash( $_POST['code'] ) );
		} else {
			$response = array(
				'result'  => 'failed',
				'message' => esc_html__( 'Code is required', 'pff-paystack' ),
			);
			// Exit here, for not processing further because of the error.
			exit( wp_json_encode( $response ) );
		}
		do_action( 'kkd_pff_paystack_before_save' );

		/**
		 * Setup our data to be processed.
		 */
		$this->setup_data();

		if ( 0 !== $this->form_id ) {
			$subaccount          = $this->meta['subaccount'];
			$txnbearer           = $this->meta['txnbearer'];
			$transaction_charge  = (int) $this->meta['merchantamount'];
			$transaction_charge *= 100;
			$fixedmetadata       = json_decode( $this->retry_meta->metadata );
			$quantity            = 1;
			foreach ( $fixedmetadata as $nkey => $nvalue ) {
				if ( 'Quantity' === $nvalue->variable_name ) {
					$quantity = $nvalue->value;
				}
				if ( 'Full_Name' === $nvalue->variable_name ) {
					$fullname = $nvalue->value;
				}
			}
		}

		if ( empty( $this->meta['subaccount'] ) ) {
			$subaccount         = null;
			$txnbearer          = null;
			$transaction_charge = null;
		}

		if ( empty( $transaction_charge ) || 0 === $transaction_charge ) {
			$transaction_charge = null;
		}

		$this->update_retry_code();

		$response = array(
			'result'             => 'success',
			'code'               => $this->new_code,
			'plan'               => $this->retry_meta->plan,
			'quantity'           => $quantity,
			'email'              => $this->retry_meta->email,
			'name'               => $fullname,
			'total'              => $this->retry_meta->amount * 100,
			'custom_fields'      => $fixedmetadata,
			'currency'           => $this->meta['currency'],
			'subaccount'         => $subaccount,
			'txnbearer'          => $txnbearer,
			'transaction_charge' => $transaction_charge,
		);

		// We create 2 nonces here
		// 1 incase the payment fails, and the user needs to try again.
		// 2 if the payment is successful and the confirmation ajax needs to run. 
		$response['retryNonce'] = wp_create_nonce( 'pff-paystack-retry' );
		$response['confirmNonce'] = wp_create_nonce( 'pff-paystack-confirm' );

		echo wp_json_encode( $response );

		die();
	}

	/**
	 * Generate a unique Paystack code that does not yet exist in the database.
	 *
	 * @return string Generated unique code.
	 */
	public function generate_code() {
		do {
			$code = $this->helpers->generate_new_code();
			$check = $this->helpers->check_code( $code );
		} while ( $check );

		return $code;
	}

	/**
	 * Updates the DB row with the new transaction code.
	 *
	 * @return void
	 */
	protected function update_retry_code() {
		global $wpdb;
		$return = false;
		$table  = esc_sql( $wpdb->prefix . PFF_PAYSTACK_TABLE );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$current_version = get_bloginfo('version');
		if ( version_compare( '6.2', $current_version, '<=' ) ) {
			// phpcs:disable WordPress.DB -- Start ignoring
			$return = $wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET txn_code_2 = %s WHERE txn_code = %s",
					$table,
					$this->new_code,
					$this->code
				)
			);
			// phpcs:enable -- Stop ignoring
		} else {
			// phpcs:disable WordPress.DB -- Start ignoring
			$return = $wpdb->query(
				$wpdb->prepare(
					"UPDATE `%s` SET txn_code_2 = '%s' WHERE txn_code = '%s'",
					$table,
					$this->new_code,
					$this->code
				)
			);
			// phpcs:enable -- Stop ignoring
		}


		return $return;
	}
}