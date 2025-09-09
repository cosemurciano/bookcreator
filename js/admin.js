jQuery(document).ready(function($){
    $('.bc-color-field').wpColorPicker();

    $('#bc-generate-pdf').on('click', function(){
        var postId = $(this).data('post');
        var nonce = $('#bookcreator_generate_pdf_nonce').val();
        var button = $(this);
        button.prop('disabled', true);
        $.post(ajaxurl, {
            action: 'bookcreator_generate_pdf',
            post_id: postId,
            nonce: nonce
        }, function(response){
            button.prop('disabled', false);
            if(response.success){
                $('#bc-pdf-link').attr('href', response.data.url).text(response.data.label).show();
            } else if(response.data){
                alert(response.data);
            }
        });
    });
});
