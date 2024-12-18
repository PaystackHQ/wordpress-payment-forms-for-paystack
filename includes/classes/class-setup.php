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
		add_action( 'plugin_action_links_' . PFF_PLUGIN_BASENAME, [ $this, 'add_action_links' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_styles' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

    /**
     * Registers the custom post type 'paystack_form'.
     */
    public function register_post_type() {
        $labels = [
            'name'                  => esc_html__( 'Paystack Forms', 'paystack_form' ),
            'singular_name'         => esc_html__( 'Paystack Form', 'paystack_form' ),
            'add_new'               => esc_html__( 'Add New', 'paystack_form' ),
            'add_new_item'          => esc_html__( 'Add Paystack Form', 'paystack_form' ),
            'edit_item'             => esc_html__( 'Edit Paystack Form', 'paystack_form' ),
            'new_item'              => esc_html__( 'Paystack Form', 'paystack_form' ),
            'view_item'             => esc_html__( 'View Paystack Form', 'paystack_form' ),
            'all_items'             => esc_html__( 'All Forms', 'paystack_form' ),
            'search_items'          => esc_html__( 'Search Paystack Forms', 'paystack_form' ),
            'not_found'             => esc_html__( 'No Paystack Forms found', 'paystack_form' ),
            'not_found_in_trash'    => esc_html__( 'No Paystack Forms found in Trash', 'paystack_form' ),
            'parent_item_colon'     => esc_html__( 'Parent Paystack Form:', 'paystack_form' ),
            'menu_name'             => esc_html__( 'Paystack Forms', 'paystack_form' ),
		];

        $args = [
            'labels'                => $labels,
            'hierarchical'          => true,
            'description'           => esc_html__( 'Paystack Forms filterable by genre', 'paystack_form' ),
            'supports'              => array( 'title', 'editor' ),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
			'show_in_rest'          => false,
            'menu_position'         => 5,
            'menu_icon'             => PFF_PAYSTACK_PLUGIN_URL . '/assets/images/logo.png',
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
		load_plugin_textdomain( 'pff-paystack', false, PFF_PAYSTACK_PLUGIN_PATH . '/languages/' );
	}

	/**
	 * Add a link to our settings page in the plugin action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = array(
			'<a href="' . admin_url( 'edit.php?post_type=paystack_form&page=settings') . '">' . esc_html__( 'Settings', 'pff-paystack' ) . '</a>',
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
		wp_enqueue_style( PFF_PLUGIN_NAME,  PFF_PAYSTACK_PLUGIN_URL . '/assets/css/paystack-admin.css', array(), PFF_PAYSTACK_VERSION, 'all' );
	}

	/**
	 * Enqueue the Administration scripts.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( PFF_PLUGIN_NAME, PFF_PAYSTACK_PLUGIN_URL . '/assets/js/paystack-admin.js', array( 'jquery' ), PFF_PAYSTACK_VERSION, false );
	}

	/**
	 * Enques our frontend styles
	 *
	 * @return void
	 */
	public function enqueue_styles() {
        wp_enqueue_style( PFF_PLUGIN_NAME . '-style', PFF_PAYSTACK_PLUGIN_URL . '/assets/css/pff-paystack.css', array(), PFF_PAYSTACK_VERSION, 'all' );
        wp_enqueue_style( PFF_PLUGIN_NAME . '-font-awesome', PFF_PAYSTACK_PLUGIN_URL . '/assets/css/font-awesome.min.css', array(), PFF_PAYSTACK_VERSION, 'all' );
    }

	/**
	 * Enqueue the frontend scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		$page_content = get_the_content();
		if ( ! has_shortcode( $page_content, 'pff-paystack' ) ) {
			return;
		}

		wp_enqueue_script( 'blockUI', PFF_PAYSTACK_PLUGIN_URL . '/assets/js/jquery.blockUI.min.js', array( 'jquery', 'jquery-ui-core' ), PFF_PAYSTACK_VERSION, true );

		wp_register_script( 'Paystack', 'https://js.paystack.co/v1/inline.js', false, PFF_PAYSTACK_VERSION, true );
		wp_enqueue_script( 'Paystack' );

		wp_enqueue_script( PFF_PLUGIN_NAME . '-public', PFF_PAYSTACK_PLUGIN_URL . '/assets/js/paystack-public.js', array( 'jquery' ), PFF_PAYSTACK_VERSION, true );
		
		$helpers = new Helpers();
		$js_args = [
			'key' => $helpers->get_public_key(),
			'fee' => $helpers->get_fees(),
		];
		wp_localize_script( PFF_PLUGIN_NAME . '-public', 'pffSettings', $js_args , PFF_PAYSTACK_VERSION, true );
	}
}
