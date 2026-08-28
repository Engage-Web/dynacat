( function ( $ ) {
	'use strict';

	$( function () {
		var timer;
		var $filter = $( '#filbox' );
		var $result = $( '#result' );

		if ( ! $filter.length ) {
			return;
		}

		function showResults( response ) {
			var $list;

			$result.empty();
			if ( ! response.success ) {
				$result.text( dynacatSettings.errorText );
				return;
			}

			$list = $( '<ul>' );
			$.each( response.data, function ( index, category ) {
				$( '<button>', {
					type: 'button',
					class: 'catlink',
					text: category.label
				} ).data( 'category', category ).appendTo( $( '<li>', {
					class: 'dynaparentoption'
				} ).appendTo( $list ) );
			} );
			$result.append( $list );
		}

		function checkCategories() {
			var query = $filter.val();

			if ( ! query ) {
				$result.empty();
				return;
			}

			$.post( dynacatSettings.ajaxUrl, {
				action: 'check_cat',
				data: query,
				nonce: dynacatSettings.nonce
			} ).done( showResults ).fail( function () {
				$result.text( dynacatSettings.errorText );
			} );
		}

		$filter.on( 'input', function () {
			$result.empty().append( $( '<img>', {
				src: dynacatSettings.loaderUrl,
				alt: '',
				class: 'loading'
			} ) );
			window.clearTimeout( timer );
			timer = window.setTimeout( checkCategories, 1000 );
		} );

		$result.on( 'click', '.catlink', function () {
			var category = $( this ).data( 'category' );

			$filter.val( category.name );
			$( '#post_category' ).val( category.id );
			$( this ).css( 'font-weight', 900 )
				.closest( 'li' ).show()
				.siblings().hide( 'slow' );
		} );
	} );
}( jQuery ) );
