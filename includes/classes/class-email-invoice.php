<?php
/**
 * The Invoice Email sent to the customer before payment.
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
class Email_Invoice extends Email {

	/**
	 * The slug / key if the email
	 *
	 * @var string
	 */
	public $slug = 'invoice';

	/**
	 * The form in which this was submitted.
	 *
	 * @var string
	 */
	public $referer_url = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'pff_paystack_send_invoice', [ $this, 'send_invoice' ], 10, 7 );
	}

	/**
	 * Sends the invoice before payment with the retry link.
	 *
	 * @param int $form_id
	 * @param string $currency
	 * @param int $amount
	 * @param string $name
	 * @param string $email
	 * @param string $code
	 * @return void
	 */
	public function send_invoice( $form_id, $currency, $amount, $name, $email, $code, $referer_url ) {
		$this->slug        = 'invoice';
		$this->form_id     = $form_id;
		$this->amount      = $amount;
		$this->currency    = $currency;
		$this->code        = $code;
		$this->name        = $name;
		$this->email       = stripslashes( $email );
		$this->referer_url = $referer_url;

		$this->subject = sprintf(
			// Translators: %1$s is the currency code, %2$s is the formatted amount
			esc_html__( 'Payment Invoice for %1$s %2$s', 'text-domain' ),
			$currency,
			number_format( $amount )
		);
		$this->reply_to   = get_option( 'admin_email' );
		$this->reply_name = get_option( 'blogname' );
		$this->send();
	}

	public function get_html_body() {
		?>
		<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin:0;padding:0;min-width:100%;background-color:#f2f2f2">
			<div class="email_body" style="padding:32px 10px;text-align:center;background-color:#f2f2f2">
				<div class="email_container" style="display:inline-block;width:100%;vertical-align:top;text-align:center;margin:0 auto;max-width:580px;font-size:0!important">
					<table class="header" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
						<tbody>
							<tr>
								<td class="header_cell col-bottom-0" align="center" valign="top" style="padding:0;text-align:center;padding-bottom:16px;-webkit-border-radius:4px 4px 0 0;border-radius:4px 4px 0 0;background-color:#fff;font-size:0!important">
									<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
										<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px">
											<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
												<tbody>
													<tr>
														<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:20px;text-align:left;vertical-align:top;color:#ffb26b;font-weight:bold;padding-bottom:0;padding-top:16px">
														</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<table class="content" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
						<tbody>
							<tr>
								<td class="content_cell" align="center" valign="top" style="padding:0;text-align:center;background-color:#fff;font-size:0!important">
									<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
										<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px">
											<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
												<tbody>
													<tr>
														<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:center;vertical-align:top;color:#888">
															<p style="font-family:Helvetica,Arial,sans-serif;font-size:28px;line-height:23px;margin-top:16px;margin-bottom:24px"><small class="text-muted" style="font-size:86%;font-weight:normal;color:#b3b3b5">
																	<a href="#" style="display:inline-block;text-decoration:none;font-family:Helvetica,Arial,sans-serif;color:#ffb26b"><strong class="text-muted" style="color:#b3b3b5"><?php echo esc_html__( 'Invoice', 'pff-paystack' ); ?> #<?php echo esc_html( $this->code ); ?></strong></a></p>
														</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<table class="jumbotron" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
						<tbody>
							<tr>
								<td class="jumbotron_cell product_row" align="center" valign="top" style="padding:0 0 16px;text-align:center;background-color:#fff;border-top:1px solid;border-color:#f2f2f2;font-size:0!important">
									<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
										<div class="col-13" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:390px">
											<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
												<tbody>
													<tr>
														<td class="column_cell font_default" align="center" valign="top" style="padding:16px 16px 0;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:left;vertical-align:top;color:#888">
															<small class="text-muted" style="font-size:86%;font-weight:normal;color:#b3b3b5"><?php echo esc_html( gmdate('F j,Y') ); ?></small>
															<h6 style="font-family:Helvetica,Arial,sans-serif;margin-left:0;margin-right:0;margin-top:0;margin-bottom:8px;padding:0;font-size:16px;line-height:24px;font-weight:bold;color:#666"><?php echo esc_html( $this->name ); ?></h6>
															<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:8px;margin-bottom:8px"><?php echo esc_html( $this->email ); ?></p>
														</td>
													</tr>
												</tbody>
											</table>
										</div>
										<div class="col-1" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:190px">
											<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
												<tbody>
													<tr>
														<td class="column_cell font_default" align="left" valign="top" style="padding:16px 16px 0;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:center;vertical-align:top;color:#888">
															<h1 style="font-family:Helvetica,Arial,sans-serif;margin-left:0;margin-right:0;margin-top:16px;margin-bottom:8px;padding:0;font-size:26px;line-height:36px;font-weight:bold;color:#ffb26b"><?php echo esc_html( $this->currency ) . ' ' . number_format( $this->amount ); ?></h1>
														</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<table class="content" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
						<tbody>
							<tr>
								<td class="content_cell" align="center" valign="top" style="padding:0;text-align:center;background-color:#fff;font-size:0!important">
									<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
										<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px">
											<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
												<tbody>
													<tr>
														<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:center;vertical-align:top;color:#888">
															<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px">
																<?php 
																	printf( 
																		/* translators: %s: Blog name */
																		esc_html__( "You're getting this email because you tried making a payment to %s.", 'pff-paystack' ), 
																		esc_html( get_option( 'blogname' ) )
																	); 
																?>
															</p>
															<table class="primary_btn" align="center" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;clear:both;margin:0 auto">
																<tbody>
																	<tr>
																		<td class="font_default" style="padding:12px 24px;font-family:Helvetica,Arial,sans-serif;font-size:16px;mso-line-height-rule:exactly;text-align:center;vertical-align:middle;-webkit-border-radius:4px;border-radius:4px;background-color:#666">
																			<a target="_blank" href="<?php echo esc_url( $this->referer_url . '?code=' . $this->code ); ?>" style="display:block;text-decoration:none;font-family:Helvetica,Arial,sans-serif;color:#fff;font-weight:bold;text-align:center">
																				<span style="text-decoration:none;color:#fff;text-align:center;display:block">
																					<?php echo esc_html__( 'Try Again', 'pff-paystack' ); ?>
																				</span>
																			</a>
																		</td>
																	</tr>
																</tbody>
															</table>
															<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px">
																<small class="text-muted" style="font-size:86%;font-weight:normal;color:#b3b3b5">
																	<?php echo esc_html__( 'Use this link below to try again if you encountered any issue while trying to make the payment.', 'pff-paystack' ); ?>
																</small>
															</p>
														</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<table class="footer" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
						<tbody>
							<tr>
								<td class="footer_cell" align="center" valign="top" style="padding:0;text-align:center;padding-bottom:16px;-webkit-border-radius:0 0 4px 4px;border-radius:0 0 4px 4px;background-color:#fff;border-top:1px solid;border-color:#f2f2f2;font-size:0!important">
									<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
										<div class="col-13 col-bottom-0" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:390px">
											<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
												<tbody>
													<tr>
														<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:left;vertical-align:top;color:#b3b3b5;padding-bottom:0;padding-top:16px">
															<strong><?php echo esc_html( get_option('blogname') ); ?></strong><br>
														</td>
													</tr>
												</tbody>
											</table>
										</div>
										<div class="col-1 col-bottom-0" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:190px">
											<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
												<tbody>
													<tr>
														<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:left;vertical-align:top;color:#b3b3b5;padding-bottom:0;padding-top:16px">
														</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</body>
		<?php	
	}
}