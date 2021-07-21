(function( $ ) {
	'use strict';

	$( window ).load(function() {
		$('#authenticate-app').on('click', function() {
			window.location.href = params.auth_url
		})
	});
})( jQuery );
