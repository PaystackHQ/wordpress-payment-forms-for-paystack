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
	 * Holds the current form meta data being built
	 *
	 * @var array
	 */
	protected $metadata = array();

	/**
	 * Holds the current untouched form meta, as a backup
	 *
	 * @var array
	 */
	protected $untouched = array();

	/**
	 * Holds the adjusted meta data after looking at the recurring.
	 *
	 * @var array
	 */
	protected $fixed_metadata = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_pff_paystack_submit_action', [ $this, 'submit_action' ] );
		add_action( 'wp_ajax_nopriv_pff_paystack_submit_action', [ $this, 'submit_action' ] );
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
		$this->helpers   = new Helpers();
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

		// Make sure we always have 1 quantity being purchased.
		if ( ! isset( $this->form_data['pf-quantity'] ) ) {
			$this->form_data['pf-quantity'] = 1;
		}
	}

	/**
	 * This will adjust the amount being paid according to the variable payment and amounts.
	 *
	 * @param integer $amount
	 * @return integer
	 */
	public function process_amount( $amount = 0 ) {
		$original_amount  = $amount;

		if ( 'no' === $this->meta['recur'] && 1 !== $this->meta['usevariableamount'] ) {
			if ( 0 !== (int) floatval( $this->meta['amount'] ) ) {
				$amount = floatval( $this->meta['amount'] );
			} else {
				$amount = $this->form_data['pf-amount'];
			}
			$amount = (int) str_replace( ' ', '', floatval( $amount ) );
		}

		if ( 1 === $this->meta['minimum'] && 0 !== floatval( $this->meta['amount'] ) ) {
			if ( $original_amount < floatval( $this->meta['amount'] ) ) {
				$amount = floatval( $this->meta['amount'] );
			} else {
				$amount = $original_amount;
			}
		}

		if ( 1 === $this->meta['usevariableamount'] ) {
			$payment_options = explode( ',', $this->meta['variableamount'] );
			if ( count( $payment_options ) > 0 ) {
				foreach ( $payment_options as $key => $payment_option ) {
					list( $a, $b ) = explode( ':', $payment_option );
					if ( $this->form_data['pf-vname'] == $a ) {
						$amount = $b;
					}
				}
			}
		}

		return $amount;
	}

	/**
	 * This will adjust the amount if the quantity fields are being used.
	 *
	 * @param integer $amount
	 * @return integer
	 */
	public function process_amount_quantity( $amount = 0 ) {
		if ( $this->meta['use_quantity'] === 'yes' && ! ( 'optional' === $this->meta['recur'] || 'plan' === $this->meta['recur'] ) ) {
			$quantity   = $this->form_data['pf-quantity'];
			$unit_amt   = (int) str_replace( ' ', '', $amount );
			$amount     = $quantity * $unit_amt;
		}
		return $amount;
	}

	/**
	 * This function uploads the images with media_handle_upload and adds them to the metadata array.
	 *
	 * @return void
	 */
	public function process_images() {
		$max_file_size = $this->meta['filelimit'] * 1024 * 1024;
		if ( ! empty( $_FILES ) ) {
			foreach ( $_FILES as $key_name => $value ) {
				if ( $value['size'] > 0 ) {
					if ( $value['size'] > $max_file_size ) {
						$response['result']  = 'failed';
						$response['message'] = sprintf( __( 'Max upload size is %sMB', 'pff-paystack' ), $this->meta['filelimit'] );
						exit( wp_json_encode( $response ) );
					} else {
						$attachment_id  = media_handle_upload( $key_name, $this->form_id );
						$url            = wp_get_attachment_url( $attachment_id );
						$this->fixed_metadata[] = array(
							'display_name'  => ucwords( str_replace( '_', ' ', $key_name ) ),
							'variable_name' => $key_name,
							'type'          => 'link',
							'value'         => $url,
						);
					}
				} else {
					$this->fixed_metadata[] = array(
						'display_name'  => ucwords( str_replace( '_', ' ', $key_name ) ),
						'variable_name' => $key_name,
						'type'          => 'text',
						'value'         => __( 'No file Uploaded', 'pff-paystack' ),
					);
				}
			}
		}
	}

	public function submit_action() {
		/**
		 * TODO - Needs better security checks - NONCE
		 */
		if ( ! $this->valid_submission() ) {
			// Exit here, for not processing further because of the error
			exit( wp_json_encode( $this->response ) );
		}

		/**
		 * Setup our data to be processed.
		 */
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
		$code            = $this->generate_code();
		$table           = $wpdb->prefix . PFF_PAYSTACK_TABLE;
		
		$this->fixed_metadata = [];
	
		$amount = (int) str_replace( ' ', '', $this->form_data['pf-amount'] );
		$amount = $this->process_amount( $amount );

		// Store the single unit price.
		$this->fixed_metadata[] = array(
			'display_name'  => __( 'Unit Price', 'pff-paystack' ),
			'variable_name' => 'Unit_Price',
			'type'          => 'text',
			'value'         => $this->meta['currency'] . number_format( $amount ),
		);
	
		if ( 'customer' === $this->meta['txncharge'] ) {
			$amount = $this->helpers->process_transaction_fees( $amount );
		}

		/**
		 * This function will exit early if one of the images is too large to be uploaded.
		 */
		$this->process_images();

		$this->process_recurring_plans( $amount );

		$this->fixed_metadata = json_decode( wp_json_encode( $this->fixed_metadata, JSON_NUMERIC_CHECK ), true );
		$this->fixed_metadata = array_merge( $this->untouched, $this->fixed_metadata );



		$insert = array(
			'post_id'  => $this->form_data['pf-id'],
			'email'    => $this->form_data['pf-pemail'],
			'user_id'  => $this->form_data['pf-user_id'],
			'amount'   => $amount,
			'plan'     => $this->meta['plancode'],
			'ip'       => $this->helpers->get_the_user_ip(),
			'txn_code' => $code,
			'metadata' => wp_json_encode( $this->fixed_metadata ),
		);

		$exist = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * 
					FROM {$table} 
					WHERE post_id = %s 
					AND email = %s 
					AND user_id = %s 
					AND amount = %s 
					AND plan = %s 
					AND ip = %s 
					AND paid = '0' 
					AND metadata = %s",
				$insert['post_id'], 
				$insert['email'],
				$insert['user_id'],
				$insert['amount'],
				$insert['plan'],
				$insert['ip'],
				$insert['metadata']
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
		}

		/**
		 * Allow 3rd party plugins to send off an invoice as well
		 * 
		 * 11: Email_Invoice::send_invoice();
		 */
		if ( 'yes' === $this->meta['sendinvoice'] ) {
			do_action( 'pff_paystack_send_invoice', $this->meta['currency'], $insert['amount'], $this->form_data['pf-fname'], $insert['email'], $code );
		}

		$transaction_charge = (int) $this->meta['merchantamount'];
        $transaction_charge = $transaction_charge * 100;

		if ( '' == $this->meta['subaccount'] || ! isset( $this->meta['subaccount'] ) ) {
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
			'quantity'           => $this->form_data['pf-quantity'],
			'email'              => $insert['email'],
			'name'               => $this->form_data['pf-fname'],
			'total'              => round( $amount ),
			'currency'           => $this->meta['currency'],
			'custom_fields'      => $this->fixed_metadata,
			'subaccount'         => $subaccount,
			'txnbearer'          => $txn_bearer,
			'transaction_charge' => $transaction_charge,
		);

		//-------------------------------------------------------------------------------------------

		// $pstk_logger = new paystack_plugin_tracker('pff-paystack', Kkd_Pff_Paystack_Public::fetchPublicKey());
		// $pstk_logger->log_transaction_attempt($code);*/

		echo wp_json_encode( $response );
		die();
	}

	/**
	 * This function looks for a recurring plan set by the customer, a recurring plan code set by the owner.
	 */
	public function process_recurring_plans( $amount ) {
		$plan_code    = 'none';
		$has_interval = false;

		if ( 'no' !== $this->meta['recur'] ) {

			// is the user setting the interval?
			if ( 'optional' === $this->meta['recur'] ) {
				$interval = $this->form_data['pf-interval'];

				// Only create a subscription plan if they choose an interval.
				if ( 'no' !== $interval ) {
					$unit_amount    = $amount * 100;
					$possible_plan = pff_paystack()->classes['request-plan']->list_plans( '?amount=' . $unit_amount . '&interval=' . $interval );

					// If we have found a plan, then use that code, otherwise create a new one.
					if ( false !== $possible_plan && isset( $possible_plan->plan_code ) ) {
						$plan_code    = $possible_plan->plan_code;
						$has_interval = $possible_plan->interval;
					} else {
						// Create Plan.
						$body = array(
							'name'     => get_the_title( $this->form_id ) . ' [' . $this->meta['currency'] . number_format( $amount ) . '] - ' . $interval,
							'amount'   => $unit_amount,
							'interval' => $interval,
						);
						$created_plan = pff_paystack()->classes['request-plan']->create_plan( $body );
						if ( false !== $created_plan && isset( $created_plan->plan_code ) ) {
							$plan_code    = $created_plan->data->plan_code;
							$has_interval = $created_plan->data->interval;
						}
					}
				}
			} else {
				// Use Plan Code.
				$plan_code = sanitize_text_field( wp_unslash( $this->form_data['pf-plancode'] ) );
				unset( $this->metadata['pf-plancode'] );
			}
		}
		
		if ( 'none' !== $plan_code ) {
			$this->meta['plancode'] = $plan_code;
			$this->fixed_metadata[] = array(
				'display_name'  => __( 'Plan', 'pff-paystack' ),
				'variable_name' => 'Plan',
				'type'          => 'text',
				'value'         => $plan_code,
			);

			if ( false !== $has_interval ) {
				$this->fixed_metadata[] = array(
					'display_name'  => __( 'Plan Interval', 'pff-paystack' ),
					'variable_name' => 'Plan Interval',
					'type'          => 'text',
					'value'         => $has_interval,
				);
			}

		} else if ( ! isset( $this->meta['plancode'] )  ) {
			$this->meta['plancode'] = '';
		}
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
}