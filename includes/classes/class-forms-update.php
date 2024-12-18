<?php
/**
 * The class that will update the forms data.
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the additional functions for the WP Dashboard Forms List
 */
class Forms_Update {

	/**
	 * Holds the meta field keys and the default values.
	 *
	 * @var array
	 */
	public $defaults = [];

	/**
	 * Holds the meta values for the current form, using the meta key as the index.
	 *
	 * @var array
	 */
	public $meta = [];

	/**
	 * Holds the allowed HTML for output.
	 *
	 * @var array
	 */
	public $allowed_html = [];

	/**
	 * The helpers class
	 *
	 * @var paystack\payment_forms\Helpers
	 */
	public $helpers = [];

	/**
	 * Returns true if this is the paystack screen.
	 *
	 * @var boolean
	 */
	public $is_screen = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->set_vars();
		add_action( 'admin_head', [ $this, 'setup_actions' ] );
		add_filter( 'admin_head', [ $this, 'disable_wyswyg' ], 10, 1 );

		// Default Content.
		add_filter('default_content', [ $this, 'default_content' ], 10, 2);

		// Define the meta boxes.
		add_action( 'edit_form_after_title', [ $this, 'metabox_action' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );

		// Save the Meta boxes
		add_action( 'save_post', [ $this, 'save_post_meta' ], 1, 2 );
	}

	/**
	 * Sets useable variables like the fields.
	 *
	 * @return void
	 */
	public function set_vars() {
		$this->helpers      = Helpers::get_instance();
		$this->defaults     = $this->helpers->get_meta_defaults();
		$this->allowed_html = $this->helpers->get_allowed_html();
	}
	
	/**
	 * Add the phone number as the default content when a form is created.
	 *
	 * @param string $content
	 * @param WP_Post $post
	 * @return string
	 */
	public function default_content( $content, $post ) {
		switch ( $post->post_type ) {
			case 'paystack_form':
				$content = '[text name="' . esc_html__( 'Phone Number', 'pff-paystack' ) . '"]';
				break;
			default:
				$content = '';
				break;
		}
		return $content;
	}

	/**
	 * Run some actions on admin_head
	 *
	 * @return void
	 */
	public function setup_actions() {
		$screen = get_current_screen();
		if ( null !== $screen && isset( $screen->post_type ) && 'paystack_form' === $screen->post_type ) {
			$this->is_screen = true;

			add_filter( 'user_can_richedit', '__return_false', 50 );
			add_filter( 'quicktags_settings', [ $this, 'remove_fullscreen' ], 10, 1 );
	
			remove_action( 'media_buttons', 'media_buttons' );
			remove_meta_box( 'postimagediv', 'post', 'side' );
	
			add_action( 'admin_print_footer_scripts', [ $this, 'shortcode_buttons_script' ] );
		}
	}

	/**
	 * Outputs CSS to hide the WYSIWYG
	 *
	 * @param string $default
	 * @return string
	 */
	public function disable_wyswyg( $default ) {
		if ( 'paystack_form' === get_post_type() ) {
			?> 
			<style>#edit-slug-box,#message p > a{display:none;}</style>
			<?php
		}
		return $default;
	}

	/**
	 * Remove the fullscreen option
	 *
	 * @param array $arguments
	 * @return array
	 */
	public function remove_fullscreen( $arguments ) {
		if ( $this->is_screen ) {
			$arguments['buttons'] = 'fullscreen';
		}
		return $arguments;
	}

	/**
	 * Outputs the QuickTags scripts needed to generate the field shortcodes.
	 *
	 * @return void
	 */
	public function shortcode_buttons_script() {
		if ( $this->is_screen && wp_script_is( 'quicktags' ) ) {
			?>
			<script type="text/javascript">
			//this function is used to retrieve the selected text from the text editor
			function getSel() {
				var txtarea = document.getElementById( "content" );
				var start = txtarea.selectionStart;
				var finish = txtarea.selectionEnd;
				return txtarea.value.substring(start, finish);
			}
			
			QTags.addButton(
				"t_shortcode",
				"Insert Text",
				insertText
			);
			
			function insertText() {
				QTags.insertContent('[text name="Text Title"]');
			}
			QTags.addButton(
				"ta_shortcode",
				"Insert Textarea",
				insertTextarea
			);
			
			function insertTextarea() {
				QTags.insertContent('[textarea name="Text Title"]');
			}
			QTags.addButton(
				"s_shortcode",
				"Insert Select Dropdown",
				insertSelectb
			);
			
			function insertSelectb() {
				QTags.insertContent('[select name="Text Title" options="option 1,option 2,option 3"]');
			}
			QTags.addButton(
				"r_shortcode",
				"Insert Radio Options",
				insertRadiob
			);
			
			function insertRadiob() {
				QTags.insertContent('[radio name="Text Title" options="option 1,option 2,option 3"]');
			}
			QTags.addButton(
				"cb_shortcode",
				"Insert Checkbox Options",
				insertCheckboxb
			);
			
			function insertCheckboxb() {
				QTags.insertContent('[checkbox name="Text Title" options="option 1,option 2,option 3"]');
			}
			QTags.addButton(
				"dp_shortcode",
				"Insert Datepicker",
				insertDatepickerb
			);
			
			function insertDatepickerb() {
				QTags.insertContent('[datepicker name="Datepicker Title"]');
			}
			QTags.addButton(
				"i_shortcode",
				"Insert File Upload",
				insertInput
			);
			
			function insertInput() {
				QTags.insertContent('[input name="File Name"]');
			}
			QTags.addButton(
				"ngs_shortcode",
				"Insert Nigerian States",
				insertSelectStates
			);
			
			function insertSelectStates() {
				QTags.insertContent(
					'[select name="State" options="<?php echo esc_attr( $this->helpers->get_states( true ) ); ?>"]'
				);
			}
			QTags.addButton(
				"ctys_shortcode",
				"Insert All Countries",
				insertSelectCountries
			);
			
			function insertSelectCountries() {
				QTags.insertContent(
					'[select  name="country" options="<?php echo esc_attr( $this->helpers->get_countries( true ) ); ?>"] '
				);
			}
			
			//
			</script>
			<?php
		}
	}

	/**
	 * Adds in a custom action to allow us to hook into just under the forms title.
	 *
	 * @param \WP_Post $post
	 * @return void
	 */
	public function metabox_action( $post ) {
		if ( $this->is_screen ) {
			$this->parse_meta_values( $post );
			do_meta_boxes( 'paystack_form', 'pff', $post );
		}
		
	}

	/**
	 * Registers our custom metaboxes.
	 *
	 * @return void
	 */
	public function register_meta_boxes() {
		$screen = get_current_screen();
		if ( null !== $screen && isset( $screen->post_type ) && 'paystack_form' === $screen->post_type ) {
			$this->is_screen = true;

			// Register the information boxes.
			if ( isset( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				add_meta_box( 'pff_paystack_editor_details_box', esc_html__( 'Paste shortcode on preferred page', 'paystack_form' ), [ $this, 'shortcode_details' ], 'paystack_form', 'pff' );
			}
			add_meta_box( 'pff_paystack_editor_help_box', esc_html__( 'Help Section', 'pff-paystack' ), [ $this, 'help_details' ], 'paystack_form', 'pff' );

			// Add in our "normal" meta boxes
			add_meta_box( 'form_data', esc_html__( 'Extra Form Description', 'pff-paystack' ), [ $this, 'form_data' ], 'paystack_form', 'normal', 'default' );
			add_meta_box( 'email_data', esc_html__( 'Email Receipt Settings', 'pff-paystack' ), [ $this, 'email_data' ], 'paystack_form', 'normal', 'default' );

			// Add in our "side" meta boxes
			add_meta_box( 'recuring_data', esc_html__( 'Recurring Payment', 'pff-paystack' ), [ $this, 'recur_data' ], 'paystack_form', 'side', 'default' );
			add_meta_box( 'quantity_data', esc_html__( 'Quantity Payment', 'pff-paystack' ), [ $this, 'quantity_data' ], 'paystack_form', 'side', 'default' );
			add_meta_box( 'agreement_data', esc_html__( 'Agreement checkbox', 'pff-paystack' ), [ $this, 'agreement_data' ], 'paystack_form', 'side', 'default' );
			add_meta_box( 'subaccount_data', esc_html__( 'Sub Account', 'pff-paystack' ), [ $this, 'subaccount_data' ], 'paystack_form', 'side', 'default' );
			add_meta_box( 'plan_data', esc_html__( '*Special: Subscribe to plan after time', 'pff-paystack' ), [ $this, 'plan_data' ], 'paystack_form', 'side', 'default' );		
		}	
	}
	
	/**
	 * Output the shortcode details
	 *
	 * @param WP_Post $post
	 * @return void
	 */
	public function shortcode_details( $post ) {
		?>
		<p class="description">
			<label for="wpcf7-shortcode"><?php esc_html_e( 'Copy this shortcode and paste it into your post, page, or text widget content:', 'pff-paystack' ); ?></label>
			<span class="shortcode wp-ui-highlight">
				<input type="text" id="wpcf7-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[pff-paystack id=&quot;<?php echo esc_html( $post->ID ); ?>&quot;]">
			</span>
		</p>
		<?php
	}

	/**
	 * Outputs the help details below the title.
	 *
	 * @param WP_Post $post
	 * @return void
	 */
	public function help_details( $post ) {
			?>
			<div class="awesome-meta-admin">
				<?php echo wp_kses_post( __( 'Email and Full Name field is added automatically, no need to include that.<br /><br />
				To make an input field compulsory add <code> required="required" </code> to the shortcode <br /><br />
				It should look like this <code> [text name="Full Name" required="required" ]</code><br /><br />' ) ) ; ?>

				<?php echo wp_kses_post( __( '<b style="color:red;">Warning:</b> Using the file input field may cause data overload on your server.
				Be sure you have enough server space before using it. You also have the ability to set file upload limits.' ) ) ; ?>
			</div>
		<?php
	}

	/**
	 * Gets the current meta fields and set the defaults if needed.
	 *
	 * @param WP_Post $post
	 * @return void
	 */
	public function parse_meta_values( $post ) {
		$this->meta = $this->helpers->parse_meta_values( $post );
	}

	/**
	 * Outputs the Extra Form Description Meta Box.
	 *
	 * @return void
	 */
	public function form_data() {
		$html = [];

		// We shall output 1 Nonce Field for all of our metaboxes.
		$html[] = wp_nonce_field( 'pff-paystack-save-form', 'pff_paystack_save', true, false );

		if ($this->meta['hidetitle'] == 1) {
			$html[] = '<label><input name="_hidetitle" type="checkbox" value="1" checked> ' . esc_html__('Hide the form title', 'pff-paystack') . ' </label>';
		} else {
			$html[] = '<label><input name="_hidetitle" type="checkbox" value="1" > ' . esc_html__('Hide the form title', 'pff-paystack') . ' </label>';
		}
		$html[] = '<br>';
		$html[] = '<p>Currency:</p>';
		$html[] = '<select class="form-control" name="_currency" style="width:100%;">
					<option value="NGN" ' . $this->is_option_selected( 'NGN', $this->meta['currency'] ) . '>' . esc_html__('Nigerian Naira', 'pff-paystack') . '</option>
					<option value="GHS" ' . $this->is_option_selected( 'GHS', $this->meta['currency'] ) . '>' . esc_html__('Ghanaian Cedis', 'pff-paystack') . '</option>
					<option value="ZAR" ' . $this->is_option_selected( 'ZAR', $this->meta['currency'] ) . '>' . esc_html__('South African Rand', 'pff-paystack') . '</option>
					<option value="KES" ' . $this->is_option_selected( 'KES', $this->meta['currency'] ) . '>' . esc_html__('Kenyan Shillings', 'pff-paystack') . '</option>
					<option value="XOF" ' . $this->is_option_selected( 'XOF', $this->meta['currency'] ) . '>' . esc_html__('West African CFA Franc', 'pff-paystack') . '</option>
					<option value="RWF" ' . $this->is_option_selected( 'RWF', $this->meta['currency'] ) . '>' . esc_html__('Rwandan Franc', 'pff-paystack') . '</option>
					<option value="EGP" ' . $this->is_option_selected( 'EGP', $this->meta['currency'] ) . '>' . esc_html__('Egyptian Pound', 'pff-paystack') . '</option>
					<option value="USD" ' . $this->is_option_selected( 'USD', $this->meta['currency'] ) . '>' . esc_html__('US Dollars', 'pff-paystack') . '</option>
			  </select>';

		$html[] = wp_kses_post( '<small>' . __('Ensure you are activated for the currency you are selecting. Check <a href="https://support.paystack.com/hc/en-us/articles/360009973799-Can-I-accept-payments-in-US-Dollars-USD" target="_blank">here</a> for more information.', 'pff-paystack') . '</small>' );
		$html[] = '<p>' . esc_html__('Amount to be paid(Set 0 for customer input):', 'pff-paystack') . '</p>';
		$html[] = '<input type="number" min="0" name="_amount" value="' . $this->meta['amount'] . '" class="widefat pf-number" />';
		if ($this->meta['minimum'] == 1) {
			$html[] = '<br><label><input name="_minimum" type="checkbox" value="1" checked> ' . esc_html__('Make amount minimum payable', 'pff-paystack') . ' </label>';
		} else {
			$html[] = '<br><label><input name="_minimum" type="checkbox" value="1"> ' . esc_html__('Make amount minimum payable', 'pff-paystack') . ' </label>';
		}
		$html[] = '<p>' . esc_html__('Variable Dropdown Amount:', 'pff-paystack') . '<code><label>' . esc_html__('Format(option:amount):  Option 1:10000,Option 2:3000 Separate options with "," ', 'pff-paystack') . '</label></code></p>';
		$html[] = '<input type="text" name="_variableamount" value="' . $this->meta['variableamount'] . '" class="widefat " />';
		$html[] = '<br><label><input name="_usevariableamount" type="checkbox" value="1" ' . $this->is_option_selected( 1, $this->meta['usevariableamount'], 'checked' ) . '> ' . esc_html__('Use dropdown amount option', 'pff-paystack') . ' </label>';
		

		$html[] = '<p>' . esc_html__('Pay button Description:', 'pff-paystack') . '</p>';
		$html[] = '<input type="text" name="_paybtn" value="' . $this->meta['paybtn'] . '" class="widefat" />';
		$html[] = '<p>' . esc_html__('Add Extra Charge:', 'pff-paystack') . '</p>';

		$html[] = wp_kses_post( '<select class="form-control" name="_txncharge" id="parent_id" style="width:100%;">
							<option value="merchant" ' . $this->is_option_selected('merchant', $this->meta['txncharge']) . '> ' . esc_html__('No, do not add', 'pff-paystack') . '</option>
							<option value="customer" ' . $this->is_option_selected('customer', $this->meta['txncharge']) . '> ' . esc_html__('Yes, add it', 'pff-paystack') . '</option>
						</select>
					<br><small>' . __('This allows you include an extra charge to cushion the effect of the transaction fee. <a href="', 'pff-paystack') . get_admin_url() . "edit.php?post_type=paystack_form&page=class-paystack-forms-admin.php#paystack_setting_fees" . '">' . esc_html__('Configure', 'pff-paystack') . '</a></small>' );
		$html[] = '<p>' . esc_html__('User logged In:', 'pff-paystack') . '</p>';
		$html[] = '<select class="form-control" name="_loggedin" id="parent_id" style="width:100%;">
							<option value="no" ' . $this->is_option_selected('no', $this->meta['loggedin']) . '> ' . esc_html__('User must not be logged in', 'pff-paystack') . '</option>
							<option value="yes" ' . $this->is_option_selected('yes', $this->meta['loggedin']) . '> ' . esc_html__('User must be logged In', 'pff-paystack') . '</option>
						</select>';
		$html[] = '<p>' . esc_html__('Success Message after Payment', 'pff-paystack') . '</p>';
		$html[] = '<textarea rows="3"  name="_successmsg"  class="widefat" >' . $this->meta['successmsg'] . '</textarea>';
		$html[] = '<p>' . esc_html__('File Upload Limit(MB):', 'pff-paystack') . '</p>';
		$html[] = '<input type="number" name="_filelimit" value="' . $this->meta['filelimit'] . '" class="widefat  pf-number" />';
		$html[] = '<p>' . esc_html__('Redirect to page link after payment(keep blank to use normal success message):', 'pff-paystack') . '</p>';
		$html[] = '<input type="text" name="_redirect" value="' . $this->meta['redirect'] . '" class="widefat" />';
		
		// To output the concatenated $html array content
		echo wp_kses( implode( '', $html ), $this->allowed_html ); 
	}

	/**
	 * Checks to see if the curren value is selected.
	 *
	 * @param string $value
	 * @param string $compare
	 * @return string
	 */
	public function is_option_selected( $value, $compare, $selected = 'selected' ) {
		if ( $value == $compare ) {
			$result = $selected;
		} else {
			$result = "";
		}
		return $result;
	}

	/**
	 * Output the recurring data meta box.
	 *
	 * @return void
	 */
	public function recur_data(){
		$html   = [];
		$html[] = '<p>' . esc_html__('Recurring Payment:', 'pff-paystack') . '</p>';
		$html[] = '<select class="form-control" name="_recur" style="width:100%;">
					  <option value="no" ' . $this->is_option_selected('no', $this->meta['recur']) . '>' . esc_html__('None', 'pff-paystack') . '</option>
					  <option value="optional" ' . $this->is_option_selected('optional', $this->meta['recur']) . '>' . esc_html__('Optional Recurring', 'pff-paystack') . '</option>
					  <option value="plan" ' . $this->is_option_selected('plan', $this->meta['recur']) . '>' . esc_html__('Paystack Plan', 'pff-paystack') . '</option>
					</select>';
		$html[] = '<p>' . esc_html__('Paystack Recur Plan code:', 'pff-paystack') . '</p>';
		$html[] = '<input type="text" name="_recurplan" value="' . $this->meta['recurplan'] . '" class="widefat" />
				   <small>' . esc_html__('Plan amount must match amount on extra form description.', 'pff-paystack') . '</small>';
		
		// Output the accumulated HTML
		echo wp_kses( implode( '', $html ), $this->allowed_html ); 
	}

	/**
	 * Add the email metabox
	 *
	 * @return void
	 */
	public function email_data() {
		$html   = [];
		// Echo out the field
		$html[] = '<p>' . esc_html__('Send an invoice when a payment is attempted:', 'pff-paystack') . '</p>';
		$html[] = '<select class="form-control" name="_sendinvoice" id="parent_id" style="width:100%;">
					  <option value="no" ' . $this->is_option_selected('no', $this->meta['sendinvoice']) . '>' . esc_html__('Don\'t send', 'pff-paystack') . '</option>
					  <option value="yes" ' . $this->is_option_selected('yes', $this->meta['sendinvoice']) . '>' . esc_html__('Send', 'pff-paystack') . '</option>
				   </select>';
		$html[] = '<p>' . esc_html__('Send Email Receipt:', 'pff-paystack') . '</p>';
		$html[] = '<select class="form-control" name="_sendreceipt" id="parent_id" style="width:100%;">
					  <option value="no" ' . $this->is_option_selected('no', $this->meta['sendreceipt']) . '>' . esc_html__('Don\'t send', 'pff-paystack') . '</option>
					  <option value="yes" ' . $this->is_option_selected('yes', $this->meta['sendreceipt']) . '>' . esc_html__('Send', 'pff-paystack') . '</option>
				   </select>';
		$html[] = '<p>' . esc_html__('Email Subject:', 'pff-paystack') . '</p>';
		$html[] = '<input type="text" name="_subject" value="' . $this->meta['subject'] . '" class="widefat" />';
		$html[] = '<p>' . esc_html__('Merchant Name on Receipt:', 'pff-paystack') . '</p>';
		$html[] = '<input type="text" name="_merchant" value="' . $this->meta['merchant'] . '" class="widefat" />';
		$html[] = '<p>' . esc_html__('Email Heading:', 'pff-paystack') . '</p>';
		$html[] = '<input type="text" name="_heading" value="' . $this->meta['heading'] . '" class="widefat" />';
		$html[] = '<p>' . esc_html__('Email Body/Message:', 'pff-paystack') . '</p>';
		$html[] = '<textarea rows="6" name="_message" class="widefat">' . $this->meta['message'] . '</textarea>';
		
		echo wp_kses( implode( '', $html ), $this->allowed_html ); 
	}

	/**
	 * Add the quantity metabox
	 *
	 * @return void
	 */
	public function quantity_data() {
		$html   = [];

		// Echo out the field
		$html[] = '<small>' . esc_html__('Allow your users pay in multiple quantity', 'pff-paystack') . '</small>
			<p>' . esc_html__('Quantified Payment:', 'pff-paystack') . '</p>';

		if ($this->meta['recur'] != "no") {
			$html[] = '<select disabled class="form-control" name="_usequantity" style="width:100%;">
				<option value="no" ' . $this->is_option_selected('no', $this->meta['usequantity']) . '>' . esc_html__('No', 'pff-paystack') . '</option>
				</select>';
		} else {
			$html[] = '<select class="form-control" name="_usequantity" style="width:100%;">
				<option value="no" ' . $this->is_option_selected('no', $this->meta['usequantity']) . '>' . esc_html__('No', 'pff-paystack') . '</option>
				<option value="yes" ' . $this->is_option_selected('yes', $this->meta['usequantity']) . '>' . esc_html__('Yes', 'pff-paystack') . '</option>
				</select>';
		}

		if ($this->meta['usequantity'] == "yes") {

			$html[] = '<p>' . esc_html__('Max payable quantity:', 'pff-paystack') . '</p>';
			$html[] = '<input type="number" min="1"  name="_quantity" value="' . $this->meta['quantity'] . '" class="widefat  pf-number" /><small>' . esc_html__('Your users only get to pay in quantities if the from amount is not set to zero and recur is set to none.', 'pff-paystack') . '</small>';
			$html[] = '<p>' . esc_html__('Unit of quantity:', 'pff-paystack') . '</p>';
			$html[] = wp_kses_post( '<input type="text" name="_quantityunit" value="' . $this->meta['quantityunit'] . '" class="widefat" /><small>' . __('What is the unit of this quantity? Default is <code>Quantity</code>.', 'pff-paystack') . '</small>' );

			$html[] = '<p>' . esc_html__('Inventory Payment:', 'pff-paystack') . '</p>';
			$html[] = '<select class="form-control" name="_useinventory" style="width:100%;">
				<option value="no" ' . $this->is_option_selected('no', $this->meta['useinventory']) . '>' . esc_html__('No', 'pff-paystack') . '</option>
				<option value="yes" ' . $this->is_option_selected('yes', $this->meta['useinventory']) . '>' . esc_html__('Yes', 'pff-paystack') . '</option>
				</select>
				<small>' . esc_html__('Set maximum available items in stock', 'pff-paystack') . '</small>';
		}

		if ($this->meta['useinventory'] == "yes" && $this->meta['usequantity'] == "yes") {
			$html[] = '<p>' . esc_html__('Total Inventory', 'pff-paystack') . '</p>';
			$html[] = '<input type="number" min="' . $this->meta['sold'] . '" name="_inventory" value="' . $this->meta['inventory'] . '" class="widefat  pf-number" />';
			$html[] = '<p>' . esc_html__('Already sold', 'pff-paystack') . '</p>';
			$html[] = '<input type="number" name="_sold" value="' . $this->meta['sold'] . '" class="widefat  pf-number" />
				<small></small>
				<br/>';
		}

		echo wp_kses( implode( '', $html ), $this->allowed_html ); 
	}

	/**
	 * Add the agreement metabox
	 *
	 * @return void
	 */
	public function agreement_data() {
		$html   = [];
		
		// Add components to the $html array
		$html[] = '<p>' . esc_html__( 'Use agreement checkbox:', 'pff-paystack' ) . '</p>';
		$html[] = '<select class="form-control" name="_useagreement" style="width:100%;">
					<option value="no" ' . $this->is_option_selected('no', $this->meta['useagreement']) . '>' . esc_html__( 'No', 'pff-paystack' ) . '</option>
					<option value="yes" ' . $this->is_option_selected('yes', $this->meta['useagreement']) . '>' . esc_html__( 'Yes', 'pff-paystack' ) . '</option>
				</select>';
		$html[] = '<p>' . esc_html__( 'Agreement Page Link:', 'pff-paystack' ) . '</p>';
		$html[] = '<input type="text" name="_agreementlink" value="' . $this->meta['agreementlink']  . '" class="widefat" />';
		echo wp_kses( implode( '', $html ), $this->allowed_html );
	}

	/**
	 * Output the Subaccount metabox.
	 *
	 * @return void
	 */
	public function subaccount_data() {
		$html   = [];
		// Add components to the $html array
		$html[] = '<p>' . esc_html__( 'Sub Account code:', 'pff-paystack' ) . '</p>';
		$html[] = '<input type="text" name="_subaccount" value="' . $this->meta['subaccount']  . '" class="widefat" />';
		$html[] = '<p>' . esc_html__( 'Transaction Charge bearer:', 'pff-paystack' ) . '</p>';
		$html[] = '<select class="form-control" name="_txnbearer" id="parent_id" style="width:100%;">
					<option value="account" ' . $this->is_option_selected('account', $this->meta['txnbearer']) . '>' . esc_html__( 'Merchant (default)', 'pff-paystack' ) . '</option>
					<option value="subaccount" ' . $this->is_option_selected('subaccount', $this->meta['txnbearer']) . '>' . esc_html__( 'Sub Account', 'pff-paystack' ) . '</option>
				</select>';
		$html[] = '<p>' . esc_html__( 'Merchant Amount:', 'pff-paystack' ) . '</p>';
		$html[] = '<input type="text" name="_merchantamount" value="' . $this->meta['merchantamount'] . '" class="widefat" />';
		echo wp_kses( implode( '', $html ), $this->allowed_html );
	}

	/**
	 * Output the Plan metabox
	 *
	 * @return void
	 */
	public function plan_data() {
		$html   = [];
		$html[] = '<p>' . esc_html__( 'User subscribes to plan after number of days:', 'pff-paystack' ) . '</p>';
		$html[] = '<p>' . esc_html__( 'Number of days:', 'pff-paystack' ) . '</p>';
		$html[] = '<input type="number" name="_startdate_days" value="' . $this->meta['startdate_days'] . '" class="widefat pf-number" />';
		$html[] = '<p>' . esc_html__( 'Plan:', 'pff-paystack' ) . '</p>';
		$html[] = '<input type="text" name="_startdate_plan_code" value="' . $this->meta['startdate_plan_code'] . '" class="widefat" />';
		
		if ($this->meta['startdate_enabled'] == 1) {
			$html[] = '<p><br><label><input name="_startdate_enabled" type="checkbox" value="1" checked> ' . esc_html__( 'Enable', 'pff-paystack' ) . ' </label></p>';
		} else {
			$html[] = '<p><br><label><input name="_startdate_enabled" type="checkbox" value="1"> ' . esc_html__( 'Enable', 'pff-paystack' ) . ' </label></p>';
		}
		echo wp_kses( implode( '', $html ), $this->allowed_html );
	}

	/**
	 * Saves the post meta field stored in the $defaults variable.
	 *
	 * @param int|string $post_id
	 * @param WP_Post $post
	 * @return void
	 */
	public function save_post_meta( $form_id, $post ) {

		$screen = get_current_screen();
		if ( null !== $screen && isset( $screen->post_type ) && 'paystack_form' === $screen->post_type ) {
			$this->is_screen = true;

			if ( ! isset( $_POST['pff_paystack_save'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pff_paystack_save'] ) ), 'pff-paystack-save-form' ) ) {
				return $form_id;
			}
			
			// Is the user allowed to edit the post or page?
			if ( ! current_user_can('edit_post', $form_id ) ) {
				return $form_id;
			}
			
			// Cycle through our fields and save the information.
			foreach ( $this->defaults as $key => $default ) { 
				if ( $post->post_type == 'revision' ) {
					return; // Don't store custom data twice
				}
			
				if ( isset( $_POST[ '_' . $key ] ) ) {
					$value = sanitize_text_field( wp_unslash( $_POST[ '_' . $key ] ) );
				} else {
					$value = $default;
				}
			
				$value = implode( ',', (array) $value ); // If $value is an array, make it a CSV (unlikely)
				if ( get_post_meta( $form_id, '_' . $key, false ) ) { // If the custom field already has a value
					update_post_meta( $form_id, '_' . $key, $value );
				} else { // If the custom field doesn't have a value
					add_post_meta( $form_id, '_' . $key, $value );
				}
				if ( ! $value ) {
					delete_post_meta( $form_id, '_' . $key ); // Delete if blank
				}
			}
		}
	}
}
