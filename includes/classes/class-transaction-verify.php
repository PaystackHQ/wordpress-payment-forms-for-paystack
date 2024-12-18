<?php
/**
 * A request to verify the current transaction code.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transaction_Verify
 */
class Transaction_Verify extends API {

	/**
	 * Construct the class.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_module( 'transaction/verify' );
	}

	/**
	 * Send a request to Paystack and get the Plan Object.
	 *
	 * @return boolean|object
	 */
	public function verify_transaction( $code = '' ) {
		$return = false;
		if ( '' === $code || ! $this->api_ready() ) {
			return false;
		}

		$this->set_url_args( $code );
		$response = $this->get_request();
		$return   = $this->verify_response( $response );
		return $return;
	}

	/**
	 * Reviews the transaction and returns success or an error and a message.
	 *
	 * @param object $response
	 * @return boolean
	 */
	public function verify_response( $response ) {
		$return = $response;
		if ( false === $response ) {
			$return = [
				'message' => esc_html__( 'Payment Verification Failed', 'pff-paystack' ),
				'result'  => 'failed',
			];
		} else {
			if ( 'success' === $response->data->status ) {
				$return = [
					'message' => esc_html__( 'Payment Verification Passed', 'pff-paystack' ),
					'result'  => 'success',
					'data'    => wp_json_encode( $response->data ),
				];
			} else {
				$return = [
					'message' => esc_html__( 'Transaction Failed/Invalid Code', 'pff-paystack' ),
					'result'  => 'failed',
				];
			}
		}
		return $return;
	}
}