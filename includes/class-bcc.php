<?php
/**
 * The file that defines the core plugin class.
 *
 * @package    Bcc
 * @subpackage Bcc/includes
 */

class Bcc {

	/** @var Bcc_Loader */
	protected $loader;

	/** @var string */
	protected $plugin_name;

	/** @var string */
	protected $version;

	public function __construct() {
		$this->version     = defined( 'BCC_VERSION' ) ? BCC_VERSION : '1.0.0';
		$this->plugin_name = 'Basecamp Connector';

		$this->load_dependencies();

		set_exception_handler( array( 'BCC_Exception_Handler', 'handle_exception' ) );

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		$base = plugin_dir_path( __DIR__ );

		require_once $base . 'vendor/autoload.php';

		require_once $base . 'includes/class-bcc-loader.php';
		require_once $base . 'includes/class-bcc-i18n.php';
		require_once $base . 'includes/class-bcc-logger.php';
		require_once $base . 'includes/class-bcc-notifier.php';
		require_once $base . 'includes/class-bcc-exception-handler.php';
		require_once $base . 'includes/class-bcc-mutex.php';
		require_once $base . 'includes/Basecamp3Client.php';
		require_once $base . 'includes/EasyvereinClient.php';

		require_once $base . 'admin/class-bcc-admin.php';
		require_once $base . 'public/class-bcc-public.php';
		require_once $base . 'public/class-bcc-ajax.php';

		$this->loader = new Bcc_Loader();
	}

	private function set_locale() {
		$plugin_i18n = new Bcc_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	private function define_admin_hooks() {
		$plugin_admin = new Bcc_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_post_manual_sync', $plugin_admin, 'handle_manual_sync' );
	}

	private function define_public_hooks() {
		$plugin_public = new Bcc_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_shortcode( 'BasecampForm', $plugin_public, 'BasecampFormFunc' );
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'rest_api_init' );
		$this->loader->add_action( 'easy_verein_basecamp_sync', $plugin_public, 'easy_verein_basecamp_sync' );

		$plugin_ajax = new Plugin_Public_Ajax( $this->plugin_name );
		$this->loader->add_action( 'wp_ajax_nopriv_submit_project', $plugin_ajax, 'submit_project' );
		$this->loader->add_action( 'wp_ajax_submit_project', $plugin_ajax, 'submit_project' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}
}
