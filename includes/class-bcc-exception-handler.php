<?php
/**
 * Global fallback handler for uncaught throwables in plugin code paths that
 * register it via set_exception_handler(). Persists to log/error.log and
 * notifies the operator via Bcc_Notifier.
 */

class BCC_Exception_Handler {

	public static function handle_exception( $exception ): void {
		$message = sprintf(
			"Exception: %s in %s on line %d\nStack trace:\n%s\n",
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
			$exception->getTraceAsString()
		);

		$error_log = plugin_dir_path( __DIR__ ) . 'log/error.log';
		// Last-resort logging: we cannot rely on Bcc_Logger here because the
		// handler may fire before our autoloader is ready (e.g. activation
		// failures). Appending to a known file is intentional.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $message, 3, $error_log );

		if ( class_exists( 'Bcc_Notifier' ) ) {
			Bcc_Notifier::sendError(
				'uncaught',
				$exception->getMessage(),
				array(
					'exception' => $exception,
					'log_file'  => $error_log,
                )
			);
		}
	}
}
