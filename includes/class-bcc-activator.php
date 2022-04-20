<?php

/**
 * Fired during plugin activation
 *
 * @link       https://florianlenz.com
 * @since      1.0.0
 *
 * @package    Bcc
 * @subpackage Bcc/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Bcc
 * @subpackage Bcc/includes
 * @author     Florian Lenz <hi@florianlenz.com>
 */
class Bcc_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		if ($wpdb->get_var('SELECT count(*) FROM information_schema.TABLES WHERE (TABLE_SCHEMA = "' . $wpdb->dbname . '") AND (TABLE_NAME = "' . $wpdb->base_prefix . 'bcc_options")') <= 0) {
			// Table not installed, install it
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE `{$wpdb->base_prefix}bcc_options` (
			  identifier varchar(100) NOT NULL,
			  `value` varchar(1000) NOT NULL,
			  PRIMARY KEY  (identifier)
			) $charset_collate;";
	  
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
				
			$success = empty($wpdb->last_error);
			if ($success) {
				$wpdb->insert("{$wpdb->base_prefix}bcc_options", array("identifier" => 'access_token',"value" => ''),array("%s", "%s"));
				$wpdb->insert("{$wpdb->base_prefix}bcc_options", array("identifier" => 'refresh_token',"value" => ''),array("%s", "%s"));
				$wpdb->insert("{$wpdb->base_prefix}bcc_options", array("identifier" => 'access_token_expires',"value" => ''),array("%s", "%s"));
				$wpdb->insert("{$wpdb->base_prefix}bcc_options", array("identifier" => 'ev_bc_sync_last_new',"value" => ''),array("%s", "%s"));
				$wpdb->insert("{$wpdb->base_prefix}bcc_options", array("identifier" => 'ev_bc_sync_last_deleted',"value" => ''),array("%s", "%s"));
			}
		} else {
			// Table already installed
		}

		if ($wpdb->get_var('SELECT count(*) FROM information_schema.TABLES WHERE (TABLE_SCHEMA = "' . $wpdb->dbname . '") AND (TABLE_NAME = "' . $wpdb->base_prefix . 'bcc_projects")') <= 0) {
			// Table not installed, install it
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE `{$wpdb->base_prefix}bcc_projects` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`bc_message_id` VARCHAR(25) NOT NULL,
				`bc_todo_id` VARCHAR(25) NOT NULL,
				`poll_content_id` varchar(50) NOT NULL,
				PRIMARY KEY (id)
			  ) $charset_collate";
	  
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		} else {
			// Table already installed
		}
	}
}