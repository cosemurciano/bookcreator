(function ($) {
    function updateStatus($status, message, type) {
        $status.removeClass('success error').text('');
        if (message) {
            $status.addClass(type || '').text(message);
        }
    }

    $(function () {
        var settings = window.bookcreatorClaudeSettings || {};
        var $button = $('#bookcreator_claude_test_connection');
        var $status = $('#bookcreator_claude_test_connection_status');

        if (!$button.length || !settings.ajaxUrl) {
            return;
        }

        $button.on('click', function () {
            updateStatus($status, settings.messages ? settings.messages.testing : '');
            $button.prop('disabled', true);

            $.ajax({
                url: settings.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'bookcreator_test_claude_connection',
                    nonce: settings.nonce
                }
            })
                .done(function (response) {
                    if (response && response.success && response.data && response.data.message) {
                        updateStatus($status, response.data.message, 'success');
                    } else if (response && response.data && response.data.message) {
                        updateStatus($status, response.data.message, 'error');
                    } else if (settings.messages && settings.messages.genericError) {
                        updateStatus($status, settings.messages.genericError, 'error');
                    }
                })
                .fail(function (jqXHR) {
                    var message = settings.messages ? settings.messages.genericError : '';
                    if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                        message = jqXHR.responseJSON.data.message;
                    }
                    updateStatus($status, message, 'error');
                })
                .always(function () {
                    $button.prop('disabled', false);
                });
        });
    });
})(jQuery);
