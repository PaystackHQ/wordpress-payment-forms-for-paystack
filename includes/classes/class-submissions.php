<?php
namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Submissions class.
 */
class Submissions {

	/**
	 * Constructor
	 */
	public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_submissions_page' ] );
		add_action( 'admin_post_pff_paystack_export_excel', [ $this, 'export_excel' ] );
	}

	/**
	 * Register the submissions page
	 *
	 * @return void
	 */
	public function register_submissions_page() {
		add_submenu_page( 'edit.php?post_type=paystack_form', esc_html__( 'Submissions', 'pff-paystack' ), esc_html__( 'Submissions', 'pff-paystack' ), 'administrator', 'submissions', [ $this, 'output_submissions_page' ] );
		remove_submenu_page( 'edit.php?post_type=paystack_form', 'submissions' );
	}

	/**
	 * Outputs the Submissions page displaying the WP List table.
	 *
	 * @return void
	 */
	public function output_submissions_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['form'] ) ) { 
			return esc_html__( 'No form set', 'pff-paystack' );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id  = sanitize_text_field( wp_unslash( $_GET['form'] ) );
		$form     = get_post( $form_id );

		if ( 'paystack_form' === get_post_type( $form ) ) {
			$amount    = get_post_meta( $form_id, '_amount', true );
			$thankyou  = get_post_meta( $form_id, '_successmsg', true );
			$paybtn    = get_post_meta( $form_id, '_paybtn', true );
			$loggedin  = get_post_meta( $form_id, '_loggedin', true );
			$txncharge = get_post_meta( $form_id, '_txncharge', true );
	
			$payments_table = $this->get_payments_list_table();
			$data           = $payments_table->prepare_items();
			?>
			<div class="info-panel">
				<div class="info-panel-content">
				<h1 style="margin: 0px;"><?php echo esc_html( get_the_title( $form ) ); ?> <?php esc_html_e( 'Payments', 'pff-paystack' ); ?></h1>
					<p class="about-description">
						<?php esc_html_e( 'All payments made for this form', 'pff-paystack' ); ?>
					</p>
					<?php if ( $data > 0 ) { ?>
						<form action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="pff_paystack_export_excel">
							<input type="hidden" name="form_id" value="<?php echo esc_html( $form_id ); ?>">
							<button type="submit" class="button button-primary button-hero load-customize"><?php esc_html_e( 'Export Data to Excel', 'pff-paystack' ); ?></button>
						</form>
					<?php } ?>
					<br />
					<br />
				</div>
			</div>
			<div class="wrap">
				<div id="icon-users" class="icon32"></div>
				<?php $payments_table->display(); ?>
			</div>
			<?php
		}
	}

	/**
	 * Create a new instance of the Payments List Table.
	 *
	 * @return \paystack\payment_forms\Payments_List_Table
	 */
	public function get_payments_list_table() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		include_once PFF_PAYSTACK_PLUGIN_PATH . 'includes/classes/class-payments-list-table.php';
		return new \paystack\payment_forms\Payments_List_Table();
	}

	
	/**
	 * Wraps the line items as strings for CSVs
	 *
	 * @param string $item
	 * @return string
	 */
	public function prep_csv_data( $item ) {
		return '"' . str_replace( '"', '""', $item ) . '"';
	}
	
	/**
	 * Export data to Excel.
	 *
	 * @return void
	 */
	public function export_excel() {
		global $wpdb;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['form_id'] ) || empty( $_POST['form_id'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$form_id    = sanitize_text_field( wp_unslash( $_POST['form_id'] ) );
		$obj        = get_post( $form_id );
		$csv_output = '';
		$currency   = get_post_meta( $form_id, '_currency', true );

		if ( '' === $currency ) {
			$currency = 'NGN';
		}
		$helpers  = Helpers::get_instance();
		$all_data = $helpers->get_payments_by_id( $form_id );

		if ( count( $all_data ) > 0 ) {
			$header = $all_data[0];

			$csv_output .= "#,";
			$csv_output .= "Email,";
			$csv_output .= "Amount,";
			$csv_output .= "Date Paid,";
			$csv_output .= "Reference,";

			$new = json_decode( $header->metadata );
			if ( array_key_exists( 0, $new ) ) {
				foreach ( $new as $item ) {
					$csv_output .= $this->prep_csv_data( $item->display_name ) . ',';
				}
			} elseif ( count( $new ) > 0 ) {
				foreach ( $new as $key => $item ) {
					$csv_output .= $this->prep_csv_data( $key ) . ',';
				}
			}

			$csv_output .= "\n";

			foreach ( $all_data as $key => $dbdata ) {
				$newkey   = $key + 1;
				$txn_code = '' !== $dbdata->txn_code_2 ? $dbdata->txn_code_2 : $dbdata->txn_code;

				$csv_output .= $this->prep_csv_data( $newkey ) . ',';
				$csv_output .= $this->prep_csv_data( $dbdata->email ) . ',';
				$csv_output .= $this->prep_csv_data( $currency . ' ' . $dbdata->amount ) . ',';
				$csv_output .= $this->prep_csv_data( substr( $dbdata->paid_at, 0, 10 ) ) . ',';
				$csv_output .= $this->prep_csv_data( $txn_code ) . ',';

				$new = json_decode( $dbdata->metadata );
				if ( array_key_exists( 0, $new ) ) {
					foreach ( $new as $item ) {
						$csv_output .= $this->prep_csv_data( $item->value ) . ',';
					}
				} elseif ( count( $new ) > 0 ) {
					foreach ( $new as $item ) {
						$csv_output .= $this->prep_csv_data( $item ) . ',';
					}
				}

				$csv_output .= "\n";
			}

			$filename = $obj->post_title . "_payments_" . gmdate( 'Y-m-d_H-i' );

			header( 'Content-Type: application/vnd.ms-excel' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '.csv"' );
			echo $csv_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV output must not be escaped.
			exit;
		}
	}
}
