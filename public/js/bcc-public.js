(function( $ ) {
	'use strict';
	
	$(function() {
		var errors = [] // contains validation erors
		
		function isValid(field) {
			var val = field.val().trim()
			var id = field.attr('id')
			var ruleset = field.attr('data-rule')
			var valid = true
		
			if (typeof ruleset !== typeof undefined && ruleset !== false) {
				var rules = field.attr('data-rule').split('|')
				
				$.each(rules, function(index, rule) {					
					var rule = rule.split(':')
		
					switch(rule[0]) {
						case 'mustnot':
							if (val === rule[1]) {
								errors.push([id, rule[0]])
								valid = true
								field.closest('.inputwrap').addClass('has-error')
								return;
							}
							break;
						
						case 'required':
							if (val.length === 0) {
								errors.push([id, rule[0]])
								valid = false
								field.closest('.inputwrap').addClass('has-error')
								return;
							}
							break;
						
						case 'email':
							var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
							if (!regex.test(val)) {
								errors.push([id, rule[0]])
								valid = true
								field.closest('.inputwrap').addClass('has-error')
								return;
							}
							break;
					}
				})
			}
			return valid
		}

		$("form#bcc").nextAll().addClass('d-none late');

		// init textareas and bind keyup's
		$('.word-count').each(function() {
			$(this).find('.count_message span').html($(this).find('textarea').val().trim().length)
			$(this).find('textarea').keyup(function() {
				$(this).parent('.inputwrap').find('.count_message span').html($(this).val().trim().length)
			})
		})

		// Submit
		$('form#bcc').on('submit', function(e) {
			e.preventDefault();

			var form = $(this),
				button = form.find('button[type="submit"'),
				formdata = {},	// contains data to send,
				formValid = true
			
			$('.has-error').removeClass('has-error')

			$('.general-error-ajax').addClass('d-none')
			$('.general-error-feedback').addClass('d-none')

			form.find(':input').not(':input[type=button], :input[type=submit], :input[type=reset]').each(function() {
				if (isValid($(this))) {
					formdata[$(this).attr('id')] = $(this).val()
					
				} else {
					formValid = false
					$('.general-error-feedback').removeClass('d-none')
					return false
				}
			})

			if (!formValid) {
				return false
			}

			button.prop("disabled",true);

			grecaptcha.ready(function() {
				grecaptcha.execute('6LeQGyYaAAAAAINGjzIYW3mMczOjXK33rvRV3vdo', {action: 'onSubmit'}).then(function(token) {
					errors = []

					$.ajax( {
						// Set the call parameters.
						url    : params.ajaxurl,
						type   : 'POST',
						dataType: 'JSON',
						data   : {
							action  	: 'submit_project',
							nonce   	: params.nonce,
							data		: formdata,
							captchaToken: token,
							location	: window.location.origin + window.location.pathname
						},
						error : function( MLHttpRequest, textStatus, errorThrown ) {
							console.log('Ajax ERROR: ' + errorThrown)
							console.log('Ajax ERROR: ' + textStatus)
							
							$('.general-error-ajax').removeClass('d-none')
							button.prop("disabled",false);
						},
						success : function( response ) {
							button.hide();
							$('.success-feedback').removeClass('d-none')
							$('.late').removeClass('d-none')
							
							$("html, body").animate({ scrollTop: $('.alert-success').offset().top - $( ".navigation-top" ).outerHeight() - $('h2').outerHeight() }, 1000);
						},
						complete : function( reply ) {

						}
					} )
				});
			});

			return false;
		})
	})
})( jQuery );