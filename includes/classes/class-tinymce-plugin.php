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
class TinyMCE_Plugin {

	/**
	 * Returns true if this is the paystack screen.
	 *
	 * @var boolean
	 */
	public $is_screen = false;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_tinymce_plugin' ) );
	}

	/**
	 * Check if the current user can edit Posts or Pages, and is using the Visual Editor
	 * If so, add some filters so we can register our plugin
	 */
	function setup_tinymce_plugin() {
		// Check if the logged in WordPress User can edit Posts or Pages
		// If not, don't register our TinyMCE plugin
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		// Check if the logged in WordPress User has the Visual Editor enabled
		// If not, don't register our TinyMCE plugin
		if ( get_user_option( 'rich_editing' ) !== 'true' ) {
			return;
		}

		// Setup some filters
		add_filter( 'mce_external_plugins', array( $this, 'add_tinymce_plugin' ) );
		add_filter( 'mce_buttons', array( $this, 'add_tinymce_toolbar_button' ) );
	}

    /**
     * Adds a TinyMCE plugin compatible JS file to the TinyMCE / Visual Editor instance
     *
     * @param  array $plugin_array Array of registered TinyMCE Plugins
     * @return array Modified array of registered TinyMCE Plugins
     */
    function add_tinymce_plugin( $plugin_array ) {
		$current_version = get_bloginfo('version');
		if ( version_compare( '6.2', $current_version, '<=' ) ) {
			$screen = get_current_screen();
			if ( null !== $screen && isset( $screen->post_type ) && 'paystack_form' === $screen->post_type ) {
				$this->is_screen = true;
				$plugin_array['custom_class'] = PFF_PAYSTACK_PLUGIN_URL . 'assets/css/tinymce-plugin.js';
			}
		}
        return $plugin_array;
    }

    /**
     * Adds a button to the TinyMCE / Visual Editor which the user can click
     * to insert a custom CSS class.
     *
     * @param  array $buttons Array of registered TinyMCE Buttons
     * @return array Modified array of registered TinyMCE Buttons
     */
    function add_tinymce_toolbar_button( $buttons ) {
		if ( $this->is_screen ) {
			array_push( $buttons, 'custom_class' );
		}
        return $buttons;
    }
}