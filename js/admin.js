jQuery(function($){
    if ( window.Konva ) {
        $(document).trigger('bookcreator:konva-ready', [ window.Konva ]);
    }

    var translationSettings = window.bookcreatorTranslation || null;
    if ( ! translationSettings ) {
        return;
    }

    $( '.bookcreator-translation-languages' ).each( function () {
        var $wrapper = $( this );
        var existingLanguages = ($wrapper.data( 'existing-languages' ) || '').toString().split( ',' ).filter( function ( value ) {
            return value.length > 0;
        } );

        $wrapper.on( 'click', '.bookcreator-translation-delete', function ( event ) {
            event.preventDefault();

            var $button  = $( this );
            var language = ($button.data( 'language' ) || '').toString();

            if ( ! language ) {
                return;
            }

            var label          = $button.data( 'label' ) || language;
            var confirmMessage = translationSettings.strings.deleteConfirm || '';

            if ( confirmMessage ) {
                confirmMessage = confirmMessage.replace( '%s', label );
                if ( ! window.confirm( confirmMessage ) ) {
                    return;
                }
            } else if ( ! window.confirm( translationSettings.strings.replaceConfirm ) ) {
                return;
            }

            var sectionId = ($button.data( 'section' ) || '').toString();
            var $section  = sectionId ? $( '#' + sectionId ) : $();

            if ( $section.length ) {
                if ( window.wp && wp.editor && typeof wp.editor.remove === 'function' ) {
                    $section.find( '.wp-editor-area' ).each( function () {
                        var editorId = $( this ).attr( 'id' );
                        if ( editorId ) {
                            try {
                                wp.editor.remove( editorId );
                            } catch ( error ) {
                                // Ignora eventuali errori durante la rimozione dell'editor.
                            }
                        }
                    } );
                }

                $section.remove();
            }

            var $listItem = $button.closest( 'li' );
            if ( $listItem.length ) {
                $listItem.remove();
            }

            existingLanguages = existingLanguages.filter( function ( value ) {
                return value !== language;
            } );

            var serializedLanguages = existingLanguages.join( ',' );
            $wrapper.data( 'existing-languages', serializedLanguages );
            $wrapper.attr( 'data-existing-languages', serializedLanguages );

            var $select = $wrapper.find( '.bookcreator-translation-language-select' );
            if ( $select.length ) {
                if ( $select.val() === language ) {
                    $select.val( '' );
                }

                $select.find( 'option[value="' + language + '"]' ).each( function () {
                    $( this ).removeAttr( 'data-existing' ).data( 'existing', null );
                } );
            }

            var $list = $wrapper.find( '.bookcreator-translation-languages-list' );
            if ( $list.length && 0 === $list.children( 'li' ).length ) {
                $list.remove();
                $wrapper.find( '.bookcreator-translation-no-languages' ).show();
            }

            var $sectionsWrapper = $( '.bookcreator-translation-sections' );
            if ( $sectionsWrapper.length && 0 === $sectionsWrapper.find( '.bookcreator-translation-section' ).length ) {
                var emptyText = $sectionsWrapper.data( 'empty-text' );
                if ( emptyText && 0 === $sectionsWrapper.find( '.bookcreator-translation-sections-empty' ).length ) {
                    $( '<p class="bookcreator-translation-sections-empty" />' ).text( emptyText ).appendTo( $sectionsWrapper );
                }
            }
        } );

        $wrapper.on( 'click', '.bookcreator-translation-generate', function ( event ) {
            event.preventDefault();

            var $button   = $( this );
            var postId    = parseInt( $button.data( 'post-id' ), 10 );
            var $select   = $wrapper.find( '.bookcreator-translation-language-select' );
            var language  = $select.val();
            var $spinner  = $wrapper.find( '.spinner' );
            var $feedback = $wrapper.find( '.bookcreator-translation-feedback' );

            if ( ! postId ) {
                return;
            }

            if ( ! language ) {
                window.alert( translationSettings.strings.selectLanguage );
                return;
            }

            var selectedOption = $select.find( 'option:selected' );
            var alreadyExists  = existingLanguages.indexOf( language ) !== -1 || selectedOption.data( 'existing' );

            if ( alreadyExists && ! window.confirm( translationSettings.strings.replaceConfirm ) ) {
                return;
            }

            $button.prop( 'disabled', true );
            $select.prop( 'disabled', true );
            $spinner.addClass( 'is-active' );

            $feedback
                .removeClass( 'notice notice-error notice-success' )
                .text( translationSettings.strings.generating );

            $.post( translationSettings.ajaxUrl, {
                action: 'bookcreator_generate_translation',
                nonce: translationSettings.nonce,
                post_id: postId,
                language: language
            } ).done( function ( response ) {
                $spinner.removeClass( 'is-active' );
                $button.prop( 'disabled', false );
                $select.prop( 'disabled', false );

                if ( response && response.success ) {
                    var data = response.data || {};
                    $feedback
                        .removeClass( 'notice-error notice-success' )
                        .addClass( 'notice notice-success' )
                        .text( data.message || translationSettings.strings.success );

                    if ( data.model_notice ) {
                        $( '<p />' ).text( data.model_notice ).appendTo( $feedback );
                    }

                    if ( data.warnings && data.warnings.length ) {
                        var $list = $( '<ul />' );
                        data.warnings.forEach( function ( warning ) {
                            $( '<li />' ).text( warning ).appendTo( $list );
                        } );
                        $feedback.append( $list );
                    }

                    if ( existingLanguages.indexOf( data.language ) === -1 ) {
                        existingLanguages.push( data.language );
                    }

                    window.setTimeout( function () {
                        window.location.reload();
                    }, 1200 );
                } else {
                    var errorMessage = translationSettings.strings.error;
                    if ( response && response.data && response.data.message ) {
                        errorMessage = response.data.message;
                    }

                    $feedback
                        .removeClass( 'notice-success notice-error' )
                        .addClass( 'notice notice-error' )
                        .text( errorMessage );
                }
            } ).fail( function () {
                $spinner.removeClass( 'is-active' );
                $button.prop( 'disabled', false );
                $select.prop( 'disabled', false );
                $feedback
                    .removeClass( 'notice-success notice-error' )
                    .addClass( 'notice notice-error' )
                    .text( translationSettings.strings.error );
            } );
        } );
    } );
});
