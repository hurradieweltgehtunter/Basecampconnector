<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://florianlenz.com
 * @since      1.0.0
 *
 * @package    Bcc
 * @subpackage Bcc/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Bcc
 * @subpackage Bcc/public
 * @author     Florian Lenz <hi@florianlenz.com>
 */
class Bcc_Public {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		global $post;
		$postType = $post->post_type;
		if($postType == 'post' || $postType == 'page' && has_shortcode($post->post_content, 'BasecampForm'))
		{
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bcc-public.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css', array(), '5.0.0', 'all' );
		}

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		global $post;
		$postType = $post->post_type;
        if ($postType == 'post' || $postType == 'page' && has_shortcode($post->post_content, 'BasecampForm')) {
			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/bcc-public.js', array( 'jquery' ), $this->version, false);
			
			wp_localize_script( $this->plugin_name, 'params', array(
				'ajaxurl' 	=> admin_url( 'admin-ajax.php' ),
				'nonce'    	=> wp_create_nonce( 'plugin' )
				)
			);

			wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js', array( 'jquery' ), '5.0.0', false);
			wp_enqueue_script('GoogleCaptcha', 'https://www.google.com/recaptcha/api.js?render=6LeQGyYaAAAAAINGjzIYW3mMczOjXK33rvRV3vdo', array( 'jquery' ), '3', false);
        }
	}

	public function BasecampFormFunc() {
		ob_start();
		include plugin_dir_path( __FILE__ ) . '/partials/bcc-public-display.php';
		$output = ob_get_clean();
		return $output;
	}

	/* WEBHOOKS */
	public function rest_api_init() {
		// Webhook for strawpolls.com
		// Route is /wp-json/bcc/v1/webhook/
		// See here: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/

		register_rest_route( 'bcc/v1', '/webhook/', array(
			'methods' => 'POST',
			'callback' => array( $this, 'webhook' ),
		) );
	}

	/**
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function webhook(WP_REST_Request $request) {
		global $wpdb;
	
		$event = $request->get_param('event');
		$data = $request->get_param('data')['poll'];
		
		if ($event === 'deadline_poll') {
			$poll_id = $data['id'];

			$bcMessageId = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT bc_message_id FROM `" . $wpdb->prefix . "bcc_projects` WHERE poll_content_id = %s",
            		$poll_id
				)
			);

			// Post voting results on basecamp
            $client = new BClient();

			$totalCount = 0;

			$options = [];
			foreach($data['poll_options'] as $option) {
				$options[$option['value']] = $option['vote_count'];
				$totalCount = $totalCount + $option['vote_count'];
			}

			if ($options['Ja'] > $options['Nein']) {
				$win = true;
			} else {
				$win = false;
			}

			ob_start();
            include plugin_dir_path(__FILE__) . 'partials/bcc-basecamp-template-comment-votingended.php';
            $comment = ob_get_clean();

			$client->comments()->create(get_option('bcc_b3_project_id'), $bcMessageId, array(
				'content' => $comment
			));

			// Complete the initial todo
			$bcTodoId = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT bc_todo_id FROM `" . $wpdb->prefix . "bcc_projects` WHERE poll_content_id = %s",
            		$poll_id
				)
			);

			$client->todos()->complete(get_option('bcc_b3_project_id'), $bcTodoId);

			// Delete Poll
			$client = new \GuzzleHttp\Client();
			$response = $client->delete('https://api.strawpoll.com/v2/polls/' . $poll_id, [
				'headers' => [
					'X-API-KEY' => get_option('bcc_sp_api_key'),
				]
			]);
			
			// Remove from DB
			$wpdb->delete( $wpdb->prefix . 'bcc_projects', array( 'poll_content_id' => $poll_id ) );
		}
	}

	/**
	 *
	 * Syncs newly added club members from EasyVerein to Basecamp. As sson as a member is marked as valid mamber in EasyVerein
	 * he/she gets an account on Basecamp and gets access to a default thread (see option bcc_ev_api_key)
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function easy_verein_basecamp_sync() {
		global $wpdb;
		$log = '';

		if (get_option('bcc_ev_api_key') === '' || get_option('bcc_ev_api_key') === false || 
			get_option('bcc_ev_api_url') === '' || get_option('bcc_ev_api_url') === false ||
			get_option('bcc_ev_project_id') === '' || get_option('bcc_ev_project_id') === false
		) {
			$missingParams = [];
			if (get_option('bcc_ev_api_key') === '' || get_option('bcc_ev_api_key') === false) {
				$missingParams[] = 'bcc_ev_api_key';
			}

			if (get_option('bcc_ev_api_url') === '' || get_option('bcc_ev_api_url') === false) {
				$missingParams[] = 'bcc_ev_api_url';
			}

			if (get_option('bcc_ev_project_id') === '' || get_option('bcc_ev_project_id') === false) {
				$missingParams[] = 'bcc_ev_project_id';
			}
			wp_mail( get_option( 'admin_email' ), 'EasyVerein Sync Error', 'One or more of the required parameters is not set: ' . implode(', ', $missingParams) . ' | Check BasecampConnector Settings.');
			exit();
		}

		$client = new \GuzzleHttp\Client();

		// Get all members ordered by joinDate DESC
		try {
			$response = $client->request('GET', get_option('bcc_ev_api_url') . 'member/?ordering=-joinDate&limit=25', [
				'headers' => [
					'Authorization' => 'Token ' . get_option('bcc_ev_api_key')
				]
			]);
		} catch (GuzzleHttp\Exception\ClientException $e) {
			$response = $e->getResponse();
			$responseBodyAsString = $response->getBody()->getContents();
			wp_mail( get_option( 'admin_email' ), 'EasyVerein Sync Error', 'Error while retrieving member list from EasyVerein' . "\r\n" . print_r($responseBodyAsString, true));
		} catch (\Exception $e) {
			wp_mail( get_option( 'admin_email' ), 'EasyVerein Sync Error', 'Error while retrieving member list from EasyVerein' . "\r\n" . print_r($e, true));
			exit();
		}
		$list = json_decode($response->getBody()->getContents());
		$members = $list->results;
		// Get data of last synced member
		$result = $wpdb->get_var(
			'SELECT value FROM `' . $wpdb->prefix . 'bcc_options` WHERE identifier = "ev_bc_sync_last_new"'
		);

		if ($result === NULL || $result === '') {
			wp_mail( get_option( 'admin_email' ), 'EasyVerein Sync Error', 'Option "ev_bc_sync_last_new" not set');
			exit();
		}

		// 0 => email address, 1 => member ID (member ID is not reliable, can be null); added for possibe future use
		$lastSyncedMember = explode('|', $result);

		$bclient = new BClient();

		$additionalProjects = explode(',', get_option('bcc_ev_project_id_additional'));
		$message = get_option('bcc_ev_welcome_text');

		// Iterate over new members ...
		foreach($members as $member) {
			// ... until we reached the last synced member
			if ($member->email === $lastSyncedMember[1]) {
				$log .= "\r\n" . 'Current member ' . $member->email . ' is already synced: ' . $lastSyncedMember[1];
				break;
			}

			if (!isset($member->membershipNumber) || is_null($member->membershipNumber) || $member->membershipNumber === '') {
				//Only accepted/paid members have a membershipNumber; Others are prospects and shoud not get access to basecamp
				continue;
			}

			// Get the members details
			try {
				$response = $client->request('GET', $member->contactDetails, [
					'headers' => [
						'Authorization' => 'Token ' . get_option('bcc_ev_api_key')
					]
				]);
			} catch (\Exception $e) {
				wp_mail( get_option( 'admin_email' ), 'EasyVerein Sync Error', 'Error while retrieving member details from EasyVerein' . "\r\n" . print_r($e, true));
				exit();
			}

			$details = json_decode($response->getBody()->getContents());
			
			// Create the basecamp account
			$data = $bclient->people()->create([
				'email' => $member->email,
				'name' => $details->name,
				'company' => $details->companyName,
				'title' => ''
			]);

			$log .= "\r\n" . 'Granted ' . $member->email . ' to project ' . get_option('bcc_ev_project_id') . '; Result: ' . print_r($data, true);

			if (!property_exists($data, 'granted')) {
				wp_mail( get_option( 'admin_email' ), 'EasyVerein Sync Error', 'Could not create Basecamp account for ' . $member->email . "\r\n" . print_r($data, true));
				exit();
			}

			// Add user to additional projects
			foreach($additionalProjects as $projectId) {
				$result = $bclient->people()->addToProject(trim($projectId), $data->granted[0]->id);
				
				if (!property_exists($result, 'granted')) {
					wp_mail( get_option( 'admin_email' ), 'EasyVerein Sync Error', 'Could not add user ' . $member->email . ' to project ' . $projectId . "\r\n" . print_r($data, true));
					exit();
				}
			}			

			// Create the welcome message
			if (count($data->granted) > 0) {
				$userLink = '<bc-attachment sgid="' . $data->granted[0]->attachable_sgid . '"></bc-attachment>';
				$message = str_replace('{user}', $userLink, $message);
				$result = $bclient->comments()->create(get_option('bcc_ev_project_id'), get_option('bcc_ev_welcome_text_message_id'), array(
					'content' => $message
				));

				$log .= "\r\n" . 'Posted welcome message: ' . $message . '; Result: ' . print_r($result, true);
			}

			// Store the newest synced member
			$result = $wpdb->update(
				"{$wpdb->base_prefix}bcc_options", 
				array(
					'value' => $member->membershipNumber . '|' . $member->email
				), 
				array(
					'identifier' => 'ev_bc_sync_last_new'
				),
				array('%s'),
				array('%s')
			);

			$log .= "\r\n" . 'Added ' . $member->email;
		}

		wp_mail( get_option( 'admin_email' ), 'EasyVerein Sync Log', $log);
	}
}
