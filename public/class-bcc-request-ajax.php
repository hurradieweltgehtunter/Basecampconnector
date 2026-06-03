<?php
/**
 * Public AJAX entry point for the simple "request" forms (event booking,
 * room request). Unlike the project application (Plugin_Public_Ajax), these
 * have NO StrawPoll vote, NO todo and NO webhook round-trip — they just post
 * a message to a per-form Basecamp message board.
 *
 * The form behaviour is data-driven via self::formConfig(): each form type
 * declares its required fields, message template, subject and the option keys
 * that hold its Basecamp target (project + message board).
 *
 * @package    Bcc
 * @subpackage Bcc/public
 */

if ( ! class_exists( 'Bcc_Request_Ajax' ) ) {

	class Bcc_Request_Ajax {

		/**
		 * Form-type definitions. Add a new entry here + a partial + a template
		 * to introduce another request form — no handler changes needed.
		 *
		 * @return array<string,array<string,mixed>>|null
		 */
		public static function formConfig( string $type ): ?array {
			$configs = array(
				'event' => array(
					'project_id_option'      => 'bcc_b3_event_project_id',
					'messageboard_id_option' => 'bcc_b3_event_messageboard_id',
					'template'               => 'bcc-basecamp-template-event.php',
					'subject_prefix'         => 'Veranstaltungsanfrage: ',
					'subject_field'          => 'event_name',
					'required'               => array(
						'format',
						'member',
						'name',
						'phone',
						'email',
						'event_name',
						'event_type',
						'event_description',
						'motivation',
						'date',
						'time',
						'entry',
						'size',
						'aware_responsibility',
						'aware_public',
						'aware_helpers',
						'aware_cleanup',
						'termsAccepted',
					),
				),
				'room'  => array(
					'project_id_option'      => 'bcc_b3_room_project_id',
					'messageboard_id_option' => 'bcc_b3_room_messageboard_id',
					'template'               => 'bcc-basecamp-template-room.php',
					'subject_prefix'         => 'Raumanfrage: ',
					'subject_field'          => 'name',
					'required'               => array(
						'format',
						'member',
						'name',
						'position',
						'phone',
						'email',
						'usage_type',
						'motivation',
						'room_requirements',
						'usage_description',
						'date',
						'time',
						'people_count',
						'termsAccepted',
					),
				),
			);

			return $configs[ $type ] ?? null;
		}

		/**
		 * Handler for wp_ajax(_nopriv)_submit_bcc_request.
		 */
		public function submit_request(): void {
			$log       = new Bcc_Logger( 'request' );
			$remoteIp  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '?';
			$userAgent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '?';

			try {
				// phpcs:disable WordPress.Security.NonceVerification.Missing
				$type = isset( $_POST['form_type'] ) ? sanitize_key( wp_unslash( $_POST['form_type'] ) ) : '';
				// phpcs:enable WordPress.Security.NonceVerification.Missing
				$config = self::formConfig( $type );
				if ( $config === null ) {
					throw new Exception( 'Unknown request form type: "' . $type . '"' );
				}
				$log->log( "Request ({$type}) received from {$remoteIp} UA=\"{$userAgent}\"", 'info' );

				$data = $this->validatePost( $config, $log );

				$projectId      = (int) get_option( $config['project_id_option'] );
				$messageboardId = (int) get_option( $config['messageboard_id_option'] );
				if ( $projectId <= 0 || $messageboardId <= 0 ) {
					throw new Exception(
						'Basecamp target not configured for form "' . $type . '" ('
						. $config['project_id_option'] . '=' . $projectId . ', '
						. $config['messageboard_id_option'] . '=' . $messageboardId . ').'
					);
				}

				ob_start();
				include __DIR__ . '/partials/' . $config['template'];
				$content = ob_get_clean();

				$subjectValue = trim( (string) ( $data[ $config['subject_field'] ] ?? '' ) );
				$subject      = $config['subject_prefix'] . ( $subjectValue !== '' ? $subjectValue : 'ohne Titel' );

				$bclient    = new Basecamp3Client();
				$newMessage = $bclient->createMessage(
					$projectId,
					$messageboardId,
					array(
						'subject' => $subject,
						'content' => $content,
						'status'  => 'active',
					)
				);
				$log->log( 'Created Basecamp message ' . ( $newMessage->id ?? '?' ) . ' at ' . ( $newMessage->app_url ?? '?' ) );
				$log->flush();

				wp_send_json( array( 'message' => $newMessage ) );
			} catch ( \Throwable $e ) {
				$log->log( 'submit_request failed: ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine(), 'error' );
				$logFile = $log->flush();
				Bcc_Notifier::sendError(
					'request',
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
		 * Verifies reCAPTCHA, then sanitises + validates the posted fields
		 * against the form's required list.
		 *
		 * @param array<string,mixed> $config
		 * @return array<string,string>
		 * @throws Exception
		 */
		private function validatePost( array $config, Bcc_Logger $log ): array {
			// Public form: reCAPTCHA is the auth layer, no WP nonce is available
			// for non-logged-in submitters.
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

			$rawData = isset( $_POST['data'] ) && is_array( $_POST['data'] )
				? array_map( 'sanitize_text_field', wp_unslash( $_POST['data'] ) )
				: array();
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			$data = array();
			foreach ( (array) $rawData as $k => $v ) {
				$data[ (string) $k ] = trim( (string) $v );
			}

			foreach ( $config['required'] as $field ) {
				if ( ! isset( $data[ $field ] ) || $data[ $field ] === '' ) {
					throw new Exception( "Required field '{$field}' is missing or empty." );
				}
			}
			if ( ! isset( $data['email'] ) || ! filter_var( $data['email'], FILTER_VALIDATE_EMAIL ) ) {
				throw new Exception( 'Invalid or missing email address.' );
			}

			return $data;
		}
	}
}
