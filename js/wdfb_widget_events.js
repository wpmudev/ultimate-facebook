(function ($) {
	$(function () {

		function init_ui() {
			$(document).on('focus', ".wdfb_date_threshold", function () {
				$(this).datepicker({
					dateFormat: 'yy-mm-dd'
				});
			});
		}

		function init() {
			if (typeof FB != 'object') return false; // Don't even bother
			perms = new Array();
			perms.push('user_events');

			FB.api('me/permissions', function (resp) {
				var all_good = true;
				try {
					var missing_perm = 0;
					$.each(perms, function (idx, el) {
						$.each(resp.data, function (index, val) {
							if (val.permission !== el) {
								return;
							} else {
								if (val.status !== 'granted') {
									missing_perm++;
								}
							}
						});
					});
				} catch (e) {
					missing_perm = 1;
				}
				if (!missing_perm) {
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
							"scope": 'user_events'
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