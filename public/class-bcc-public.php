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

class Bcc_Public {

	/** @var string */
	private $plugin_name;

	/** @var string */
	private $version;

	/** @var Bcc_Logger */
	private $logger;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->logger      = new Bcc_Logger( 'sync' );
	}

	public function enqueue_styles() {
		global $post;
		if ( ! isset( $post ) ) {
			return;
		}
		$postType = $post->post_type;
		if ( $postType === 'post' || ( $postType === 'page' && has_shortcode( $post->post_content, 'BasecampForm' ) ) ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bcc-public.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css', array(), '5.0.0', 'all' );
		}
	}

	public function enqueue_scripts() {
		global $post;
		if ( ! isset( $post ) ) {
			return;
		}
		$postType = $post->post_type;
		if ( $postType === 'post' || ( $postType === 'page' && has_shortcode( $post->post_content, 'BasecampForm' ) ) ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bcc-public.js', array( 'jquery' ), $this->version, false );
			wp_localize_script(
				$this->plugin_name,
				'params',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'plugin' ),
				)
			);
			wp_enqueue_script( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js', array( 'jquery' ), '5.0.0', false );
			wp_enqueue_script( 'GoogleCaptcha', 'https://www.google.com/recaptcha/api.js?render=6LeQGyYaAAAAAINGjzIYW3mMczOjXK33rvRV3vdo', array( 'jquery' ), '3', false );
		}
	}

	public function BasecampFormFunc() {
		ob_start();
		include plugin_dir_path( __FILE__ ) . '/partials/bcc-public-display.php';
		return ob_get_clean();
	}

	/* WEBHOOKS */
	public function rest_api_init() {
		// Webhook for strawpolls.com
		// Route is /wp-json/bcc/v1/webhook/
		register_rest_route(
			'bcc/v1',
			'/webhook/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function webhook( WP_REST_Request $request ) {
		global $wpdb;

		$log      = new Bcc_Logger( 'webhook' );
		$remoteIp = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '?';
		$log->log( 'Webhook received from ' . $remoteIp, 'info' );

		try {
			$event = $request->get_param( 'event' );
			$data  = $request->get_param( 'data' );
			$data  = is_array( $data ) && isset( $data['poll'] ) ? $data['poll'] : null;
			$log->log( 'event=' . (string) $event . ' poll_id=' . ( is_array( $data ) ? (string) ( $data['id'] ?? '?' ) : '?' ) );

			if ( $event !== 'deadline_poll' || ! is_array( $data ) || ! isset( $data['id'] ) ) {
				$log->log( 'Rejecting: unsupported event or missing poll id', 'warning' );
				$log->flush();
				return new WP_REST_Response( array( 'ok' => false ), 400 );
			}

			$poll_id     = (string) $data['id'];
			$bcMessageId = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT bc_message_id FROM `{$wpdb->prefix}bcc_projects` WHERE poll_content_id = %s",
					$poll_id
				)
			);
			if ( $bcMessageId === null ) {
				$log->log( "Unknown poll {$poll_id} (no bcc_projects row)", 'warning' );
				$log->flush();
				return new WP_REST_Response(
                    array(
						'ok'     => false,
						'reason' => 'unknown poll',
                    ),
                    404 
                );
			}

			$client = new Basecamp3Client();

			$options    = array();
			$totalCount = 0;
			foreach ( $data['poll_options'] as $option ) {
				$options[ $option['value'] ] = (int) $option['vote_count'];
				$totalCount                 += (int) $option['vote_count'];
			}
			$win = ( $options['Ja'] ?? 0 ) > ( $options['Nein'] ?? 0 );
			$log->log( 'Vote counts: Ja=' . ( $options['Ja'] ?? 0 ) . ' Nein=' . ( $options['Nein'] ?? 0 ) . ' Enthaltung=' . ( $options['Enthaltung'] ?? 0 ) . ' win=' . ( $win ? 'yes' : 'no' ) );

			ob_start();
			include plugin_dir_path( __FILE__ ) . 'partials/bcc-basecamp-template-comment-votingended.php';
			$comment = ob_get_clean();

			$client->createComment(
				(int) get_option( 'bcc_b3_project_id' ),
				(int) $bcMessageId,
				array( 'content' => $comment )
			);
			$log->log( "Posted result comment to Basecamp message {$bcMessageId}" );

			$bcTodoId = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT bc_todo_id FROM `{$wpdb->prefix}bcc_projects` WHERE poll_content_id = %s",
					$poll_id
				)
			);
			if ( $bcTodoId !== null ) {
				$client->completeTodo( (int) get_option( 'bcc_b3_project_id' ), (int) $bcTodoId );
				$log->log( "Completed Basecamp todo {$bcTodoId}" );
			}

			$spApiKey = (string) get_option( 'bcc_sp_api_key' );
			if ( $spApiKey !== '' ) {
				try {
					$sp     = new \GuzzleHttp\Client(
                        array(
							'timeout'     => 15,
							'http_errors' => false,
                        ) 
                    );
					$spResp = $sp->delete(
						'https://api.strawpoll.com/v2/polls/' . $poll_id,
						array( 'headers' => array( 'X-API-KEY' => $spApiKey ) )
					);
					$status = $spResp->getStatusCode();
					if ( $status >= 200 && $status < 300 ) {
						$log->log( "Deleted Strawpoll {$poll_id}" );
					} else {
						// Strawpoll's API only supports session-cookie auth for DELETE
						// as of mid-2026 — X-API-KEY returns 403. We log it but
						// don't escalate because polls expire on their own.
						$log->log( "Strawpoll delete {$poll_id} returned HTTP {$status} (best-effort, ignoring): " . substr( (string) $spResp->getBody(), 0, 200 ), 'warning' );
					}
				} catch ( \Throwable $e ) {
					$log->log( 'Strawpoll delete failed (best-effort, ignoring): ' . $e->getMessage(), 'warning' );
				}
			}

			$wpdb->delete( $wpdb->prefix . 'bcc_projects', array( 'poll_content_id' => $poll_id ) );
			$log->log( 'Removed bcc_projects row, done.' );
			$log->flush();

			return new WP_REST_Response( array( 'ok' => true ) );
		} catch ( \Throwable $e ) {
			$log->log( 'Webhook failed: ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine(), 'error' );
			$logFile = $log->flush();
			Bcc_Notifier::sendError(
                'webhook',
                $e->getMessage(),
                array(
					'exception' => $e,
					'log_file'  => (string) $logFile,
                ) 
            );
			return new WP_REST_Response(
                array(
					'ok'     => false,
					'reason' => 'internal error',
                ),
                500 
            );
		}
	}

	/**
	 * Syncs newly added members from easyVerein to Basecamp. Triggered by the
	 * `easy_verein_basecamp_sync` action (WP-Cron once a day, or the manual
	 * sync button on the settings page).
	 */
	public function easy_verein_basecamp_sync() {
		$mutex = new Bcc_Mutex( 'easy_verein_sync' );
		if ( ! $mutex->acquire() ) {
			$this->logger->log( 'Sync skipped: another run is in progress.', 'info' );
			$this->logger->flush();
			return;
		}

		// Always prune old logs at the start of a run — cheap and self-healing.
		$pruned = $this->logger->pruneOldLogs();
		if ( $pruned > 0 ) {
			$this->logger->log( "Pruned {$pruned} old log file(s) (>" . Bcc_Logger::RETENTION_DAYS . ' days).', 'info' );
		}

		try {
			$this->assertRequiredOptions();

			$evClient         = new EasyVereinClient();
			$bcClient         = new Basecamp3Client();
			$projectIdHQ      = (int) get_option( 'bcc_ev_project_id' );
			$additional       = array_filter(
				array_map(
					'intval',
					array_map( 'trim', explode( ',', (string) get_option( 'bcc_ev_project_id_additional', '' ) ) )
				)
			);
			$welcomeText      = (string) get_option( 'bcc_ev_welcome_text', '' );
			$welcomeMessageId = (int) get_option( 'bcc_ev_welcome_text_message_id', 0 );

			$this->logger->log( 'Getting latest synced member' );
			$latestSyncedMember = $evClient->getLatestSyncedMember();
			$this->logger->log( 'Got latest synced member: ' . wp_json_encode( $latestSyncedMember ) );

			// Safety guard: pointer must identify the last synced member by
			// either id (preferred — invariant) or email_or_user_name (legacy
			// fallback for state created by plugin <= 1.x). Without either,
			// the break-condition can never fire and we would bulk-grant
			// every member in the fetch page.
			if (
				! is_array( $latestSyncedMember )
				|| ( empty( $latestSyncedMember['id'] ) && empty( $latestSyncedMember['email_or_user_name'] ) )
			) {
				throw new Exception(
					'Refusing to sync: ev_bc_sync_last_new is empty or missing both id and email_or_user_name. '
					. 'Set the pointer manually before the first sync to avoid bulk-granting existing members.'
				);
			}

			$this->logger->log( 'Fetching members from easyVerein' );
			$members = $evClient->getNewestMembers( 25 );
			$this->logger->log( 'Got ' . count( $members ) . ' members from easyVerein' );

			$pointerFound     = false;
			$notSyncedMembers = $this->collectNewMembers( $members, $latestSyncedMember, $pointerFound );
			$this->logger->log( 'Identified ' . count( $notSyncedMembers ) . ' member(s) to sync (pointer ' . ( $pointerFound ? 'matched' : 'not in window' ) . ')' );

			// Refuse to proceed if the pointer was not found in the fetched
			// window. Using an explicit found-flag (not a count comparison)
			// matters: prospects, resigned members or other skipped rows
			// would otherwise mask the overrun and let us bulk-grant the
			// remaining valid rows.
			if ( ! $pointerFound ) {
				$ident = $latestSyncedMember['id'] ?? $latestSyncedMember['email_or_user_name'] ?? '?';
				throw new Exception(
					'Refusing to sync: pointer "' . $ident
					. '" was not found in the most recent ' . count( $members )
					. ' members. Either the window was overrun or the pointer is stale; '
					. 'fix the pointer manually before retrying.'
				);
			}

			foreach ( $notSyncedMembers as $member ) {
				$this->syncMember( $member, $bcClient, $evClient, $projectIdHQ, $additional, $welcomeText, $welcomeMessageId );
			}
		} catch ( \Throwable $e ) {
			$this->logger->log( 'Sync failed: ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine(), 'error' );
			$logFile = $this->logger->flush();
			$mutex->release();

			Bcc_Notifier::sendError(
				'sync',
				$e->getMessage(),
				array(
					'exception' => $e,
					'log_file'  => (string) $logFile,
                )
			);

			throw new Exception( 'Sync failed (see ' . basename( (string) $logFile ) . '): ' . $e->getMessage(), 0, $e );
		}

		$this->logger->flush();
		$mutex->release();
	}

	/**
	 * Throws if any of the required plugin options are unset/empty.
	 */
	private function assertRequiredOptions(): void {
		$required = array( 'bcc_ev_api_key', 'bcc_ev_api_url', 'bcc_ev_project_id' );
		$missing  = array();
		foreach ( $required as $opt ) {
			$value = get_option( $opt );
			if ( $value === '' || $value === false ) {
				$missing[] = $opt;
			}
		}
		if ( ! empty( $missing ) ) {
			throw new Exception( 'Required plugin option(s) missing: ' . implode( ', ', $missing ) );
		}
	}

	/**
	 * @param array<int,object>        $members
	 * @param array<string,mixed>|null $latestSyncedMember
	 * @param bool                     $pointerFound  out-param: true if the pointer matched a row in $members
	 * @return array<int,object>
	 */
	private function collectNewMembers( array $members, ?array $latestSyncedMember, bool &$pointerFound = false ): array {
		// Prefer the invariant easyVerein primary key as break-marker — an
		// email_or_user_name change in easyVerein would otherwise blow the
		// state out the window. Fall back to email for legacy pointers
		// written by plugin <= 1.x.
		$lastId       = is_array( $latestSyncedMember ) ? ( $latestSyncedMember['id'] ?? null ) : null;
		$lastEmail    = is_array( $latestSyncedMember ) ? ( $latestSyncedMember['email_or_user_name'] ?? null ) : null;
		$today        = ( new DateTime( 'now', new DateTimeZone( 'Europe/Berlin' ) ) )->format( 'Y-m-d' );
		$result       = array();
		$pointerFound = false;

		foreach ( $members as $member ) {
			// Break first: identity check must beat all other filters so the
			// loop stops at the last-synced row even if that row has e.g. an
			// empty membership_number due to back-office cleanup.
			if ( $lastId !== null && isset( $member->id ) && (int) $member->id === (int) $lastId ) {
				$pointerFound = true;
				break;
			}
			if ( $lastId === null && $lastEmail !== null && ( $member->email_or_user_name ?? null ) === $lastEmail ) {
				$pointerFound = true;
				break;
			}

			$mNumber = $member->membership_number ?? null;
			if ( $mNumber === null || $mNumber === '' ) {
				// Only accepted members carry a membership_number; skip
				// prospects, applications and pseudo-accounts.
				continue;
			}

			// Skip members who have resigned on or before today. easyVerein
			// sets resignation_date when the member leaves the club; we
			// should not be granting them new Basecamp access.
			$resignationDate = $member->resignation_date ?? null;
			if ( ! empty( $resignationDate ) && substr( (string) $resignationDate, 0, 10 ) <= $today ) {
				continue;
			}

			$result[] = $member;
		}

		// Members come in DESC; sync oldest-first so a partial failure
		// leaves the "last synced" pointer in a sensible position.
		return array_reverse( $result );
	}

	private function syncMember(
		object $member,
		Basecamp3Client $bc,
		EasyVereinClient $ev,
		int $projectIdHQ,
		array $additionalProjectIds,
		string $welcomeText,
		int $welcomeMessageId
	): void {
		$email = $member->email_or_user_name ?? '(no-email)';
		$this->logger->log( "Syncing {$email}" );

		$details = $ev->getMemberContactDetails( $member );
		$this->logger->log( 'Got contact details for ' . $email );

		$payloadEmail = $details->primary_email ?? $details->private_email ?? $member->email ?? $email;
		$payloadName  = $details->name ?? trim( ( $details->first_name ?? '' ) . ' ' . ( $details->family_name ?? '' ) ) ?: $email;

		$this->logger->log( "Granting {$email} to project {$projectIdHQ}" );
		$result = $bc->createPersonInProject( $projectIdHQ, (string) $payloadEmail, (string) $payloadName );

		$granted = ( is_object( $result ) && isset( $result->granted ) && is_array( $result->granted ) ) ? $result->granted : array();
		if ( empty( $granted ) ) {
			$this->logger->log( "No new grant for {$email} (already in project?). Skipping additional projects + welcome.", 'info' );
		} else {
			$person   = $granted[0];
			$personId = (int) $person->id;
			$this->logger->log( "Granted {$email} as person {$personId}" );

			foreach ( $additionalProjectIds as $projectId ) {
				try {
					$bc->grantPersonToProject( $projectId, $personId );
					$this->logger->log( "Added {$email} to additional project {$projectId}" );
				} catch ( \Throwable $e ) {
					$this->logger->log( "Failed to add {$email} to additional project {$projectId}: " . $e->getMessage(), 'warning' );
				}
			}

			if ( $welcomeMessageId > 0 && $welcomeText !== '' ) {
				$userLink = '<bc-attachment sgid="' . esc_attr( (string) $person->attachable_sgid ) . '"></bc-attachment>';
				$message  = str_replace( '{user}', $userLink, $welcomeText );
				$bc->createComment( $projectIdHQ, $welcomeMessageId, array( 'content' => $message ) );
				$this->logger->log( "Posted welcome comment for {$email}" );
			}
		}

		$ev->setLatestSyncedMember(
			array(
				'id'                 => isset( $member->id ) ? (int) $member->id : null,
				'membership_number'  => $member->membership_number ?? null,
				'first_name'         => $details->first_name ?? null,
				'family_name'        => $details->family_name ?? null,
				'private_email'      => $details->private_email ?? null,
				'join_date'          => $member->join_date ?? null,
				'email_or_user_name' => $email,
			)
		);

		$this->logger->log( "Synced {$email}" );
	}
}
