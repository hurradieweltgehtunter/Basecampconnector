<?php
declare(strict_types=1);

/**
 * In-memory log buffer + file-flush + retention sweeper.
 *
 * One log file per run, retained for {@see RETENTION_DAYS} days. The `$type`
 * argument tags the file (e.g. `sync`, `submit`, `webhook`, `oauth`) so a
 * directory listing maps to entry points at a glance.
 *
 * Filenames use `Y-m-d_His_<type>.log` (no spaces, no colons) so they
 * roundtrip cleanly through FTP, rsync and Windows.
 */

if ( class_exists( 'Bcc_Logger', false ) ) {
    return;
}

class Bcc_Logger {

    public const RETENTION_DAYS = 90;

    /** @var list<array{level:string,message:string,timestamp:string}> */
    private array $entries = array();

    private string $dir;
    private string $type;

    public function __construct( string $type = 'misc', ?string $dir = null ) {
        $this->type = preg_replace( '/[^a-z0-9_-]/i', '', $type ) ?: 'misc';
        $this->dir  = $dir ?? ( plugin_dir_path( __DIR__ ) . 'log/' );
    }

    public function log( string $message, string $level = 'debug' ): void {
        $this->entries[] = array(
            'level'     => $level,
            'message'   => $message,
            'timestamp' => gmdate( 'Y-m-d H:i:s' ),
        );
    }

    /** @return array<int,array{level:string,message:string,timestamp:string}> */
    public function entries(): array {
        return $this->entries;
    }

    /**
     * Concatenate entries as plain text (used in email notifications).
     */
    public function renderText(): string {
        $body = '';
        foreach ( $this->entries as $entry ) {
            $body .= '[' . $entry['timestamp'] . '] ' . $entry['level'] . ': ' . $entry['message'] . "\r\n";
        }
        return $body;
    }

    /**
     * Flush the in-memory buffer to a timestamped file. Returns the absolute
     * path of the file or null if nothing was flushed.
     */
    public function flush(): ?string {
        if ( empty( $this->entries ) ) {
            return null;
        }

        if ( ! is_dir( $this->dir ) && ! wp_mkdir_p( $this->dir ) ) {
            return null;
        }

        $filename = $this->dir . gmdate( 'Y-m-d_His' ) . '_' . $this->type . '.log';
        file_put_contents( $filename, $this->renderText(), LOCK_EX );
        return $filename;
    }

    /**
     * Delete *.log files older than RETENTION_DAYS. error.log is preserved.
     * Returns the number of files removed. Errors during the sweep are
     * recorded into the in-memory buffer so the caller can flush them with
     * the rest of the run's entries.
     */
    public function pruneOldLogs(): int {
        if ( ! is_dir( $this->dir ) ) {
            $this->log( 'pruneOldLogs: log dir does not exist (' . $this->dir . ')', 'warning' );
            return 0;
        }

        $cutoff  = time() - ( self::RETENTION_DAYS * DAY_IN_SECONDS );
        $removed = 0;

        $handle = opendir( $this->dir );
        if ( $handle === false ) {
            $this->log( 'pruneOldLogs: opendir failed for ' . $this->dir . ' (check permissions)', 'error' );
            return 0;
        }

        // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- readdir() idiom.
        while ( ( $name = readdir( $handle ) ) !== false ) {
            if ( $name === '.' || $name === '..' || $name === '.gitignore' || $name === 'error.log' ) {
                continue;
            }
            if ( substr( $name, -4 ) !== '.log' ) {
                continue;
            }
            $path  = $this->dir . $name;
            $mtime = filemtime( $path );
            if ( $mtime === false ) {
                $this->log( "pruneOldLogs: filemtime failed for {$name}", 'warning' );
                continue;
            }
            if ( $mtime < $cutoff ) {
                if ( unlink( $path ) ) {
                    ++$removed;
                } else {
                    $this->log( "pruneOldLogs: unlink failed for {$name}", 'warning' );
                }
            }
        }
        closedir( $handle );

        return $removed;
    }
}
