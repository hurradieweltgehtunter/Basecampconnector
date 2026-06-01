<?php
/**
 * Public AJAX entry point for the "Platz buchen" project application form.
 *
 * @package    Plugin
 * @subpackage Plugin/public
 */

if ( ! class_exists( 'Plugin_Public_Ajax' ) ) {

	class Plugin_Public_Ajax {

		public function __construct( string $plugin_name = '' ) {
			// Plugin name was tracked for logging context; the per-run
			// Bcc_Logger already carries enough origin info, so we no
			// longer store it. Constructor signature kept for BC.
			unset( $plugin_name );
		}

		/**
		 * Handler for both wp_ajax_* and wp_ajax_nopriv_* `submit_project`.
		 */
		public function submit_project(): void {
			global $wpdb;
			$log       = new Bcc_Logger( 'submit' );
			$remoteIp  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '?';
			$userAgent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '?';
			$log->log( "Submit received from {$remoteIp} UA=\"{$userAgent}\"", 'info' );

			try {
				$data = $this->validatePost( $log );
				$log->log( 'Validated form for project "' . $data['project_name'] . '" (' . $data['email'] . ')' );

				$pollData = $this->createStrawPoll( $data, $log );

				// Strawpoll is load-bearing: the Basecamp message links to
				// the vote, and the webhook keys back to the poll ID. If
				// Strawpoll failed, fail the whole submit instead of
				// posting a Basecamp record without a working vote link.
				if ( empty( $pollData['id'] ) || empty( $pollData['url'] ) ) {
					throw new Exception( 'Strawpoll creation returned no id/url — aborting submit so we do not create an orphan Basecamp message.' );
				}

				$bclient = new Basecamp3Client();

				$projectId       = (int) get_option( 'bcc_b3_project_id' );
				$messageboardId  = (int) get_option( 'bcc_b3_messageboard_id' );
				$todosetId       = (int) get_option( 'bcc_b3_todolistset_id', 0 );
				$campfireId      = (int) get_option( 'bcc_b3_campfire_id', 0 );
				$categoryId      = (int) get_option( 'bcc_b3_message_category_id', 0 );
				$campfireMessage = (string) get_option( 'bcc_b3_campfire_message', '' );

				$deadline = $this->deadline();

				ob_start();
				include __DIR__ . '/partials/bcc-basecamp-template-message.php';
				$content = ob_get_clean();

				$messagePayload = array(
					'subject' => 'Projekt-Anfrage: ' . $data['project_name'],
					'content' => $content,
					'status'  => 'active',
				);
				if ( $categoryId > 0 ) {
					$messagePayload['category_id'] = $categoryId;
				}

				$newMessage = $bclient->createMessage( $projectId, $messageboardId, $messagePayload );
				$log->log( 'Created Basecamp message ' . ( $newMessage->id ?? '?' ) . ' at ' . ( $newMessage->app_url ?? '?' ) );

				if ( $campfireId > 0 ) {
					$bclient->createCampfireLine(
						$projectId,
						$campfireId,
						array( 'content' => $campfireMessage . ' ' . ( $newMessage->app_url ?? '' ) )
					);
					$log->log( "Posted campfire line to {$campfireId}" );
				}

				$newTodo = null;
				if ( $todosetId > 0 ) {
					$people    = $bclient->listPeopleInProject( $projectId );
					$assignees = array();
					if ( is_array( $people ) ) {
						foreach ( $people as $person ) {
							if ( isset( $person->id ) ) {
								$assignees[] = (int) $person->id;
							}
						}
					}
					$log->log( 'Resolved ' . count( $assignees ) . ' project members as todo assignees' );

					$dueDate = date( 'd.m.Y', strtotime( '+' . get_option( 'bcc_sp_duration', 5 ) . ' days' ) );

					$newToDoList = $bclient->createTodolist(
                        $projectId,
                        $todosetId,
                        array(
							'name'        => 'ToDos Projektbewerbung ' . $data['project_name'],
							'description' => 'Fällig am ' . $dueDate . '<br />' . ( $newMessage->app_url ?? '' ),
						) 
                    );
					$log->log( 'Created todolist ' . ( $newToDoList->id ?? '?' ) );

					$newTodo = $bclient->createTodo(
                        $projectId,
                        (int) $newToDoList->id,
                        array(
							'content'      => 'Stimmungsbild',
							'description'  => 'Bitte stimme kurz ab ob du dieses Projekt zukünftig gerne auf dem PLATZprojekt sehen möchtest oder nicht. <br /><a href="' . ( $pollData['url'] ?? '' ) . '">zur Abstimmung</a>',
							'assignee_ids' => $assignees,
							'notify'       => true,
							'due_on'       => $dueDate,
							'starts_on'    => date( 'd.m.Y' ),
						) 
                    );
					$log->log( 'Created todo ' . ( $newTodo->id ?? '?' ) );
				}

				$wpdb->insert(
					$wpdb->prefix . 'bcc_projects',
					array(
						'bc_message_id'   => $newMessage->id ?? '',
						'bc_todo_id'      => $newTodo->id ?? '',
						'poll_content_id' => $pollData['id'] ?? '',
					),
					array( '%s', '%s', '%s' )
				);
				$log->log( 'Persisted bcc_projects row for poll ' . ( $pollData['id'] ?? '-' ) );
				$log->flush();

				wp_send_json( array( 'message' => $newMessage ) );
			} catch ( \Throwable $e ) {
				$log->log( 'submit_project failed: ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine(), 'error' );
				$logFile = $log->flush();
				Bcc_Notifier::sendError(
                    'submit',
                    $e->getMessage(),
                    array(
						'exception' => $e,
						'log_file'  => (string) $logFile,
                    ) 
                );
				wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
			}
		}

		/**
		 * @return array<string,mixed>
		 * @throws Exception
		 */
		private function validatePost( Bcc_Logger $log ): array {
			// Public form: reCAPTCHA is the auth layer, no WP nonce is
			// available for non-logged-in submitters. PHPCS's
			// NonceVerification rule does not apply here.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$captcha = isset( $_POST['captchaToken'] ) ? sanitize_text_field( wp_unslash( $_POST['captchaToken'] ) ) : '';
			$secret  = (string) get_option( 'bcc_gcaptcha_secret' );
			if ( $captcha === '' || $secret === '' ) {
				$log->log( 'reCAPTCHA missing (token empty or secret unset)', 'warning' );
				throw new Exception( 'reCAPTCHA validation failed (missing token or secret).' );
			}

			$g        = new \GuzzleHttp\Client(
				array(
					'timeout'     => 10,
					'http_errors' => false,
				)
			);
			$response = $g->post(
				'https://www.google.com/recaptcha/api/siteverify',
				array(
					'form_params' => array(
						'secret'   => $secret,
						'response' => $captcha,
					),
				)
			);
			$rData    = json_decode( (string) $response->getBody() );
			if ( ! isset( $rData->success ) || $rData->success !== true ) {
				$errorCodes = ( isset( $rData->{'error-codes'} ) && is_array( $rData->{'error-codes'} ) )
					? implode( ',', $rData->{'error-codes'} )
					: 'unknown';
				$log->log( "reCAPTCHA validation rejected by Google (errors: {$errorCodes})", 'warning' );
				throw new Exception( 'reCAPTCHA validation failed.' );
			}

			// PHPCS cannot follow the per-element sanitize_text_field below
			// out of the array-coalescing expression — explicitly map via
			// array_map so the sniff sees the sanitiser on the raw input.
			$rawData = isset( $_POST['data'] ) && is_array( $_POST['data'] )
				? array_map( 'sanitize_text_field', wp_unslash( $_POST['data'] ) )
				: array();
			$data    = array();
			foreach ( (array) $rawData as $k => $v ) {
				$data[ (string) $k ] = (string) $v;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			$requiredText = array( 'project_1', 'project_2', 'project_3', 'project_4', 'project_5', 'project_6', 'project_name' );
			foreach ( $requiredText as $field ) {
				if ( ! isset( $data[ $field ] ) || trim( (string) $data[ $field ] ) === '' ) {
					throw new Exception( "Required field '{$field}' is missing or empty." );
				}
			}
			if ( ! isset( $data['email'] ) || ! filter_var( $data['email'], FILTER_VALIDATE_EMAIL ) ) {
				throw new Exception( 'Invalid or missing email address.' );
			}

			// Just trim — sanitize_text_field already stripped tags and collapsed
			// whitespace. Deliberately NO htmlspecialchars(): the Basecamp subject
			// is plain text (entities like &quot; would show up literally), and the
			// HTML message body escapes each field via esc_html() in the template.
			foreach ( $data as $k => $v ) {
				$data[ $k ] = trim( (string) $v );
			}

			return $data;
		}

		private function deadline(): DateTime {
			return new DateTime( date( 'Y-m-d', strtotime( '+' . get_option( 'bcc_sp_duration', 5 ) . ' days' ) ) );
		}

		/**
		 * @param array<string,mixed> $data
		 * @return array<string,mixed>
		 */
		private function createStrawPoll( array $data, Bcc_Logger $log ): array {
			$deadline = $this->deadline();
			$body     = array(
				'type'         => 'multiple_choice',
				'title'        => 'Stimmungsbild Projektanfrage ' . $data['project_name'],
				'poll_meta'    => array(
					'description' => 'Würdest du dieses Projekt zukünftig gerne auf dem PLATZprojekt sehen?',
					'location'    => '',
				),
				'media'        => array( 'path' => null ),
				'poll_options' => array(
					array( 'value' => 'Ja' ),
					array( 'value' => 'Nein' ),
					array( 'value' => 'Enthaltung' ),
				),
				'poll_config'  => array(
					'is_private'           => 1,
					'allow_comments'       => 0,
					'is_multiple_choice'   => 0,
					'multiple_choice_min'  => null,
					'multiple_choice_max'  => null,
					'require_voter_names'  => 0,
					'duplication_checking' => 'ip',
					'deadline_at'          => $deadline->getTimestamp(),
					'status'               => 'published',
					'send_webhooks'        => 1,
				),
			);

			try {
				$g        = new \GuzzleHttp\Client(
                    array(
						'timeout'     => 15,
						'http_errors' => false,
                    ) 
                );
				$response = $g->request(
                    'POST',
                    'https://api.strawpoll.com/v2/polls',
                    array(
						'headers' => array( 'X-API-KEY' => get_option( 'bcc_sp_api_key' ) ),
						'json'    => $body,
					) 
                );
				$decoded  = json_decode( (string) $response->getBody(), true );
				if ( is_array( $decoded ) && isset( $decoded['poll'] ) ) {
					$log->log( 'Created Strawpoll ' . ( $decoded['poll']['id'] ?? '?' ) . ' deadline=' . ( $decoded['poll']['poll_config']['deadline_at'] ?? '?' ) );
					return $decoded['poll'];
				}
				$log->log( 'Strawpoll response missing poll key: ' . substr( (string) $response->getBody(), 0, 300 ), 'warning' );
				return array(
					'id'  => null,
					'url' => '',
				);
			} catch ( \Throwable $e ) {
				$log->log( 'Strawpoll create failed (continuing without poll): ' . $e->getMessage(), 'warning' );
				Bcc_Notifier::sendError(
					'submit/strawpoll',
					'Could not create StrawPoll for project "' . ( $data['project_name'] ?? '' ) . '": ' . $e->getMessage(),
					array( 'exception' => $e )
				);
				return array(
					'id'  => null,
					'url' => '',
				);
			}
		}
	}
}
