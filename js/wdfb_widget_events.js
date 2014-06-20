(function ($) {
$(function () {

function init_ui () {
	$(document).on('focus', ".wdfb_date_threshold", function () {
		$(this).datepicker({
			dateFormat: 'yy-mm-dd'
		});
	});
}

function init () {
    if (typeof FB != 'object') return false; // Don't even bother
    FB.api({
        "method": "fql.query",
        "query": "SELECT rsvp_event,read_stream FROM permissions WHERE uid=me()"
    }, function (resp) {
            var all_good = true;
            try {
                $.each(resp[0], function (idx, el) {
                    if (el !== "1") all_good = false;
                });
            } catch (e) {
                all_good = false;
            }
            if (all_good) {
                init_ui();
            } else {
                $('.wdfb_widget_events_home').html(
                    '<div class="error below-h2">' + l10nWdfbEventsEditor.insuficient_perms + '<br />' +
                    '<a class="wdfb_grant_events_perms" href="#" >' + l10nWdfbEventsEditor.grant_perms + '</a>' +
                    '</div>'
                );
                $(document).on("click", ".wdfb_grant_events_perms", function () {
                    var $me = $(this);
                    var locale = $me.attr("data-wdfb_locale");
                    FB.login(function () {
                        window.location.reload(true);
                    }, {
                        "scope": 'rsvp_event,read_stream'
                    });
                    return false;
                });
            }
        });
}

if (typeof FB == 'object') {
	FB.getLoginStatus(function (resp) {
		init();
	});
}
	
});
})(jQuery);