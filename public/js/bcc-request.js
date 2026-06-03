(function( $ ) {
	'use strict';

	$(function() {

		// Reads a field's logical value. Checkboxes report their value only
		// when checked (so a required-but-unchecked box validates as empty);
		// everything else is the trimmed input value.
		function fieldValue(field) {
			if (field.is(':checkbox')) {
				return field.is(':checked') ? (field.val() || '1') : '';
			}
			return (field.val() || '').trim();
		}

		function isValid(field) {
			var val = fieldValue(field);
			var ruleset = field.attr('data-rule');
			var valid = true;

			if (typeof ruleset !== typeof undefined && ruleset !== false) {
				$.each(ruleset.split('|'), function(index, raw) {
					var rule = raw.split(':');
					switch (rule[0]) {
						case 'mustnot':
							if (val === rule[1]) {
								valid = false;
								field.closest('.inputwrap').addClass('has-error');
							}
							break;
						case 'required':
							if (val.length === 0) {
								valid = false;
								field.closest('.inputwrap').addClass('has-error');
							}
							break;
						case 'email':
							var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
							if (!regex.test(val)) {
								valid = false;
								field.closest('.inputwrap').addClass('has-error');
							}
							break;
					}
				});
			}
			return valid;
		}

		$('form.bcc-form').each(function() {
			var form = $(this);

			form.nextAll().addClass('d-none late');

			// live character counters
			form.find('.word-count').each(function() {
				var wrap = $(this);
				wrap.find('.count_message span').html(wrap.find('textarea').val().trim().length);
				wrap.find('textarea').on('keyup', function() {
					$(this).closest('.word-count').find('.count_message span').html($(this).val().trim().length);
				});
			});

			form.on('submit', function(e) {
				e.preventDefault();

				var button = form.find('button[type="submit"]'),
					formdata = {},
					formValid = true,
					formType = form.attr('data-bcc-type');

				form.find('.has-error').removeClass('has-error');
				form.find('.general-error-ajax').addClass('d-none');
				form.find('.general-error-feedback').addClass('d-none');

				form.find(':input').not(':input[type=button], :input[type=submit], :input[type=reset]').each(function() {
					var field = $(this);
					if (!field.attr('id')) {
						return;
					}
					if (isValid(field)) {
						formdata[field.attr('id')] = fieldValue(field);
					} else {
						formValid = false;
						form.find('.general-error-feedback').removeClass('d-none');
					}
				});

				if (!formValid) {
					return false;
				}

				button.prop('disabled', true);

				grecaptcha.ready(function() {
					grecaptcha.execute(params.sitekey, { action: 'onSubmit' }).then(function(token) {
						$.ajax({
							url: params.ajaxurl,
							type: 'POST',
							dataType: 'JSON',
							data: {
								action: 'submit_bcc_request',
								form_type: formType,
								nonce: params.nonce,
								data: formdata,
								captchaToken: token,
								location: window.location.origin + window.location.pathname
							},
							error: function(xhr, textStatus, errorThrown) {
								console.log('Ajax ERROR: ' + errorThrown + ' / ' + textStatus);
								form.find('.general-error-ajax').removeClass('d-none');
								button.prop('disabled', false);
							},
							success: function() {
								button.hide();
								form.find('.success-feedback').removeClass('d-none');
								form.nextAll('.late').removeClass('d-none');
								$('html, body').animate({ scrollTop: form.find('.alert-success').offset().top - 120 }, 1000);
							}
						});
					});
				});

				return false;
			});
		});
	});
})( jQuery );
