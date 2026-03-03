/**
 * Admin JS for WooCommerce Product Image Associate plugin.
 */
( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		var $form   = $( '.wpia-wrap form' );
		var $button = $form.find( '[name="import_images_button"]' );

		$form.on( 'submit', function () {
			$button.prop( 'disabled', true ).val( 'Processing…' );
		} );
	} );
} )( jQuery );
