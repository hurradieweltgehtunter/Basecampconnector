<?php
declare(strict_types=1);

/**
 * Sends operator notifications about plugin failures. Recipient list is:
 *   - WordPress site admin (`admin_email`)
 *   - optional CC via the `bcc_admin_email` plugin setting
 *   - optional override / additional via the `bcc_exception_notification_email`
 *     filter (legacy, defaults to debugplatzprojekt@florianlenz.com if you
 *     hook it)
 *
 * Mails are plain text and include a structured body with origin, message,
 * site URL, plugin version and (if provided) a log file path.
 */

if ( class_exists( 'Bcc_Notifier', false ) ) {
    return;
}

class Bcc_Notifier {

    /**
     * Send a structured error notification.
     *
     * @param string               $origin  Short tag (e.g. "sync", "submit", "webhook").
     * @param string               $message Human-readable failure summary.
     * @param array<string,mixed>  $context Extra key/value rows rendered into the body.
     *                                      Recognised keys: `log_file`, `exception`, `stack`.
     */
    public static function sendError( string $origin, string $message, array $context = array() ): void {
        $to = self::recipients();
        if ( empty( $to ) ) {
            return;
        }

        $siteName = wp_specialchars_decode( (string) get_option( 'blogname' ), ENT_QUOTES );
        $subject  = sprintf( '[%s] Basecamp Connector — %s failed', $siteName, $origin );

        $lines   = array();
        $lines[] = 'A plugin run hit an error and did not complete.';
        $lines[] = '';
        $lines[] = 'Site:       ' . home_url();
        $lines[] = 'Origin:     ' . $origin;
        $lines[] = 'Plugin:     Basecamp Connector ' . ( defined( 'BCC_VERSION' ) ? BCC_VERSION : '?' );
        $lines[] = 'Time (UTC): ' . gmdate( 'Y-m-d H:i:s' );
        if ( ! empty( $context['log_file'] ) ) {
            $lines[] = 'Log file:   ' . (string) $context['log_file'];
        }
        $lines[] = '';
        $lines[] = '--- Error ---';
        $lines[] = $message;

        if ( ! empty( $context['exception'] ) && $context['exception'] instanceof Throwable ) {
            $e       = $context['exception'];
            $lines[] = '';
            $lines[] = '--- Exception ---';
            $lines[] = get_class( $e ) . ': ' . $e->getMessage();
            $lines[] = 'at ' . $e->getFile() . ':' . $e->getLine();
            $lines[] = '';
            $lines[] = '--- Stack trace ---';
            $lines[] = $e->getTraceAsString();
        } elseif ( ! empty( $context['stack'] ) ) {
            $lines[] = '';
            $lines[] = '--- Stack trace ---';
            $lines[] = (string) $context['stack'];
        }

        $body    = implode( "\r\n", $lines );
        $headers = array();

        $primary = array_shift( $to );
        foreach ( $to as $cc ) {
            $headers[] = 'Cc: ' . $cc;
        }

        wp_mail( $primary, $subject, $body, $headers );
    }

    /**
     * @return string[] de-duplicated list of valid recipient emails (primary first).
     */
    private static function recipients(): array {
        $candidates = array();

        $admin = trim( (string) get_option( 'admin_email' ) );
        if ( $admin !== '' && is_email( $admin ) ) {
            $candidates[] = $admin;
        }

        $cc = trim( (string) get_option( 'bcc_admin_email' ) );
        if ( $cc !== '' && is_email( $cc ) ) {
            $candidates[] = $cc;
        }

        $extra = apply_filters( 'bcc_exception_notification_email', '' );
        if ( is_string( $extra ) && trim( $extra ) !== '' && is_email( $extra ) ) {
            $candidates[] = $extra;
        }

        // Preserve order, drop duplicates (case-insensitive).
        $seen = array();
        $out  = array();
        foreach ( $candidates as $addr ) {
            $key = strtolower( $addr );
            if ( isset( $seen[ $key ] ) ) {
                continue;
            }
            $seen[ $key ] = true;
            $out[]        = $addr;
        }
        return $out;
    }
}
