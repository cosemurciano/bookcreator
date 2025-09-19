jQuery(function($){
    if ( window.Konva ) {
        $(document).trigger('bookcreator:konva-ready', [ window.Konva ]);
    }
});
