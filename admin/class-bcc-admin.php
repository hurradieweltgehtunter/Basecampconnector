<?php
session_start();
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://florianlenz.com
 * @since      1.0.0
 *
 * @package    Bcc
 * @subpackage Bcc/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Bcc
 * @subpackage Bcc/admin
 * @author     Florian Lenz <hi@florianlenz.com>
 */
class Bcc_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The URL of the admin page of this plugin used to redirect back from basecamp oAuth process
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $adminUrl    The URL of the admin page of this plugin used to redirect back from basecamp oAuth process
	 */
	private $adminUrl = '';

	/**
	 * Gets set to true (bool) if the auth process was successful
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool    $authenticated	gets set to true (bool) if the auth process was successful
	 */
	private $authenticated = null;

	/**
	 * If the auth process fails this contains an error message to display
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $error	If the auth process fails this contains an error message to display
	 */
	private $error;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->adminUrl = str_replace(' ', '+', admin_url( "admin.php?page=" . $plugin_name));

		add_action('admin_menu', array($this, 'setupSettingsMenu'), 10);
		add_action('admin_init', array($this, 'registerOptions') );

		if (isset($_GET['code'])) {
			$this->finalizeAuthentication($_GET['code']);
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Bcc_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Bcc_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bcc-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css', array(), '5.0.0', 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Bcc_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Bcc_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bcc-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'params', array(
			'ajaxurl' 	=> admin_url( 'admin-ajax.php' ),
			'nonce'    	=> wp_create_nonce( 'plugin' ),
			'auth_url'	=> 'https://launchpad.37signals.com/authorization/new?type=web_server&client_id=' . get_option('bcc_b3_client_id') . '&redirect_uri=' . urlencode($this->adminUrl) //redirect_uri should point to same page where auth was initiated (handle in JS: window.location.href)
			)
		);
	}

	public function setupSettingsMenu() {
		add_menu_page(
			$this->plugin_name,
			$this->plugin_name,
			'manage_options',
			$this->plugin_name,
			array($this, 'create_settings_page'),
			plugin_dir_url( __DIR__ ) . 'admin/img/icon-basecamp.jpg'
		);
	}

	public function registerOptions () {
		register_setting( 'bcc_options', 'bcc_b3_user_agent' );
		register_setting( 'bcc_options', 'bcc_b3_account_id' );
		register_setting( 'bcc_options', 'bcc_b3_project_id' );
		register_setting( 'bcc_options', 'bcc_b3_messageboard_id' );
		register_setting( 'bcc_options', 'bcc_b3_campfire_id' );
		register_setting( 'bcc_options', 'bcc_b3_campfire_message' );
		register_setting( 'bcc_options', 'bcc_b3_message_category_id' );
		register_setting( 'bcc_options', 'bcc_b3_todolistset_id' );
		register_setting( 'bcc_options', 'bcc_b3_client_id' );
		register_setting( 'bcc_options', 'bcc_b3_client_secret' );

		register_setting( 'bcc_options', 'bcc_gcaptcha_sitekey' );
		register_setting( 'bcc_options', 'bcc_gcaptcha_secret' );

		// Strawpolls 
		register_setting( 'bcc_options', 'bcc_sp_api_key' );
		register_setting( 'bcc_options', 'bcc_sp_duration' );

		// EasyVerein 
		register_setting( 'bcc_options', 'bcc_ev_api_url' );
		register_setting( 'bcc_options', 'bcc_ev_api_key' );
		register_setting( 'bcc_options', 'bcc_ev_project_id' );
		register_setting( 'bcc_options', 'bcc_ev_welcome_text' );
		register_setting( 'bcc_options', 'bcc_ev_welcome_text_message_id' );
		register_setting( 'bcc_options', 'bcc_ev_project_id_additional' );
	}

	public function create_settings_page() {
		global $wpdb;
		
		ob_start();
		include plugin_dir_path( __FILE__ ) . '/partials/bcc-admin-settingspage.php';
		$output = ob_get_clean();
		echo $output;
	}

	public function finalizeAuthentication($code) {
		global $wpdb;

		try {
		    $client = new GuzzleHttp\Client();

		    $guzzleResult = $client->post('https://launchpad.37signals.com/authorization/token?type=web_server&client_id=' . get_option('bcc_b3_client_id') . '&redirect_uri=' . urlencode($this->adminUrl) . '&client_secret=' . get_option('bcc_b3_client_secret') . '&code=' . $code, [
		        'headers' => [
		            'User-Agent'        => get_option('bcc_b3_user_agent'),
		            'Content-Type'      => 'application/json; charset=utf-8',
		            'Accept'            => 'application/json',
		            'Accept-Encoding'   => 'gzip, deflate, br'
		        ]
		    ]);

		} catch (\GuzzleHttp\Exception\RequestException $e) {
		    $guzzleResult = $e->getResponse();	
		}

		if ($guzzleResult->getStatusCode() === 200 ) {

			$result = json_decode($guzzleResult->getBody()->getContents(), true);
			$expiry = time() + (int) $result['expires_in'] - 10;

			$wpdb->query( $wpdb->prepare( "UPDATE `" . $wpdb->prefix . "bcc_options` SET `value` = %s WHERE `" . $wpdb->prefix . "bcc_options`.`identifier` = 'access_token';", array($result['access_token'])));
			$wpdb->query( $wpdb->prepare( "UPDATE `" . $wpdb->prefix . "bcc_options` SET `value` = %s WHERE `" . $wpdb->prefix . "bcc_options`.`identifier` = 'refresh_token';", array($result['refresh_token'])));
			$wpdb->query( $wpdb->prepare( "UPDATE `" . $wpdb->prefix . "bcc_options` SET `value` = %s WHERE `" . $wpdb->prefix . "bcc_options`.`identifier` = 'access_token_expires';", array($expiry)));

			// Cut off the auth query params
			$_SESSION['authenticated'] = true;
			header('Location: ' . $this->adminUrl);
			exit();
		} else {
			$this->error = $guzzleResult->getStatusCode() . ': ' . $guzzleResult->getReasonPhrase() . ' | ' . print_r($guzzleResult->getBody()->getContents(), true);
			$_SESSION['authenticated'] = false;
			header('Location: ' . $this->adminUrl);
		}
	}
}
