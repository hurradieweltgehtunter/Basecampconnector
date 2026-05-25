<?php
declare(strict_types=1);

/**
 * easyVerein API v3 client (snake_case schema, token rotation).
 *
 * Token rotation rules per easyVerein v2.0+ docs: when the response header
 * `token_refresh_needed: True` appears, hit `/refresh-token/` with the current
 * bearer; the old token is invalidated immediately and a new one is returned
 * in `{ "Bearer": "..." }`. The new token must be persisted before the next
 * call or the next request will 401.
 */

if ( class_exists( 'EasyVereinClient', false ) ) {
    return;
}

class EasyVereinClient {

    private const DEFAULT_API_URL = 'https://easyverein.com/api/v3.0/';

    private GuzzleHttp\Client $http;

    public function __construct( int $timeoutSeconds = 30 ) {
        $this->http = new GuzzleHttp\Client(
            array(
				'timeout'         => $timeoutSeconds,
				'connect_timeout' => 10,
				'http_errors'     => false,
				'headers'         => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'PLATZprojekt-easyVerein-Connector',
				),
            ) 
        );
    }

    private function apiBase(): string {
        $url = trim( (string) get_option( 'bcc_ev_api_url' ) );
        if ( $url === '' ) {
            $url = self::DEFAULT_API_URL;
        }
        return rtrim( $url, '/' ) . '/';
    }

    private function apiKey(): string {
        return trim( (string) get_option( 'bcc_ev_api_key' ) );
    }

    /**
     * Fetch newest members, ordered by joinDate descending.
     *
     * easyVerein v3 quirk: ordering field names are **camelCase** (matching
     * the Django source field), even though the JSON response body keys are
     * snake_case. Snake_case sort keys are silently accepted but ignored
     * (returns ascending) — see the API docs for examples like
     * `contactDetails__firstName`. We sort by `joinDate` because that field
     * is populated only when a member becomes a full (paying) member; using
     * `-id` would risk skipping members whose application was created early
     * but only later upgraded to full membership.
     *
     * @return array<int,object>
     * @throws Exception
     */
    public function getNewestMembers( int $limit = 25 ): array {
        $response = $this->authedRequest( 'GET', 'member/?ordering=-joinDate&limit=' . max( 1, $limit ) );
        $payload  = json_decode( (string) $response->getBody() );

        if ( ! is_object( $payload ) || ! isset( $payload->results ) || ! is_array( $payload->results ) ) {
            throw new Exception( 'easyVerein /member returned unexpected payload.' );
        }
        return $payload->results;
    }

    /**
     * Fetch full contact-details record for a member.
     *
     * @throws Exception
     */
    public function getMemberContactDetails( object $member ): object {
        if ( empty( $member->contact_details ) ) {
            throw new Exception( 'easyVerein member is missing contact_details URL.' );
        }
        $response = $this->authedRequest( 'GET', (string) $member->contact_details );
        $payload  = json_decode( (string) $response->getBody() );
        if ( ! is_object( $payload ) ) {
            throw new Exception( 'easyVerein contact-details returned unexpected payload.' );
        }
        return $payload;
    }

    /**
     * Force a token rotation. Returns the new bearer.
     *
     * @throws Exception
     */
    public function refreshApiToken(): string {
        $current = $this->apiKey();
        if ( $current === '' ) {
            throw new Exception( 'easyVerein API key is empty; cannot refresh.' );
        }

        $response = $this->http->request(
            'GET',
            $this->apiBase() . 'refresh-token/',
            array(
				'headers' => array( 'Authorization' => 'Bearer ' . $current ),
			) 
        );

        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();
        $data   = json_decode( $body, true );

        if ( $status !== 200 || ! is_array( $data ) || empty( $data['Bearer'] ) ) {
            throw new Exception( "easyVerein refresh-token failed: HTTP {$status} " . substr( $body, 0, 300 ) );
        }

        $newToken = (string) $data['Bearer'];
        update_option( 'bcc_ev_api_key', $newToken );

        return $newToken;
    }

    /**
     * Read/write the last successfully synced member.
     * Format: JSON object with at least `email_or_user_name`. Legacy camelCase
     * payloads from <= 2026 plugin versions are normalized to snake_case so
     * the rest of the sync code only sees one shape.
     */
    public function getLatestSyncedMember(): ?array {
        global $wpdb;
        $raw = $wpdb->get_var(
            "SELECT `value` FROM `{$wpdb->prefix}bcc_options` WHERE `identifier` = 'ev_bc_sync_last_new'"
        );
        if ( $raw === null || $raw === '' ) {
            return null;
        }

        $decoded = json_decode( $raw, true );
        if ( ! is_array( $decoded ) ) {
            // legacy pipe-separated format from 2024
            $parts   = explode( '|', $raw );
            $decoded = array(
                'membership_number'  => $parts[0] ?? null,
                'email_or_user_name' => $parts[1] ?? null,
            );
        }

        return $this->normalizeSyncState( $decoded );
    }

    public function setLatestSyncedMember( array $memberData ): void {
        global $wpdb;
        $memberData['synced_at'] = ( new DateTime( 'now', new DateTimeZone( 'Europe/Berlin' ) ) )->format( DateTime::ATOM );
        $payload                 = wp_json_encode( $memberData );

        $existing = $wpdb->get_var(
            "SELECT 1 FROM `{$wpdb->prefix}bcc_options` WHERE `identifier` = 'ev_bc_sync_last_new'"
        );
        if ( $existing === null ) {
            $wpdb->insert(
                "{$wpdb->prefix}bcc_options",
                array(
					'identifier' => 'ev_bc_sync_last_new',
					'value'      => $payload,
                ),
                array( '%s', '%s' )
            );
        } else {
            $wpdb->update(
                "{$wpdb->prefix}bcc_options",
                array( 'value' => $payload ),
                array( 'identifier' => 'ev_bc_sync_last_new' ),
                array( '%s' ),
                array( '%s' )
            );
        }
    }

    private function normalizeSyncState( array $state ): array {
        $map = array(
            'membershipNumber' => 'membership_number',
            'emailOrUserName'  => 'email_or_user_name',
            'firstName'        => 'first_name',
            'familyName'       => 'family_name',
            'privateEmail'     => 'private_email',
            'joinDate'         => 'join_date',
        );
        foreach ( $map as $old => $new ) {
            if ( array_key_exists( $old, $state ) && ! array_key_exists( $new, $state ) ) {
                $state[ $new ] = $state[ $old ];
            }
        }
        return $state;
    }

    /**
     * Issue an authenticated request and transparently rotate the token if
     * the API signals that one is needed.
     *
     * @throws Exception
     */
    private function authedRequest( string $method, string $pathOrUrl ): \Psr\Http\Message\ResponseInterface {
        $url = $this->absoluteUrl( $pathOrUrl );

        $response = $this->http->request(
            $method,
            $url,
            array(
				'headers' => array( 'Authorization' => 'Bearer ' . $this->apiKey() ),
			) 
        );

        if ( $response->getStatusCode() === 401 ) {
            $this->refreshApiToken();
            $response = $this->http->request(
                $method,
                $url,
                array(
					'headers' => array( 'Authorization' => 'Bearer ' . $this->apiKey() ),
				) 
            );
        }

        // Header values are case-insensitive per HTTP/1.1; check both styles.
        $needsRefresh = $response->getHeaderLine( 'token_refresh_needed' );
        if ( $needsRefresh === '' ) {
            $needsRefresh = $response->getHeaderLine( 'tokenRefreshNeeded' );
        }
        if ( strtolower( $needsRefresh ) === 'true' ) {
            // Rotate so the next call uses the fresh bearer. Old one becomes
            // invalid immediately per easyVerein v2.0+ docs.
            $this->refreshApiToken();
        }

        if ( $response->getStatusCode() >= 400 ) {
            throw new Exception(
                "easyVerein {$method} {$url} failed: HTTP {$response->getStatusCode()} " . substr( (string) $response->getBody(), 0, 300 )
            );
        }

        return $response;
    }

    private function absoluteUrl( string $pathOrUrl ): string {
        if ( strpos( $pathOrUrl, 'http://' ) === 0 || strpos( $pathOrUrl, 'https://' ) === 0 ) {
            return $pathOrUrl;
        }
        return $this->apiBase() . ltrim( $pathOrUrl, '/' );
    }
}
