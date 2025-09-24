jQuery( function ( $ ) {
    var settings = window.bookcreatorTemplateTexts || null;
    if ( ! settings ) {
        return;
    }

    var $sectionsWrapper = $( '#bookcreator-template-texts-sections' );
    if ( ! $sectionsWrapper.length ) {
        return;
    }

    var $languageSelect = $( '#bookcreator-template-texts-language' );
    var $addButton      = $( '#bookcreator-template-texts-add' );
    var templateHtml    = $( '#bookcreator-template-texts-section-template' ).html() || '';

    var existingLanguages = [];
    var storedLanguages   = ( $sectionsWrapper.data( 'existing-languages' ) || '' ).toString();
    if ( storedLanguages.length ) {
        existingLanguages = storedLanguages.split( ',' ).filter( function ( value ) {
            return value.length > 0;
        } );
    }

    var emptyMessage = '';
    var $initialEmpty = $sectionsWrapper.find( '.bookcreator-template-texts-empty' ).first();
    if ( $initialEmpty.length ) {
        emptyMessage = $.trim( $initialEmpty.text() );
    }
    if ( ! emptyMessage && settings.strings && settings.strings.noTranslations ) {
        emptyMessage = settings.strings.noTranslations;
    }

    function serializeLanguages() {
        return existingLanguages.join( ',' );
    }

    function setExistingLanguages( languages ) {
        existingLanguages = languages.slice( 0 );
        var serialized = serializeLanguages();
        $sectionsWrapper.attr( 'data-existing-languages', serialized );
        $sectionsWrapper.data( 'existing-languages', serialized );
        refreshOptionStates();
    }

    function refreshOptionStates() {
        if ( ! $languageSelect.length ) {
            return;
        }

        $languageSelect.find( 'option' ).each( function () {
            var $option = $( this );
            var value   = $option.val();

            if ( ! value ) {
                return;
            }

            if ( existingLanguages.indexOf( value ) !== -1 ) {
                $option.attr( 'data-existing', '1' );
            } else {
                $option.removeAttr( 'data-existing' );
            }
        } );
    }

    function ensureEmptyMessage() {
        var hasSections = $sectionsWrapper.find( '.bookcreator-template-texts-section' ).length > 0;
        var $current    = $sectionsWrapper.find( '.bookcreator-template-texts-empty' );

        if ( hasSections ) {
            $current.remove();

            return;
        }

        if ( ! emptyMessage ) {
            return;
        }

        if ( $current.length ) {
            $current.text( emptyMessage );
        } else {
            $( '<p class="description bookcreator-template-texts-empty" />' )
                .text( emptyMessage )
                .appendTo( $sectionsWrapper );
        }
    }

    function getLanguageLabel( code ) {
        if ( ! code ) {
            return '';
        }

        if ( settings.languages && settings.languages[ code ] ) {
            return settings.languages[ code ];
        }

        return code.toUpperCase();
    }

    setExistingLanguages( existingLanguages );
    ensureEmptyMessage();

    $addButton.on( 'click', function ( event ) {
        event.preventDefault();

        if ( ! templateHtml ) {
            return;
        }

        var language = ( $languageSelect.val() || '' ).toString();
        if ( ! language ) {
            window.alert( settings.strings.selectLanguage );

            return;
        }

        if ( existingLanguages.indexOf( language ) !== -1 ) {
            if ( settings.strings.duplicateLanguage ) {
                window.alert( settings.strings.duplicateLanguage );
            }

            return;
        }

        var languageLabel = getLanguageLabel( language );
        var markup        = templateHtml
            .replace( /__LANG_LABEL__/g, languageLabel )
            .replace( /__LANG__/g, language );

        $sectionsWrapper.find( '.bookcreator-template-texts-empty' ).remove();
        $sectionsWrapper.append( $( markup ) );

        existingLanguages.push( language );
        setExistingLanguages( existingLanguages );
        ensureEmptyMessage();
        if ( $languageSelect.length ) {
            $languageSelect.val( '' );
        }
    } );

    $sectionsWrapper.on( 'click', '.bookcreator-template-texts-delete', function ( event ) {
        event.preventDefault();

        var $button  = $( this );
        var language = ( $button.data( 'language' ) || '' ).toString();
        if ( ! language ) {
            return;
        }

        var label          = getLanguageLabel( language );
        var confirmMessage = settings.strings.deleteConfirm || '';
        if ( confirmMessage ) {
            confirmMessage = confirmMessage.replace( '%s', label );
        } else {
            confirmMessage = settings.strings.replaceConfirm || '';
        }

        if ( confirmMessage && ! window.confirm( confirmMessage ) ) {
            return;
        }

        var $section = $button.closest( '.bookcreator-template-texts-section' );
        if ( $section.length ) {
            $section.remove();
        }

        existingLanguages = existingLanguages.filter( function ( value ) {
            return value !== language;
        } );
        setExistingLanguages( existingLanguages );

        if ( $languageSelect.length && $languageSelect.val() === language ) {
            $languageSelect.val( '' );
        }

        ensureEmptyMessage();
    } );

    $sectionsWrapper.on( 'click', '.bookcreator-template-texts-generate', function ( event ) {
        event.preventDefault();

        var $button  = $( this );
        var language = ( $button.data( 'language' ) || '' ).toString();
        if ( ! language ) {
            return;
        }

        var $section = $button.closest( '.bookcreator-template-texts-section' );
        if ( ! $section.length ) {
            return;
        }

        var $spinner  = $section.find( '.bookcreator-template-texts-generate + .spinner' ).first();
        if ( ! $spinner.length ) {
            $spinner = $section.find( '.spinner' ).first();
        }
        var $feedback = $section.find( '.bookcreator-template-texts-feedback' );

        var hasContent = false;
        $section.find( 'input[type="text"]' ).each( function () {
            if ( $.trim( $( this ).val() ).length > 0 ) {
                hasContent = true;
                return false;
            }
        } );

        if ( hasContent && settings.strings.replaceConfirm ) {
            if ( ! window.confirm( settings.strings.replaceConfirm ) ) {
                return;
            }
        }

        $button.prop( 'disabled', true );
        if ( $spinner.length ) {
            $spinner.addClass( 'is-active' );
        }

        $feedback.removeClass( 'notice notice-success notice-error' ).empty();
        if ( settings.strings.generating ) {
            $feedback.addClass( 'notice' ).append( $( '<p />' ).text( settings.strings.generating ) );
        }

        $.post( settings.ajaxUrl, {
            action: 'bookcreator_generate_template_text_translation',
            nonce: settings.nonce,
            language: language
        } ).done( function ( response ) {
            $feedback.removeClass( 'notice' );
            if ( response && response.success ) {
                var data   = response.data || {};
                var fields = data.fields || {};
                var keys   = settings.keys || [];

                keys.forEach( function ( key ) {
                    var selector = 'input[name="bookcreator_template_texts_translations[' + language + '][fields][' + key + ']"]';
                    var $input   = $section.find( selector );
                    if ( $input.length ) {
                        var value = '';
                        if ( Object.prototype.hasOwnProperty.call( fields, key ) ) {
                            value = fields[ key ];
                        }
                        $input.val( value );
                    }
                } );

                var generatedValue = data.generated || '';
                $section.find( '.bookcreator-template-texts-generated' ).val( generatedValue );

                var generatedLabel = '';
                if ( data.generated_display ) {
                    if ( settings.strings.generatedLabel ) {
                        generatedLabel = settings.strings.generatedLabel.replace( '%s', data.generated_display );
                    } else {
                        generatedLabel = data.generated_display;
                    }
                }
                $section.find( '.bookcreator-template-texts-generated-display' ).text( generatedLabel );

                if ( existingLanguages.indexOf( language ) === -1 ) {
                    existingLanguages.push( language );
                    setExistingLanguages( existingLanguages );
                }

                var successMessage = data.message || settings.strings.success;
                $feedback.removeClass( 'notice-error notice-success' )
                    .addClass( 'notice notice-success' )
                    .empty()
                    .append( $( '<p />' ).text( successMessage ) );

                if ( data.model_notice ) {
                    $feedback.append( $( '<p />' ).text( data.model_notice ) );
                }

                if ( data.warnings && data.warnings.length ) {
                    var $list = $( '<ul />' );
                    data.warnings.forEach( function ( warning ) {
                        $list.append( $( '<li />' ).text( warning ) );
                    } );
                    $feedback.append( $list );
                }
            } else {
                var errorMessage = settings.strings.error;
                if ( response && response.data && response.data.message ) {
                    errorMessage = response.data.message;
                }

                $feedback.removeClass( 'notice-success notice-error' )
                    .addClass( 'notice notice-error' )
                    .empty()
                    .append( $( '<p />' ).text( errorMessage ) );
            }
        } ).fail( function () {
            $feedback.removeClass( 'notice-success' )
                .addClass( 'notice notice-error' )
                .empty()
                .append( $( '<p />' ).text( settings.strings.error ) );
        } ).always( function () {
            $button.prop( 'disabled', false );
            if ( $spinner.length ) {
                $spinner.removeClass( 'is-active' );
            }
        } );
    } );
} );
