<?php
/**
 * The setup plugin class, this will return register the post type and other needed items.
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Settings class.
 */
class Setup {

	/**
	 * Constructor: Registers the custom post type on WordPress 'init' action.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
		add_action( 'plugin_action_links_' . KKD_PFF_PLUGIN_BASENAME, [ $this, 'add_action_links' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_styles' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_action( 'init', [ $this, 'init' ] );
		add_action( 'parse_request', [ $this, 'parse_request' ] );
		add_action( 'query_vars', [ $this, 'query_vars' ] );
	}

    /**
     * Registers the custom post type 'paystack_form'.
     */
    public function register_post_type() {
        $labels = [
            'name'                  => __( 'Paystack Forms', 'paystack_form' ),
            'singular_name'         => __( 'Paystack Form', 'paystack_form' ),
            'add_new'               => __( 'Add New', 'paystack_form' ),
            'add_new_item'          => __( 'Add Paystack Form', 'paystack_form' ),
            'edit_item'             => __( 'Edit Paystack Form', 'paystack_form' ),
            'new_item'              => __( 'Paystack Form', 'paystack_form' ),
            'view_item'             => __( 'View Paystack Form', 'paystack_form' ),
            'all_items'             => __( 'All Forms', 'paystack_form' ),
            'search_items'          => __( 'Search Paystack Forms', 'paystack_form' ),
            'not_found'             => __( 'No Paystack Forms found', 'paystack_form' ),
            'not_found_in_trash'    => __( 'No Paystack Forms found in Trash', 'paystack_form' ),
            'parent_item_colon'     => __( 'Parent Paystack Form:', 'paystack_form' ),
            'menu_name'             => __( 'Paystack Forms', 'paystack_form' ),
		];

        $args = [
            'labels'                => $labels,
            'hierarchical'          => true,
            'description'           => __( 'Paystack Forms filterable by genre', 'paystack_form' ),
            'supports'              => array( 'title', 'editor' ),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
			'show_in_rest'          => false,
            'menu_position'         => 5,
            'menu_icon'             => KKD_PFF_PAYSTACK_PLUGIN_URL . '/assets/images/logo.png',
            'show_in_nav_menus'     => true,
            'publicly_queryable'    => true,
            'exclude_from_search'   => false,
            'has_archive'           => false,
            'query_var'             => true,
            'can_export'            => true,
            'rewrite'               => false,
            'comments'              => false,
            'capability_type'       => 'post',
		];
        register_post_type( 'paystack_form', $args );
    }

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'pff-paystack', false, KKD_PFF_PAYSTACK_PLUGIN_PATH . '/languages/' );
	}

	/**
	 * Add a link to our settings page in the plugin action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = array(
			'<a href="' . admin_url( 'edit.php?post_type=paystack_form&page=settings') . '">' . __( 'Settings', 'pff-paystack' ) . '</a>',
		);
		return array_merge( $settings_link, $links );
	}

	/**
	 * Enqueues our admin css.
	 *
	 * @param string $hook
	 * @return void
	 */
	public function admin_enqueue_styles( $hook ) {
		if ( $hook != 'paystack_form_page_submissions' && $hook != 'paystack_form_page_settings' ) {
			return;
		}
		wp_enqueue_style( KKD_PFF_PLUGIN_NAME,  KKD_PFF_PAYSTACK_PLUGIN_URL . '/assets/css/paystack-admin.css', array(), KKD_PFF_PAYSTACK_VERSION, 'all' );
	}

	/**
	 * Enqueue the Administration scripts.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( KKD_PFF_PLUGIN_NAME, KKD_PFF_PAYSTACK_PLUGIN_URL . '/assets/js/paystack-admin.js', array( 'jquery' ), KKD_PFF_PAYSTACK_VERSION, false );
	}

	/**
	 * Enques our frontend styles
	 *
	 * @return void
	 */
	public function enqueue_styles() {
        wp_enqueue_style( KKD_PFF_PLUGIN_NAME . '-style', KKD_PFF_PAYSTACK_PLUGIN_URL . '/assets/css/pff-paystack.css', array(), KKD_PFF_PAYSTACK_VERSION, 'all' );
        wp_enqueue_style( KKD_PFF_PLUGIN_NAME . '-font-awesome', KKD_PFF_PAYSTACK_PLUGIN_URL . '/assets/css/font-awesome.min.css', array(), KKD_PFF_PAYSTACK_VERSION, 'all' );
    }

	public function enqueue_scripts() {

		$page_content = get_the_content();
		if ( ! has_shortcode( $page_content, 'pff-paystack' ) ) {
			return;
		}

		wp_enqueue_script( 'blockUI', KKD_PFF_PAYSTACK_PLUGIN_URL . '/assets/js/jquery.blockUI.min.js', array( 'jquery', 'jquery-ui-core' ), KKD_PFF_PAYSTACK_VERSION, true, true );

		wp_register_script( 'Paystack', 'https://js.paystack.co/v1/inline.js', false, '1' );
		wp_enqueue_script( 'Paystack' );

		wp_enqueue_script( KKD_PFF_PLUGIN_NAME . '-public', KKD_PFF_PAYSTACK_PLUGIN_URL . '/assets/js/paystack-public.js', array( 'jquery' ), KKD_PFF_PAYSTACK_VERSION, true, true);
		
		$helpers = new Helpers();
		$js_args = [
			'key' => $helpers->get_public_key(),
			'fee' => $helpers->get_fees(),
		];
		wp_localize_script( KKD_PFF_PLUGIN_NAME . '-public', 'pffSettings', $js_args , KKD_PFF_PAYSTACK_VERSION, true, true);
	}


	/**
	 * Register our payment retry rule.
	 *
	 * @return void
	 */
	public function init(){
		add_rewrite_rule( '^paystackinvoice$', 'index.php?pff_paystack_stats=true', 'top' );
	}
	
	/**
	 * Whitelist the our variable.
	 *
	 * @param array $query_vars
	 * @return array
	 */
	public function query_vars( $query_vars ){
		$query_vars[] = 'pff_paystack_stats';
		return $query_vars;
	}
	
	/**
	 * This example checks very early in the process, if the variable is set, we include our page and stop execution after it
	 *
	 * @param object $wp
	 * @return void
	 */
	public function parse_request( $wp ) {
		if ( array_key_exists( 'pff_paystack_stats', $wp->query_vars ) ) {
			include dirname(__FILE__) . '/includes/paystack-invoice.php';
			exit();
		}
	}
}
