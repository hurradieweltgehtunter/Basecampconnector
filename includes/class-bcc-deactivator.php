<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://florianlenz.com
 * @since      1.0.0
 *
 * @package    Bcc
 * @subpackage Bcc/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Bcc
 * @subpackage Bcc/includes
 * @author     Florian Lenz <hi@florianlenz.com>
 */
class Bcc_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		global $wpdb;

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// $sql = "DROP TABLE `" . $wpdb->base_prefix . "bcc_options`";
		// $wpdb->query($sql);

		// $sql = "DROP TABLE `" . $wpdb->base_prefix . "bcc_projects`";
		// $wpdb->query($sql);
	}
}
