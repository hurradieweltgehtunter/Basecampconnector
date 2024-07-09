<?php

class BCC_Exception_Handler {

  public static function handle_exception($exception) {
    $error_log = plugin_dir_path( dirname( __FILE__ ) ) . 'log/error.log';

    error_log(
        sprintf(
            "Exception: %s in %s on line %d\nStack trace:\n%s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ),
        3,
        $error_log
    );

    wp_mail(
        'debugplatzprojekt@florianlenz.com',
        'Basecamp Connector Exception',
        sprintf(
            "Exception: %s in %s on line %d\nStack trace:\n%s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        )
    );
  }
}