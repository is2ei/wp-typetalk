/* global jQuery */
(function($){

	var self = {
		testNotifySpinner: null,
		testNotifyResponse: null
	};

	self.init = function() {
		self.testNotifySpinner  = $( '#typetalk-test-notify .spinner' );
		self.testNotifyResponse = $( '#typetalk-test-notify-response' );

		$('#typetalk-test-notify-button').on( 'click', self.testNotifyClickHandler );
	};

	self.testNotifyClickHandler = function(e) {
		self.testNotifyResponse.html('');
		self.testNotifySpinner.show();

		var xhr = $.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				'action'           : 'typetalk_test_notify',
				'endpoint_url'     : $('[name="typetalk_setting[endpoint_url]"]').val(),
				'test_notify_nonce': $('#typetalk-test-notify-nonce').val()
			}
		} );

		xhr.done( function( r ) {
			self.testNotifyResponse.html( '<span style="color: green">OK</span>' );
			self.testNotifySpinner.hide();
		} );

		xhr.fail( function( xhr, textStatus ) {
			var message = textStatus;
			if ( typeof xhr.responseJSON === 'object' ) {
				if ( 'data' in xhr.responseJSON && typeof xhr.responseJSON.data === 'string' ) {
					message = xhr.responseJSON.data;
				}
			} else if ( typeof xhr.statusText === 'string' ) {
				message = xhr.statusText;
			}
			self.testNotifyResponse.html( '<span style="color: red">' + message + '</span>' );
			self.testNotifySpinner.hide();
		} );

		e.preventDefault();
	};

	// Init.
	$(function(){
		self.init();
	});

}(jQuery));