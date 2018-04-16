/*global wsocial_enhanced_select_params */
jQuery( function( $ ) {

	//select2
	function getEnhancedSelectFormatString() {
		return {
			'language': {
				errorLoading: function() {
					// Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
					return wsocial_enhanced_select.i18n_searching;
				},
				inputTooLong: function( args ) {
					var overChars = args.input.length - args.maximum;

					if ( 1 === overChars ) {
						return wsocial_enhanced_select.i18n_input_too_long_1;
					}

					return wsocial_enhanced_select.i18n_input_too_long_n.replace( '%qty%', overChars );
				},
				inputTooShort: function( args ) {
					var remainingChars = args.minimum - args.input.length;

					if ( 1 === remainingChars ) {
						return wsocial_enhanced_select.i18n_input_too_short_1;
					}

					return wsocial_enhanced_select.i18n_input_too_short_n.replace( '%qty%', remainingChars );
				},
				loadingMore: function() {
					return wsocial_enhanced_select.i18n_load_more;
				},
				maximumSelected: function( args ) {
					if ( args.maximum === 1 ) {
						return wsocial_enhanced_select.i18n_selection_too_long_1;
					}

					return wsocial_enhanced_select.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
				},
				noResults: function() {
					return wsocial_enhanced_select.i18n_no_matches;
				},
				searching: function() {
					return wsocial_enhanced_select.i18n_searching;
				}
			}
		};
	}

	try {
			//var wsocial_types=[];
			//
			$( document.body ).on( 'wsocial-enhanced-select-init', function() {
				
				// Ajax search boxes
				window.wsocial_function_on_obj_search=function(filter){
					$(filter ).filter( ':not(.enhanced)' ).each( function() {
						var obj_type = $( this ).data( 'type' );
						var custom_params = $( this ).data( 'custom_params' );
						if(custom_params&&typeof custom_params=='string'){
							custom_params = jQuery.parseJSON(custom_params);
						}
						
						if(!custom_params||typeof custom_params!='object'){custom_params={};}
						
						var select2_args = {
							multiple: $( this ).data( 'multiple' )=='1'?true:false,
							allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
							placeholder: $( this ).data( 'placeholder' ),
							minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '0',
							escapeMarkup: function( m ) {
								return m;
							},
							ajax: {
								url:         wsocial_enhanced_select.ajax_url,
								dataType:    'json',
								delay:       250,
								data:        function( params ) {
									custom_params.obj_type=obj_type;
									custom_params.term=params.term;
									
									return custom_params;
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
					});
				}
				
				window.wsocial_function_on_obj_search(':input.wsocial-search' );
				$(document).trigger('wsocial-on-select2-inited');
			}).trigger( 'wsocial-enhanced-select-init' );

			//=================================================//
			
			$( 'html' ).on( 'click', function( event ) {
				if ( this === event.target ) {
					$( ':input.wsocial-search' ).filter( '.select2-hidden-accessible' ).select2( 'close' );
				}
			});
		
	} catch( err ) {
		// If select2 failed (conflict?) log the error but don't stop other scripts breaking.
		window.console.log( err );
	}
});
