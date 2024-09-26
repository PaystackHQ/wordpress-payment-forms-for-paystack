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
class Form_Submit {

	/**
	 * The helpers class.
	 *
	 * @var object
	 */
	public $helpers;

	/**
	 * Holds an array of the current response, can hold the error.
	 * $response['result']
	 * $response['message']
	 * @var array
	 */
	protected $response = array();

	/**
	 * Holds the current form meta
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Holds the current form id
	 *
	 * @var array
	 */
	protected $form_id = 0;
	
	/**
	 * Holds the $_POST form information.
	 *
	 * @var array
	 */
	protected $form_data = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		//add_action( 'wp_ajax_pff_paystack_submit_action', [ $this, 'submit_action' ] );
		//add_action( 'wp_ajax_nopriv_pff_paystack_submit_action', [ $this, 'submit_action' ] );
	}

	/**
	 * Runs validation checks on the data to see if this is a valid submission from our form.
	 *
	 * @return boolean|array
	 */
	protected function valid_submission() {
		
		/**
		 * TODO - Needs better security checks - NONCE
		 */
		if ( ! isset( $_POST['pf-id'] ) || '' == trim( sanitize_text_field( $_POST['pf-id'] ) ) ) {
			$this->response['result']  = 'failed';
			$this->response['message'] = 'A form ID is required';
			return false;
		} else {
			$this->form_id = sanitize_text_field( $_POST['pf-id'] );
		}

		if ( '' == trim( sanitize_text_field( $_POST['pf-pemail'] ) ) ) {
			$this->response['result']  = 'failed';
			$this->response['message'] = 'Email is required';
			return false;
		}
		return true;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	protected function setup_data() {
		$this->meta      = $this->helpers->parse_meta_values( get_post( $this->form_id ) );
		$this->form_data = filter_input_array( INPUT_POST );

		$this->metadata = $this->form_data;
		unset(
			$this->metadata['action'],
			$this->metadata['pf-recur'],
			$this->metadata['pf-id'],
			$this->metadata['pf-pemail'],
			$this->metadata['pf-amount'],
			$this->metadata['pf-user_id'],
			$this->metadata['pf-interval']
		);
		$this->untouched = $this->helpers->format_meta_as_custom_fields( $this->metadata );
	}

	public function submit_action() {
		/**
		 * TODO - Needs better security checks - NONCE
		 */
		if ( ! $this->valid_submission() ) {
			// Exit here, for not processing further because of the error
			exit( json_encode( $this->response ) );
		}

		/**
		 * Setup our data to be processed.
		 */
		$this->helpers = new Helpers();
		$this->setup_data();
	
		/**
		 * Hookable location. Allows other plugins use a fresh submission before it is saved to the database.
		 * add_action( 'pff_paystack_before_save', 'function_to_use_posted_values' );
		 * 
		 */
		do_action( 'pff_paystack_before_save', $this );
		
		/**
		 * @deprecated 3.4.2
		 */
		do_action( 'kkd_pff_paystack_before_save' );

		global $wpdb;
		//$code            = kkd_pff_paystack_generate_code();
		$table           = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
		
		$fixed_metadata     = [];
	
		// These are the values from the SAVED Form.
		$file_limit         = get_post_meta( $_POST['pf-id'], '_filelimit', true );
		$currency           = get_post_meta( $_POST['pf-id'], '_currency', true );
		$form_amount        = get_post_meta( $_POST['pf-id'], '_amount', true ); // From form
		$recur              = get_post_meta( $_POST['pf-id'], '_recur', true );
		$subaccount         = get_post_meta( $_POST['pf-id'], '_subaccount', true );
		$txn_bearer         = get_post_meta( $_POST['pf-id'], '_txnbearer', true );
		$transaction_charge = get_post_meta( $_POST['pf-id'], '_merchantamount', true );
		$transaction_charge = intval( floatval( $transaction_charge ) * 100 );
		$txn_charge       = get_post_meta( $_POST['pf-id'], '_txncharge', true );
		$minimum          = get_post_meta( $_POST['pf-id'], '_minimum', true );
		$variable_amount  = get_post_meta( $_POST['pf-id'], '_variableamount', true );
		$use_variable_amt = get_post_meta( $_POST['pf-id'], '_usevariableamount', true );
		$use_quantity     = get_post_meta( $_POST['pf-id'], '_usequantity', true );

		$amount           = (int) str_replace( ' ', '', $_POST['pf-amount'] );
		$variable_name    = $_POST['pf-vname'];
		$original_amount  = $amount;
		$quantity         = 1;
		
	
		/*if ( ( 'no' === $recur ) && ( floatval( $form_amount ) != 0 ) ) {
			$amount = (int) str_replace( ' ', '', floatval( $form_amount ) );
		}
		if ( $minimum == 1 && floatval( $form_amount ) != 0 ) {
			if ( $original_amount < floatval( $form_amount ) ) {
				$amount = floatval( $form_amount );
			} else {
				$amount = $original_amount;
			}
		}
		if ( $use_variable_amt == 1 ) {
			$payment_options = explode( ',', $variable_amount );
			if ( count( $payment_options ) > 0 ) {
				foreach ( $payment_options as $key => $payment_option ) {
					list( $a, $b ) = explode( ':', $payment_option );
					if ( $variable_name == $a ) {
						$amount = $b;
					}
				}
			}
		}
		$fixed_metadata[] = array(
			'display_name'  => 'Unit Price',
			'variable_name' => 'Unit_Price',
			'type'          => 'text',
			'value'         => $currency . number_format( $amount ),
		);
		if ( $use_quantity === 'yes' && ! ( ( $recur === 'optional' ) || ( $recur === 'plan' ) ) ) {
			$quantity   = $_POST['pf-quantity'];
			$unit_amt   = (int) str_replace( ' ', '', $amount );
			$amount     = $quantity * $unit_amt;
		}
	
		if ( $txn_charge == 'customer' ) {
			$amount = kkd_pff_paystack_add_paystack_charge( $amount );
		}
		$max_file_size = $file_limit * 1024 * 1024;
	
		if ( ! empty( $_FILES ) ) {
			foreach ( $_FILES as $key_name => $value ) {
				if ( $value['size'] > 0 ) {
					if ( $value['size'] > $max_file_size ) {
						$response['result']  = 'failed';
						$response['message'] = 'Max upload size is ' . $file_limit . 'MB';
						exit( json_encode( $response ) );
					} else {
						$attachment_id  = media_handle_upload( $key_name, $_POST['pf-id'] );
						$url            = wp_get_attachment_url( $attachment_id );
						$fixed_metadata[] = array(
							'display_name'  => ucwords( str_replace( '_', ' ', $key_name ) ),
							'variable_name' => $key_name,
							'type'          => 'link',
							'value'         => $url,
						);
					}
				} else {
					$fixed_metadata[] = array(
						'display_name'  => ucwords( str_replace( '_', ' ', $key_name ) ),
						'variable_name' => $key_name,
						'type'          => 'text',
						'value'         => 'No file Uploaded',
					);
				}
			}
		}
		$plan_code = 'none';
		if ( $recur != 'no' ) {
			if ( $recur == 'optional' ) {
				$interval = $_POST['pf-interval'];
				if ( $interval != 'no' ) {
					unset( $metadata['pf-interval'] );
					$mode = esc_attr( get_option( 'mode' ) );
					if ( $mode == 'test' ) {
						$key = esc_attr( get_option( 'tsk' ) );
					} else {
						$key = esc_attr( get_option( 'lsk' ) );
					}
					$kobo_amount = $amount * 100;
	
					$paystack_url = 'https://api.paystack.co/plan';
					$check_url    = 'https://api.paystack.co/plan?amount=' . $kobo_amount . '&interval=' . $interval;
					$headers      = array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $key,
					);
	
					$check_args = array(
						'headers' => $headers,
						'timeout' => 60,
					);
					// Check if plan exists
					$check_request = wp_remote_get( $check_url, $check_args );
					if ( ! is_wp_error( $check_request ) ) {
						$response = json_decode( wp_remote_retrieve_body( $check_request ) );
						if ( $response->meta->total >= 1 ) {
							$plan = $response->data[0];
							$plan_code = $plan->plan_code;
							$fixed_metadata[] = array(
								'display_name'  => 'Plan Interval',
								'variable_name' => 'Plan Interval',
								'type'          => 'text',
								'value'         => $plan->interval,
							);
						} else {
							// Create Plan
							$body = array(
								'name'     => $currency . number_format( $original_amount ) . ' [' . $currency . number_format( $amount ) . '] - ' . $interval,
								'amount'   => $kobo_amount,
								'interval' => $interval,
							);
							$args = array(
								'body'    => json_encode( $body ),
								'headers' => $headers,
								'timeout' => 60,
							);
	
							$request = wp_remote_post( $paystack_url, $args );
							if ( ! is_wp_error( $request ) ) {
								$paystack_response = json_decode( wp_remote_retrieve_body( $request ) );
								$plan_code         = $paystack_response->data->plan_code;
								$fixed_metadata[]  = array(
									'display_name'  => 'Plan Interval',
									'variable_name' => 'Plan Interval',
									'type'          => 'text',
									'value'         => $paystack_response->data->interval,
								);
							}
						}
					}
				}
			} else {
				// Use Plan Code
				$plan_code = $_POST['pf-plancode'];
				unset( $metadata['pf-plancode'] );
			}
		}
	
		if ( $plan_code != 'none' ) {
			$fixed_metadata[] = array(
				'display_name'  => 'Plan',
				'variable_name' => 'Plan',
				'type'          => 'text',
				'value'         => $plan_code,
			);
		}

		$fixed_metadata = json_decode( json_encode( $fixed_metadata, JSON_NUMERIC_CHECK ), true );
		$fixed_metadata = array_merge( $untouched_metadata, $fixed_metadata );

		$insert = array(
			'post_id'  => sanitize_text_field( $_POST['pf-id'] ),
			'email'    => sanitize_email( $_POST['pf-pemail'] ),
			'user_id'  => sanitize_text_field( $_POST['pf-user_id'] ),
			'amount'   => sanitize_text_field( $_POST['amount'] ), // Assuming $amount comes from $_POST
			'plan'     => sanitize_text_field( $_POST['plancode'] ), // Assuming $plancode comes from $_POST
			'ip'       => kkd_pff_paystack_get_the_user_ip(), // Make sure this function returns a sanitized IP
			'txn_code' => sanitize_text_field( $_POST['code'] ), // Assuming $code comes from $_POST
			'metadata' => wp_json_encode( $_POST['fixedmetadata'] ), // Assuming $fixed_metadata comes from $_POST
		);

		$exist = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE post_id = %s AND email = %s AND user_id = %s AND amount = %s AND plan = %s AND ip = %s AND paid = '0' AND metadata = %s",
				$insert['post_id'], $insert['email'], $insert['user_id'], $insert['amount'], $insert['plan'], $insert['ip'], $insert['metadata']
			)
		);

		if ( count( $exist ) > 0 ) {
			$wpdb->update(
				$table,
				array(
					'txn_code' => $code,
					'plan'     => $insert['plan'],
				),
				array(
					'id' => $exist[0]->id,
				)
			);
		} else {
			$wpdb->insert(
				$table,
				$insert
			);

			if ( 'yes' == get_post_meta( $insert['post_id'], '_sendinvoice', true ) ) {
				kkd_pff_paystack_send_invoice( $currency, $insert['amount'], $fullname, $insert['email'], $code );
			}
		}

		if ( '' == $subaccount || ! isset( $subaccount ) ) {
			$subaccount         = null;
			$txn_bearer         = null;
			$transaction_charge = null;
		}
		if ( '' == $transaction_charge || 0 == $transaction_charge || null == $transaction_charge ) {
			$transaction_charge = null;
		}

		$amount = floatval( $insert['amount'] ) * 100;

		$response = array(
			'result'             => 'success',
			'code'               => $insert['txn_code'],
			'plan'               => $insert['plan'],
			'quantity'           => $quantity,
			'email'              => $insert['email'],
			'name'               => $fullname,
			'total'              => round( $amount ),
			'currency'           => $currency,
			'custom_fields'      => $fixed_metadata,
			'subaccount'         => $subaccount,
			'txnbearer'          => $txn_bearer,
			'transaction_charge' => $transaction_charge,
		);

		//-------------------------------------------------------------------------------------------

		// $pstk_logger = new paystack_plugin_tracker('pff-paystack', Kkd_Pff_Paystack_Public::fetchPublicKey());
		// $pstk_logger->log_transaction_attempt($code);*/

		echo json_encode( $response );
		die();
	}
}