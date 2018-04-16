/*global wshop_enhanced_select_params */
jQuery( function( $ ) {
	function getEnhancedSelectFormatString() {
		return {
			'language': {
				errorLoading: function() {
					// Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
					return wshop_enhanced_select.i18n_searching;
				},
				inputTooLong: function( args ) {
					var overChars = args.input.length - args.maximum;

					if ( 1 === overChars ) {
						return wshop_enhanced_select.i18n_input_too_long_1;
					}

					return wshop_enhanced_select.i18n_input_too_long_n.replace( '%qty%', overChars );
				},
				inputTooShort: function( args ) {
					var remainingChars = args.minimum - args.input.length;

					if ( 1 === remainingChars ) {
						return wshop_enhanced_select.i18n_input_too_short_1;
					}

					return wshop_enhanced_select.i18n_input_too_short_n.replace( '%qty%', remainingChars );
				},
				loadingMore: function() {
					return wshop_enhanced_select.i18n_load_more;
				},
				maximumSelected: function( args ) {
					if ( args.maximum === 1 ) {
						return wshop_enhanced_select.i18n_selection_too_long_1;
					}

					return wshop_enhanced_select.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
				},
				noResults: function() {
					return wshop_enhanced_select.i18n_no_matches;
				},
				searching: function() {
					return wshop_enhanced_select.i18n_searching;
				}
			}
		};
	}

	try {
			var wshop_types=['customer','product'];
			$( document.body ).on( 'wshop-enhanced-select-init', function() {
				
				// Ajax search boxes
				function on_obj_search(obj_type){
					$( ':input.wshop-'+obj_type+'-search' ).filter( ':not(.enhanced)' ).each( function() {
						var select2_args = {
							allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
							placeholder: $( this ).data( 'placeholder' ),
							minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
							escapeMarkup: function( m ) {
								return m;
							},
							ajax: {
								url:         wshop_enhanced_select.ajax_url,
								dataType:    'json',
								delay:       250,
								data:        function( params ) {
									return {
										obj_type:obj_type,
										term:params.term
									};
								},
								processResults: function( data ) {
									if ( data &&data.items) {
										return {
											results:  data.items
										};
									}
									
									return {
										results: []
									};
								},
								cache: true
							}
						};

						select2_args = $.extend( select2_args, getEnhancedSelectFormatString() );

						$( this ).select2( select2_args ).addClass( 'enhanced' );

						/*if ( $( this ).data( 'sortable' ) ) {
							var $select = $(this);
							var $list   = $( this ).next( '.select2-container' ).find( 'ul.select2-selection__rendered' );

							$list.sortable({
								placeholder : 'ui-state-highlight select2-selection__choice',
								forcePlaceholderSize: true,
								items       : 'li:not(.select2-search__field)',
								tolerance   : 'pointer',
								stop: function() {
									$( $list.find( '.select2-selection__choice' ).get().reverse() ).each( function() {
										var id     = $( this ).data( 'data' ).id;
										var option = $select.find( 'option[value="' + id + '"]' )[0];
										$select.prepend( option );
									} );
								}
							});
						}*/
					});
				}
				
				for(var i=0;i<wshop_types.length;i++){
					on_obj_search(wshop_types[i]);
				}
			})
			.trigger( 'wshop-enhanced-select-init' );

			//=================================================//
			
			$( 'html' ).on( 'click', function( event ) {
				if ( this === event.target ) {
					for(var i=0;i<wshop_types.length;i++){
						$( ':input.wshop-'+wshop_types[i]+'-search' ).filter( '.select2-hidden-accessible' ).select2( 'close' );
					}
				}
			});
		
	} catch( err ) {
		// If select2 failed (conflict?) log the error but don't stop other scripts breaking.
		window.console.log( err );
	}
});
