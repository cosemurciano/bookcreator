jQuery( function ( $ ) {
    $( '.bookcreator-color-field' ).wpColorPicker();

    var $typeField = $( '#bookcreator_template_type' );
    if ( $typeField.length ) {
        var toggleTemplateSettings = function () {
            var currentType = $typeField.val();
            $( '.bookcreator-template-settings' ).each( function () {
                var $section = $( this );
                var matches = $section.data( 'template-type' ) === currentType;
                $section.toggle( matches );
            } );
        };

        $typeField.on( 'change', toggleTemplateSettings );
        toggleTemplateSettings();
    }
} );
