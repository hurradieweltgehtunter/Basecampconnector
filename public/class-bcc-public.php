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
	 * The log of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $log    The log of this plugin.
	 */
	private $log = [];

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

			throw new \Exception('One or more of the required parameters is not set: ' . implode(', ', $missingParams) . ' | Check BasecampConnector Settings.');
		}
		
		$client = new \GuzzleHttp\Client();

		// Check, if we have an EasyVerein ApiToken
		try {
			$evClient = new EasyVereinClient();
			$EvApiToken = get_option('bcc_ev_api_token');

			if ($EvApiToken === '' || $EvApiToken === NULL) {
				$EvApiToken = $evClient->refreshApiToken();
			}
		} catch (\Exception $e) {
			throw new \Exception('Error while retrieving EasyVerein API Token: ' . $e->getMessage());
		}

		// Get all members ordered by joinDate DESC
		try {
			$this->log('Getting members from EasyVerein');
			$members = $evClient->getMembers('ordering=-joinDate&limit=25');

			$this->log('Got ' . count($members) . ' members from EasyVerein');
		} catch (\Exception $e) {

			// if status is 401, refresh api token
			if ($e->getCode() === 401) {
				$this->log('Received 401 from EasyVerein. Refreshing API Token');

				try {
					$EvApiToken = $evClient->refreshApiToken();
					$members = $evClient->getMembers('ordering=-joinDate&limit=25');
				} catch (\Exception $e) {
					throw new \Exception('Error while retrieving member list from EasyVerein'. $e->getMessage());
				}

			} else {
				throw new \Exception('Error while retrieving member list from EasyVerein'. $e->getMessage());
			}
		}

		// Get data of last synced member
		try {
			$this->log('Getting latest synced member from EasyVerein');
			$latestSyncedMember = $evClient->getLatestSyncedMember();
			$this->log('Got latest synced member from EasyVerein: ' . print_r($latestSyncedMember, true));
		} catch (\Exception $e) {
			throw new \Exception('Error while retrieving latest synced member from EasyVerein'. $e->getMessage());
		}

		$message = get_option('bcc_ev_welcome_text');
		$this->log('Got welcome message text');

		$bclient = new BClient();

		$additionalProjects = explode(',', get_option('bcc_ev_project_id_additional'));

		if(count($additionalProjects) === 0) {
			$additionalProjects = [];
		}
		
		$this->log('Starting Sync Process');

		try {
			// Iterate over new members ...
			foreach($members as $member) {
				$this->log('Syncing ' . $member->emailOrUserName);

				// ... until we reached the last synced member
				if ($member->emailOrUserName === $latestSyncedMember['emailOrUserName']) {
					$this->log('Member is already synced: ' . $latestSyncedMember['emailOrUserName']);
					break;
				}

				if (!isset($member->membershipNumber) || is_null($member->membershipNumber) || $member->membershipNumber === '') {
					//Only accepted/paid members have a membershipNumber; Others are prospects and shoud not get access to basecamp
					$this->log('Not a full member (No membership number). Skipping.');
					continue;
				}

				// Get the members details
				$this->log('Getting member details from EasyVerein');
				$memberDetails = $evClient->getMemberDetails($member);
				$this->log('Got member details');

				// Create the account and add it to PP general project
				$this->log('Granting ' . $member->emailOrUserName . ' to project ' . get_option('bcc_ev_project_id'));
				$this->log('Email: ' . $memberDetails->primaryEmail . ', Name: ' . $memberDetails->name . ', Company: ' . $memberDetails->companyName);

				$data = $bclient->people()->create([
					'email' => $memberDetails->primaryEmail,
					'name' => $memberDetails->name,
					'company' => '',
					'title' => ''
				], get_option('bcc_ev_project_id'));

				$this->log('Result: ' . print_r($data, true));
				
				if (!property_exists($data, 'granted') || count($data->granted) === 0) {
					$this->log('Could not create Basecamp account for ' . $member->emailOrUserName . ". Possibly already added to project? \r\n <pre>" . print_r($data, true) . '</pre>');
				}

				// Add user to additional projects (Halping hands etc.)
				foreach($additionalProjects as $projectId) {
					$this->log('Adding ' . $member->emailOrUserName . ' to additional project ' . $projectId);
					$result = $bclient->people()->addToProject(trim($projectId), $data->granted[0]->id);				
					
					if (!property_exists($result, 'granted')) {
						$this->log('Could not add user ' . $member->emailOrUserName . ' to additional project ' . $projectId . ". Possibly already added? \r\n <pre>" . print_r($data, true) . '</pre>');
					} else {
						$this->log('Added ' . $member->emailOrUserName . ' to additional project ' . $projectId . "\r\n");
					}
				}			

				// Create the welcome message
				if (count($data->granted) > 0) {
					$userLink = '<bc-attachment sgid="' . $data->granted[0]->attachable_sgid . '"></bc-attachment>';
					$message = str_replace('{user}', $userLink, $message);
					$result = $bclient->comments()->create(get_option('bcc_ev_project_id'), get_option('bcc_ev_welcome_text_message_id'), array(
						'content' => $message
					));

					$this->log('Posted welcome message: ' . $message . '; Result: ' . print_r($result, true));
				}

				// Store the newest synced member
				$latestSyncedMember = $evClient->setLatestSyncedMember([
					'membershipNumber' => $member->membershipNumber,
					'firstName' => $memberDetails->firstName,
					'lastName' => $memberDetails->lastName,
					'privateEmail' => $memberDetails->privateEmail,
					'joinDate' => $member->joinDate,
					'emailOrUserName' => $member->emailOrUserName
				]);

				$this->log('Added ' . $member->emailOrUserName);
			}
		} catch (\Exception $e) {
			$this->log('Error while syncing members: ' . $e->getMessage(), 'error');
			// wp_mail( get_option( 'admin_email' ), 'EasyVerein Sync Error', 'Error while syncing members from EasyVerein to Basecamp: ' . $e->getMessage() . "\r\n" . $log);
			throw new \Exception('Error while syncing members from EasyVerein to Basecamp: ' . $e->getMessage());
		} finally {
			// Store log with timestamp in plugins /log directory
			$logFile = plugin_dir_path( dirname( __FILE__ ) ) . 'log/' . date('Y-m-d H:i:s') . '.log';
			
			$log = '';

			foreach($this->log as $entry) {
				$log .= $entry['level'] . ': ' . $entry['message'] . "\r\n";
			}
			
			// Append log to file
			file_put_contents($logFile, $log, FILE_APPEND);
			echo '<pre>';
			print_r($log);
			echo '</pre>';
			
		}
	}

	/**
	 * Log a message
	 * 
	 * @param string $message
	 */
	private function log($message, $level = 'debug')
	{
		$this->log[] = [
			'message' => $message,
			'timestamp' => date('Y-m-d H:i:s'),
			'level' => $level
		];
	}
}
