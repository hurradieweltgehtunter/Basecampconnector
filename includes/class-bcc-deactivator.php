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
		// Intentionally a no-op. Plugin tables (bcc_options, bcc_projects)
		// hold OAuth tokens and the last-synced-member pointer; we do not
		// want a deactivation to lose them. Permanent removal happens in
		// uninstall.php.
	}
}
