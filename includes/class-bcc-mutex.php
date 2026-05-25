<?php
declare(strict_types=1);

/**
 * Lightweight transient-backed mutex so the same sync action can not run
 * twice in parallel (manual click + cron, or two cron jobs racing — both
 * happened in 2026-04 and produced duplicate Basecamp invites).
 */

if ( class_exists( 'Bcc_Mutex', false ) ) {
    return;
}

class Bcc_Mutex {

    public const DEFAULT_TTL = 300; // 5 minutes — well above one full sync run

    private readonly string $key;
    private readonly int $ttl;
    private bool $owned = false;

    public function __construct( string $key, int $ttl = self::DEFAULT_TTL ) {
        $this->key = 'bcc_mutex_' . $key;
        $this->ttl = $ttl;
    }

    public function acquire(): bool {
        // add_option returns false if the option already exists, which gives
        // us an atomic check-and-set on top of WP's options API.
        if ( add_option( $this->key, (string) time(), '', false ) ) {
            $this->owned = true;
            return true;
        }

        // Stale lock recovery: if the lock is older than ttl, claim it.
        $lockedAt = (int) get_option( $this->key, 0 );
        if ( $lockedAt > 0 && ( time() - $lockedAt ) > $this->ttl ) {
            update_option( $this->key, (string) time() );
            $this->owned = true;
            return true;
        }

        return false;
    }

    public function release(): void {
        if ( ! $this->owned ) {
            return;
        }
        delete_option( $this->key );
        $this->owned = false;
    }
}
