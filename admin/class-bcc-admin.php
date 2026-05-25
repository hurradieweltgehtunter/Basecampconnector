<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://florianlenz.com
 * @since      1.0.0
 *
 * @package    Bcc
 * @subpackage Bcc/admin
 */

class Bcc_Admin {

	private const FLASH_TRANSIENT   = 'bcc_oauth_flash';
	private const OAUTH_STATE_TRANS = 'bcc_oauth_state';
	private const OAUTH_STATE_TTL   = 600;

	/** @var string */
	private $plugin_name;

	/** @var string */
	private $version;

	/** @var string */
	private $adminUrl = '';

	/** @var ?array{ok:bool,message:string} */
	public $oauth_flash = null;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		// admin_url already URL-encodes the query string; the historical
		// `str_replace(' ', '+', ...)` was a workaround for plugin slugs
		// containing spaces, which we now do properly via add_query_arg.
		$this->adminUrl = add_query_arg( 'page', rawurlencode( $plugin_name ), admin_url( 'admin.php' ) );

		add_action( 'admin_menu', array( $this, 'setupSettingsMenu' ), 10 );
		add_action( 'admin_init', array( $this, 'registerOptions' ) );
		add_action( 'admin_init', array( $this, 'maybeFinalizeAuthentication' ) );
		add_action( 'admin_init', array( $this, 'maybeIssueOauthState' ) );

		$this->oauth_flash = $this->consumeOauthFlash();
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bcc-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css', array(), '5.0.0', 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bcc-admin.js', array( 'jquery' ), $this->version, false );

		$state = $this->getOrCreateOauthState();

		$authUrl = add_query_arg(
			array(
				'type'         => 'web_server',
				'client_id'    => (string) get_option( 'bcc_b3_client_id' ),
				'redirect_uri' => $this->adminUrl,
				'state'        => $state,
			),
			'https://launchpad.37signals.com/authorization/new'
		);

		wp_localize_script(
			$this->plugin_name,
			'params',
			array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'plugin' ),
				'auth_url' => $authUrl,
			)
		);
	}

	/**
	 * Handle the manual sync action — fired from the settings page button.
	 * Hook: admin_post_manual_sync
	 */
	public function handle_manual_sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.', 'Forbidden', array( 'response' => 403 ) );
		}
		check_admin_referer( 'bcc_manual_sync' );

		$status = 'success';
		$detail = '';
		try {
			do_action( 'easy_verein_basecamp_sync' );
		} catch ( \Throwable $e ) {
			$status = 'error';
			$detail = $e->getMessage();
		}

		$args = array(
			'page' => 'Basecamp+Connector',
			'sync' => $status,
		);
		if ( $detail !== '' ) {
			$args['detail'] = rawurlencode( substr( $detail, 0, 300 ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?' . http_build_query( $args ) ) );
		exit;
	}

	public function setupSettingsMenu(): void {
		add_menu_page(
			$this->plugin_name,
			$this->plugin_name,
			'manage_options',
			$this->plugin_name,
			array( $this, 'create_settings_page' ),
			plugin_dir_url( __DIR__ ) . 'admin/img/icon-basecamp.jpg'
		);
	}

	public function registerOptions(): void {
		// Most options are single-line text. The welcome message and the
		// campfire one-liner can contain user-authored prose with newlines
		// (or HTML-ish snippets like {user} placeholders), so they get
		// sanitize_textarea_field, which preserves line breaks.
		$textareaFields = array( 'bcc_ev_welcome_text', 'bcc_b3_campfire_message' );

		$options = array(
			'bcc_b3_user_agent',
			'bcc_b3_account_id',
			'bcc_b3_project_id',
			'bcc_b3_messageboard_id',
			'bcc_b3_campfire_id',
			'bcc_b3_campfire_message',
			'bcc_b3_message_category_id',
			'bcc_b3_todolistset_id',
			'bcc_b3_client_id',
			'bcc_b3_client_secret',
			'bcc_gcaptcha_sitekey',
			'bcc_gcaptcha_secret',
			'bcc_sp_api_key',
			'bcc_sp_duration',
			'bcc_ev_api_url',
			'bcc_ev_api_key',
			'bcc_ev_project_id',
			'bcc_ev_welcome_text',
			'bcc_ev_welcome_text_message_id',
			'bcc_ev_project_id_additional',
			'bcc_admin_email',
		);
		foreach ( $options as $opt ) {
			$cb = in_array( $opt, $textareaFields, true ) ? 'sanitize_textarea_field' : 'sanitize_text_field';
			register_setting( 'bcc_options', $opt, array( 'sanitize_callback' => $cb ) );
		}
	}

	public function create_settings_page(): void {
		global $wpdb;
		include plugin_dir_path( __FILE__ ) . '/partials/bcc-admin-settingspage.php';
	}

	/**
	 * Triggered on admin_init: if Basecamp bounced the user back with a
	 * `?code=` query param, exchange it for tokens.
	 */
	public function maybeFinalizeAuthentication(): void {
		// Auth-callback semantics: capability check is the gate here, the
		// state parameter below verifies authenticity of the OAuth round-trip.
		// PHPCS flags the $_GET reads as nonce-less form processing; that does
		// not apply to an OAuth callback flow.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['code'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$pageSlug = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( $pageSlug === '' || strpos( $pageSlug, 'Basecamp' ) === false ) {
			return;
		}

		$log = new Bcc_Logger( 'oauth' );
		$log->log( 'Finalizing Basecamp OAuth callback for user ' . get_current_user_id(), 'info' );

		// Verify the OAuth state parameter to prevent a third party from
		// tricking a logged-in admin into binding the WP instance to an
		// attacker-controlled Basecamp account.
		$returnedState = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		if ( ! $this->verifyOauthState( $returnedState ) ) {
			$log->log( 'OAuth state mismatch — rejecting callback', 'error' );
			$logFile = $log->flush();
			Bcc_Notifier::sendError( 'oauth', 'OAuth state parameter missing or did not match. Possible CSRF attempt.', array( 'log_file' => (string) $logFile ) );
			$this->storeOauthFlash( false, 'Basecamp connection rejected: state parameter mismatch. Try again from the settings page.' );
			wp_safe_redirect( $this->adminUrl );
			exit;
		}

		try {
			$client = new Basecamp3Client();
			$client->exchangeAuthorizationCode( sanitize_text_field( wp_unslash( $_GET['code'] ) ), $this->adminUrl );
			$log->log( 'Exchanged auth code, tokens persisted.' );
			$log->flush();
			$this->storeOauthFlash( true, 'You successfully connected WordPress with Basecamp.' );
		} catch ( \Throwable $e ) {
			$log->log( 'OAuth exchange failed: ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine(), 'error' );
			$logFile = $log->flush();
			Bcc_Notifier::sendError(
                'oauth',
                $e->getMessage(),
                array(
					'exception' => $e,
					'log_file'  => (string) $logFile,
                ) 
            );
			$this->storeOauthFlash( false, 'Basecamp connection failed: ' . $e->getMessage() );
		}

		wp_safe_redirect( $this->adminUrl );
		exit;
	}

	/**
	 * On the settings page, ensure a per-user OAuth state token exists so
	 * the auth_url passed to the JS click handler includes it.
	 */
	public function maybeIssueOauthState(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$pageSlug = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( strpos( $pageSlug, 'Basecamp' ) === false ) {
			return;
		}
		$this->getOrCreateOauthState();
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	private function getOrCreateOauthState(): string {
		$key   = self::OAUTH_STATE_TRANS . '_' . get_current_user_id();
		$state = get_transient( $key );
		if ( is_string( $state ) && strlen( $state ) === 64 ) {
			return $state;
		}
		$state = bin2hex( random_bytes( 32 ) );
		set_transient( $key, $state, self::OAUTH_STATE_TTL );
		return $state;
	}

	private function verifyOauthState( string $candidate ): bool {
		if ( $candidate === '' || strlen( $candidate ) !== 64 ) {
			return false;
		}
		$key      = self::OAUTH_STATE_TRANS . '_' . get_current_user_id();
		$expected = get_transient( $key );
		// One-shot: consume immediately so a replay needs a fresh state.
		delete_transient( $key );
		if ( ! is_string( $expected ) ) {
			return false;
		}
		return hash_equals( $expected, $candidate );
	}

	private function storeOauthFlash( bool $ok, string $message ): void {
		set_transient(
            self::FLASH_TRANSIENT . '_' . get_current_user_id(),
            array(
				'ok'      => $ok,
				'message' => $message,
            ),
            30 
        );
	}

	private function consumeOauthFlash(): ?array {
		$key   = self::FLASH_TRANSIENT . '_' . get_current_user_id();
		$flash = get_transient( $key );
		if ( $flash === false ) {
			return null;
		}
		delete_transient( $key );
		return is_array( $flash ) ? $flash : null;
	}
}
