<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://florianlenz.com
 * @since             1.0.0
 * @package           Bcc
 *
 * @wordpress-plugin
 * Plugin Name:       Basecamp Connector
 * Plugin URI:        https://platzprojekt.de
 * Description:       Adds a form via shortode, connects to your basecamp instance and posts contents
 * Version:           1.0.0
 * Author:            Florian Lenz
 * Author URI:        https://florianlenz.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bcc
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'BCC_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-bcc-activator.php
 */
function activate_bcc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bcc-activator.php';
	Bcc_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-bcc-deactivator.php
 */
function deactivate_bcc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bcc-deactivator.php';
	Bcc_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_bcc' );
register_deactivation_hook( __FILE__, 'deactivate_bcc' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-bcc.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_bcc() {

	$plugin = new Bcc();
	$plugin->run();

}
run_bcc();
