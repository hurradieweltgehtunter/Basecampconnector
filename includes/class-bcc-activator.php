<?php
/**
 * Plugin activation: idempotent table create + lightweight schema migration.
 *
 * `bcc_options.value` was VARCHAR(1000) in <= 1.0.0; easyVerein bearer tokens
 * can exceed that, so we bump to TEXT on every activation.
 */

class Bcc_Activator {

	public static function activate(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->base_prefix;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql_options = "CREATE TABLE `{$prefix}bcc_options` (
			identifier varchar(100) NOT NULL,
			`value` longtext NOT NULL,
			PRIMARY KEY  (identifier)
		) $charset_collate;";
		dbDelta( $sql_options );

		// Existing installs created the column as VARCHAR(1000); make sure it
		// becomes LONGTEXT so refreshed bearer tokens (potentially > 1KB) fit.
		self::maybe_widen_options_value_column( $prefix );

		$sql_projects = "CREATE TABLE `{$prefix}bcc_projects` (
			id int(11) NOT NULL AUTO_INCREMENT,
			bc_message_id varchar(25) NOT NULL,
			bc_todo_id varchar(25) NOT NULL,
			poll_content_id varchar(50) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_projects );

		// Seed required option rows if they are missing. Existing rows are
		// preserved. Table name is a trusted WP prefix + literal — not user
		// input — but we still interpolate explicitly because $wpdb->prepare
		// rejects %s for identifiers.
		$tableName = $prefix . 'bcc_options';
		$seed      = array( 'access_token', 'refresh_token', 'access_token_expires', 'ev_bc_sync_last_new' );
		foreach ( $seed as $identifier ) {
			$exists = $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $tableName is the WP-managed table prefix concatenated with a literal.
				$wpdb->prepare( "SELECT 1 FROM `{$tableName}` WHERE identifier = %s", $identifier )
			);
			if ( $exists === null ) {
				$wpdb->insert(
					$tableName,
					array(
						'identifier' => $identifier,
						'value'      => '',
					),
					array( '%s', '%s' )
				);
			}
		}
	}

	private static function maybe_widen_options_value_column( string $prefix ): void {
		global $wpdb;
		$tableName = $prefix . 'bcc_options';
		$column    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
				 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'value'",
				$wpdb->dbname,
				$tableName
			)
		);
		if ( $column && strtolower( (string) $column->DATA_TYPE ) !== 'longtext' ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $tableName is the WP-managed table prefix concatenated with a literal.
			$wpdb->query( "ALTER TABLE `{$tableName}` MODIFY `value` LONGTEXT NOT NULL" );
		}
	}
}
