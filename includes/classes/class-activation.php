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
 * Plugin Activation class.
 */
class Activation {

	/**
	 * Install Paystack DB Table
	 */
	public static function install() {
        global $wpdb;
        $table_name = $wpdb->prefix . PFF_PAYSTACK_TABLE;
        $table_name = sanitize_text_field( $table_name );

		// Include the DB Functions.
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';

		Activation::create_tables( $table_name );
        Activation::maybe_upgrade( $table_name );
		update_option( 'kkd_db_version', '2.0' );
    }

	/**
	 * Install Paystack DB Table
	 */
	public static function create_tables( $table_name ) {
		global $wpdb;
        $query = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
				id int(11) NOT NULL AUTO_INCREMENT,
				post_id int(11) NOT NULL,
				user_id int(11) NOT NULL,
				email varchar(255) DEFAULT '' NOT NULL,
				metadata text,
				paid int(1) NOT NULL DEFAULT '0',
				plan varchar(255) DEFAULT '' NOT NULL,
				txn_code varchar(255) DEFAULT '' NOT NULL,
				txn_code_2 varchar(255) DEFAULT '' NOT NULL,
				amount varchar(255) DEFAULT '' NOT NULL,
				ip varchar(255) NOT NULL, 
				deleted_at varchar(255) DEFAULT '' NULL,
				created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				paid_at timestamp,
				modified timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
				UNIQUE KEY id (id),PRIMARY KEY  (id)
			) {$wpdb->get_charset_collate()};";
		dbDelta( $query );
	}

	/**
	 * Install Paystack DB Table
	 * 
	 * This function supresses the following WPCS warnings because we dont want caching involved.
	 */
	public static function maybe_upgrade( $table_name ) {
		global $wpdb;

		$table_name = esc_sql( $table_name );

		// Get the current version number, defaults to 1.0
		$version = get_option( 'kkd_db_version', '1.0' );

        if ( version_compare( $version, '2.0' ) < 0 ) {
			// Check if the plan column is there?
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$row = $wpdb->get_results(
				$wpdb->prepare( 
					"SELECT COLUMN_NAME 
					FROM INFORMATION_SCHEMA.COLUMNS 
					WHERE table_name = %s
					AND column_name = 'plan'",
					$table_name
				)
			);
			// Add in the plan column if not.
			if ( empty( $row ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery
						"ALTER TABLE `%s` ADD `plan` VARCHAR(255) NOT NULL AFTER `paid`;",
						$table_name
					)
				);
			}
	
			// Add in the txn_code_2 column.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$row1 = $wpdb->get_results(
				$wpdb->prepare( 
					"SELECT COLUMN_NAME 
					FROM INFORMATION_SCHEMA.COLUMNS
					WHERE table_name = %s
					AND column_name = 'txn_code_2'",
					$table_name
				)
			);
			if ( empty( $row1 ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery
						"ALTER TABLE `%s` ADD `txn_code_2` VARCHAR(255) DEFAULT '' NULL AFTER `txn_code`;",
						$table_name
					)
				);
			}
	
			// Add in the paid_at column.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$row2 = $wpdb->get_results(
				$wpdb->prepare( 
					"SELECT COLUMN_NAME 
					FROM INFORMATION_SCHEMA.COLUMNS
					WHERE table_name = %s
					AND column_name = 'paid_at'",
					$table_name
				)
			);
			if ( empty( $row2 ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery
						"ALTER TABLE `%s` ADD `paid_at` timestamp  AFTER `created_at`;",
						$table_name
					)
				);
			}
       }
	}
}
