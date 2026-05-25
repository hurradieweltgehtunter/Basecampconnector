<?php
declare(strict_types=1);

/**
 * Slim Basecamp 3 API client built on Guzzle 7.
 *
 * Replaces the abandoned arturf/basecamp-api + kriswallsmith/buzz stack that
 * shipped with this plugin until 2026. Only the endpoints actually consumed
 * by the plugin are exposed.
 *
 * Auth: OAuth2 with 37signals' launchpad. Access tokens live for two weeks,
 * refresh tokens are long lived. State is persisted in the plugin's custom
 * `bcc_options` table (identifier/value rows).
 */

if ( class_exists( 'Basecamp3Client', false ) ) {
    return;
}

class Basecamp3Client {

    private const AUTH_BASE     = 'https://launchpad.37signals.com';
    private const API_BASE      = 'https://3.basecampapi.com';
    private const TOKEN_TTL_PAD = 60; // refresh a minute before stated expiry

    private GuzzleHttp\Client $http;
    private string $accountId;
    private string $userAgent;
    private string $clientId;
    private string $clientSecret;

    public function __construct( int $timeoutSeconds = 30 ) {
        $this->accountId    = (string) get_option( 'bcc_b3_account_id' );
        $this->userAgent    = (string) get_option( 'bcc_b3_user_agent' );
        $this->clientId     = (string) get_option( 'bcc_b3_client_id' );
        $this->clientSecret = (string) get_option( 'bcc_b3_client_secret' );

        if ( $this->userAgent === '' ) {
            $this->userAgent = 'PLATZprojekt-Basecamp-Connector';
        }

        $this->http = new GuzzleHttp\Client(
            array(
				'timeout'         => $timeoutSeconds,
				'connect_timeout' => 10,
				'http_errors'     => false,
				'headers'         => array(
					'User-Agent'   => $this->userAgent,
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
            ) 
        );
    }

    public function getAccountId(): string {
        return $this->accountId;
    }

    // ---------------------------------------------------------------------
    // OAuth
    // ---------------------------------------------------------------------

    /**
     * Exchange an authorization code for access + refresh tokens.
     *
     * @return array{access_token:string,refresh_token:string,expires_in:int}
     * @throws Exception
     */
    public function exchangeAuthorizationCode( string $code, string $redirectUri ): array {
        $response = $this->http->post(
            self::AUTH_BASE . '/authorization/token',
            array(
				'query' => array(
					'type'          => 'web_server',
					'client_id'     => $this->clientId,
					'redirect_uri'  => $redirectUri,
					'client_secret' => $this->clientSecret,
					'code'          => $code,
				),
			) 
        );

        $body = json_decode( (string) $response->getBody(), true );
        if ( $response->getStatusCode() !== 200 || ! isset( $body['access_token'], $body['refresh_token'], $body['expires_in'] ) ) {
            throw new Exception( 'Basecamp OAuth exchange failed: ' . $response->getStatusCode() . ' ' . substr( (string) $response->getBody(), 0, 500 ) );
        }

        $this->persistTokens(
            (string) $body['access_token'],
            (string) $body['refresh_token'],
            (int) $body['expires_in']
        );

        return array(
            'access_token'  => (string) $body['access_token'],
            'refresh_token' => (string) $body['refresh_token'],
            'expires_in'    => (int) $body['expires_in'],
        );
    }

    /**
     * Refresh the access token using the stored refresh token.
     *
     * @throws Exception
     */
    public function refreshAccessToken(): string {
        $refreshToken = $this->readToken( 'refresh_token' );
        if ( $refreshToken === '' ) {
            throw new Exception( 'Basecamp refresh token is missing. Re-authenticate via the settings page.' );
        }
        if ( $this->clientId === '' || $this->clientSecret === '' ) {
            throw new Exception( 'Basecamp client credentials are not configured.' );
        }

        $response = $this->http->post(
            self::AUTH_BASE . '/authorization/token',
            array(
				'query' => array(
					'type'          => 'refresh',
					'refresh_token' => $refreshToken,
					'client_id'     => $this->clientId,
					'client_secret' => $this->clientSecret,
					'redirect_uri'  => home_url(),
				),
			) 
        );

        $body = json_decode( (string) $response->getBody(), true );
        if ( $response->getStatusCode() !== 200 || empty( $body['access_token'] ) ) {
            $err = is_array( $body ) && isset( $body['error'] ) ? $body['error'] : (string) $response->getBody();
            throw new Exception( 'Basecamp token refresh failed: ' . $response->getStatusCode() . ' ' . substr( $err, 0, 300 ) );
        }

        $expiresIn = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 1209600;
        $this->persistAccessToken( (string) $body['access_token'], $expiresIn );

        return (string) $body['access_token'];
    }

    private function ensureAccessToken(): string {
        $token   = $this->readToken( 'access_token' );
        $expires = (int) $this->readToken( 'access_token_expires' );

        if ( $token === '' || $expires <= ( time() + self::TOKEN_TTL_PAD ) ) {
            return $this->refreshAccessToken();
        }

        return $token;
    }

    private function persistTokens( string $access, string $refresh, int $expiresIn ): void {
        $this->writeToken( 'access_token', $access );
        $this->writeToken( 'refresh_token', $refresh );
        $this->persistAccessToken( $access, $expiresIn );
    }

    private function persistAccessToken( string $access, int $expiresIn ): void {
        $this->writeToken( 'access_token', $access );
        $this->writeToken( 'access_token_expires', (string) ( time() + max( 60, $expiresIn ) - self::TOKEN_TTL_PAD ) );
    }

    private function readToken( string $identifier ): string {
        global $wpdb;
        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `value` FROM `{$wpdb->prefix}bcc_options` WHERE `identifier` = %s",
                $identifier
            )
        );
        return (string) ( $value ?? '' );
    }

    private function writeToken( string $identifier, string $value ): void {
        global $wpdb;
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM `{$wpdb->prefix}bcc_options` WHERE `identifier` = %s",
                $identifier
            )
        );
        if ( $existing === null ) {
            $wpdb->insert(
                "{$wpdb->prefix}bcc_options",
                array(
					'identifier' => $identifier,
					'value'      => $value,
                ),
                array( '%s', '%s' )
            );
        } else {
            $wpdb->update(
                "{$wpdb->prefix}bcc_options",
                array( 'value' => $value ),
                array( 'identifier' => $identifier ),
                array( '%s' ),
                array( '%s' )
            );
        }
    }

    // ---------------------------------------------------------------------
    // Endpoints used by the plugin
    // ---------------------------------------------------------------------

    /**
     * Grant a fresh person access to a project. Returns the raw API payload
     * (decoded as object) so callers can keep using `->granted[0]`.
     *
     * @throws Exception
     */
    public function createPersonInProject( int $projectId, string $email, string $name, string $title = '', string $company = '' ) {
        return $this->request(
            'PUT',
            "/projects/{$projectId}/people/users.json",
            array(
				'create' => array(
					array(
						'name'          => $name,
						'email_address' => $email,
						'title'         => $title,
						'company_name'  => $company,
					),
				),
			) 
        );
    }

    /**
     * Grant an already-existing user access to an additional project.
     *
     * @throws Exception
     */
    public function grantPersonToProject( int $projectId, int $userId ) {
        return $this->request(
            'PUT',
            "/projects/{$projectId}/people/users.json",
            array(
				'grant' => array( $userId ),
			) 
        );
    }

    /**
     * @throws Exception
     */
    public function listPeopleInProject( int $projectId ) {
        return $this->request( 'GET', "/projects/{$projectId}/people.json" );
    }

    /**
     * @throws Exception
     */
    public function createMessage( int $projectId, int $messageboardId, array $params ) {
        return $this->request( 'POST', "/buckets/{$projectId}/message_boards/{$messageboardId}/messages.json", $params );
    }

    /**
     * @throws Exception
     */
    public function createComment( int $projectId, int $recordingId, array $params ) {
        return $this->request( 'POST', "/buckets/{$projectId}/recordings/{$recordingId}/comments.json", $params );
    }

    /**
     * @throws Exception
     */
    public function createCampfireLine( int $projectId, int $campfireId, array $params ) {
        return $this->request( 'POST', "/buckets/{$projectId}/chats/{$campfireId}/lines.json", $params );
    }

    /**
     * @throws Exception
     */
    public function createTodolist( int $projectId, int $todosetId, array $params ) {
        return $this->request( 'POST', "/buckets/{$projectId}/todosets/{$todosetId}/todolists.json", $params );
    }

    /**
     * @throws Exception
     */
    public function createTodo( int $projectId, int $todolistId, array $params ) {
        return $this->request( 'POST', "/buckets/{$projectId}/todolists/{$todolistId}/todos.json", $params );
    }

    /**
     * @throws Exception
     */
    public function completeTodo( int $projectId, int $todoId ) {
        return $this->request( 'POST', "/buckets/{$projectId}/todos/{$todoId}/completion.json", array() );
    }

    // ---------------------------------------------------------------------
    // Internal request plumbing
    // ---------------------------------------------------------------------

    /**
     * Issue an authenticated request against the Basecamp account scope.
     * Returns the decoded JSON payload as object (stdClass) for parity with
     * the legacy SDK callers, or `null` for empty bodies.
     *
     * @throws Exception
     */
    private function request( string $method, string $path, ?array $params = null ) {
        if ( $this->accountId === '' ) {
            throw new Exception( 'Basecamp account id is not configured.' );
        }

        $url = self::API_BASE . '/' . $this->accountId . $path;

        $options = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->ensureAccessToken(),
            ),
        );
        if ( $params !== null ) {
            $options['json'] = $params;
        }

        $response = $this->http->request( $method, $url, $options );
        $status   = $response->getStatusCode();

        // Single retry on 401 — token might have been revoked server side.
        if ( $status === 401 ) {
            $options['headers']['Authorization'] = 'Bearer ' . $this->refreshAccessToken();
            $response                            = $this->http->request( $method, $url, $options );
            $status                              = $response->getStatusCode();
        }

        $body = (string) $response->getBody();

        if ( $status >= 200 && $status < 300 ) {
            if ( $body === '' || $status === 204 ) {
                return null;
            }
            $decoded = json_decode( $body );
            if ( $decoded === null && json_last_error() !== JSON_ERROR_NONE ) {
                throw new Exception( "Basecamp {$method} {$path}: malformed JSON response" );
            }
            return $decoded;
        }

        if ( $status === 429 ) {
            $retryAfter = $response->getHeaderLine( 'Retry-After' );
            throw new Exception( "Basecamp rate limit hit on {$method} {$path}. Retry-After: {$retryAfter}" );
        }

        throw new Exception( "Basecamp {$method} {$path} failed: HTTP {$status} " . substr( $body, 0, 400 ) );
    }
}
