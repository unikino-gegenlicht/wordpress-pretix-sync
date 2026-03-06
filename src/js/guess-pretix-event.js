jQuery(function($) {
    $('#pretix_guess_event_btn').on('click', function () {
        // TODO: Implement AJAX call and fill the input
        const input_id = "pretix_event_url"
        let url_input = document.getElementById(input_id)

        const datetime_field_id = "screening_date"
        let datetime_field = document.getElementById(datetime_field_id)


        $.post(pretixEventAjax.ajax_url, {
            _ajax_nonce: pretixEventAjax.nonce,
            action: "guess-pretix-event-url",
            screeningStart: datetime_field.value
        }, function (res) {
            let data = JSON.parse(res)
            url_input.value = data.eventUrl;
        })
    })
})