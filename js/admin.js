jQuery(document).ready(function($){
    $('.bc-color-field').wpColorPicker();

    $('#bc-generate-pdf').on('click', function(){
        var postId = $(this).data('post');
        var nonce  = $('#bookcreator_generate_pdf_nonce').val();
        var button = $(this);
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'bookcreator_generate_pdf',
                post_id: postId,
                nonce: nonce
            }
        })
        .done(function(response){
            if (response && response.success) {
                $('#bc-pdf-link').attr('href', response.data.url).text(response.data.label).show();
            } else if (response && response.data) {
                alert(response.data);
            } else {
                alert('Unexpected server response.');
            }
        })
        .fail(function(){
            alert('PDF generation failed.');
        })
        .always(function(){
            button.prop('disabled', false);
        });
    });
});
