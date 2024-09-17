<?php
/**
 * The main plugin class, this will return the and instance of the class.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Settings class.
 */
class Settings {

	/**
	 * Holdes the array of settings fields.
	 *
	 * @var array
	 */
	private $fields = array();

	/**
	 * Construct the class.
	 */
	public function __construct() {
		$this->fields = array(
			'general' => array(
				'mode' => array(
					'title'   => __( 'Mode', 'paystack_forms' ),
					'type'    => 'select',
					'default' => 'test',
				),
				'tsk' => array(
					'title'   => __( 'Test Secret Key', 'paystack_forms' ),
					'type'    => 'password',
					'default' => '',
				),
				'tpk' => array(
					'title'   => __( 'Test Public Key', 'paystack_forms' ),
					'type'    => 'text',
					'default' => '',
				),
				'lsk' => array(
					'title'   => __( 'Live Secret Key', 'paystack_forms' ),
					'type'    => 'password',
					'default' => '',
				),
				'lpk' => array(
					'title'   => __( 'Live Public Key', 'paystack_forms' ),
					'type'    => 'text',
					'default' => '',
				),
			),
			'fees' => array(
				'prc' => array(	
					'title'   => __( 'Percentage', 'paystack_forms' ),
					'type'    => 'text',
					'default' => 1.5,
				),
				'ths' => array(	
					'title'   => __( 'Threshold <br> <small>(amount above which Paystack adds the fixed amount below)</small>', 'paystack_forms' ),
					'type'    => 'text',
					'default' => 2500,
				),
				'adc' => array(	
					'title'   => __( 'Additional Charge <br> <small> (amount added to percentage fee when transaction amount is above threshold) </small>', 'paystack_forms' ),
					'type'    => 'text',
					'default' => 100,
				),
				'cap' => array(	
					'title'   => __( 'Cap <br> <small> (maximum charge paystack can charge on your transactions)', 'paystack_forms' ),
					'type'    => 'text',
					'default' => 2000,
				),
			),
		);
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_menu', [ $this, 'register_settings_fields' ] );
	}

	/**
	 * Registers our settings sub page under the Paystack Forms menu item.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_submenu_page( 'edit.php?post_type=paystack_form', __( 'Settings', 'paystack_forms' ), __( 'Settings', 'paystack_forms' ), 'edit_posts', 'settings', [ $this, 'output_settings_page' ] );
	}

	/**
	 * Registers our Settings fields with the WP API.
	 *
	 * @return void
	 */
	public function register_settings_fields() {
		$fields = $this->get_settings_fields();
		// Run through each group, and the fields in there.
		foreach ( $fields as $group => $fields ) {
			foreach ( $fields as $field_key => $args ) {
				register_setting( 'kkd-pff-paystack-settings-group', $field_key );
			}
		}
	}

	public function output_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Paystack Forms Settings', 'paystack_forms' ); ?></h1>
			<h2><?php esc_html_e( 'API Keys Settings', 'paystack_forms' ); ?></h2>

			<span><?php echo wp_kses_post( __( 'Get your API Keys <a href="https://dashboard.paystack.co/#/settings/developer" target="_blank">here</a>', 'paystack_forms' ) ); ?> </span>
			
			<form method="post" action="options.php">
				<?php 
					settings_fields( 'kkd-pff-paystack-settings-group' );
					do_settings_sections( 'kkd-pff-paystack-settings-group' );
					$settings_fields = $this->get_settings_fields();
				?>
				<table class="form-table paystack_setting_page">
				<?php
					foreach ( $settings_fields['general'] as $key => $field ) {
						?>
						<tr valign="top">
							<th scope="row"><?php echo wp_kses_post( $field['title'] ); ?></th>
							<td>
							<?php if ( 'mode' === $key ) {
								$saved_val = get_option( 'mode', $field['default'] );
								?>
								<select class="form-control" name="<?php echo esc_attr( $key ); ?>" id="parent_id">
									<option value="test" <?php echo esc_attr( $this->is_option_selected( 'test', $saved_val ) ); ?>><?php esc_html_e( 'Test Mode', 'paystack_forms' ); ?></option>
									<option value="live" <?php echo esc_attr( $this->is_option_selected( 'live', $saved_val ) ); ?>><?php esc_html_e( 'Live Mode', 'paystack_forms' ); ?></option>
								</select>
							<?php } else { ?>
								<input type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_option( $key, $field['default'] ) ); ?>" />
							<?php } ?>
							</td>
						</tr>
						<?php
					}
				?>
				</table>
				<hr>
				<table class="form-table paystack_setting_page" id="paystack_setting_fees">
					<h2><?php esc_html_e( 'Fees Settings', 'paystack_forms' ); ?></h2>
					<?php
					foreach ( $settings_fields['fees'] as $key => $field ) {
						?>
						<tr valign="top">
							<th scope="row"><?php echo wp_kses_post( $field['title'] ); ?></th>
							<td>
								<input type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_option( $key, $field['default'] ) ); ?>" />
							</td>
						</tr>
						<?php
					}
					?>
				</table>

				<?php submit_button(); ?>

			</form>
		</div>
		<?php
	}

	/**
	 * Returns the array of settings fields.
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		return apply_filters( 'kkd_pff_paystack_settings_fields', $this->fields );
	}

	/**
	 * Checks to see if the curren value is selected.
	 *
	 * @param string $value
	 * @param string $compare
	 * @return string
	 */
	public function is_option_selected( $value, $compare ) {
		if ( $value == $compare ) {
			$result = "selected";
		} else {
			$result = "";
		}
		return $result;
	}
}