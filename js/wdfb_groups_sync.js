(function ($) {

function get_user_groups (me, token) {
	me
		.hide()
		.after('<img id="wdfb_sync_group-waiting" src="' + _wdfb_root_url + '/img/waiting.gif" />')
	;
	$.post(_wdfb_ajaxurl, {"action": "wdfb_get_facebook_groups_map"}, function (data) {
		$("#wdfb_sync_group-waiting").remove();
		me.show();
		if (!data.length) {
			return false;
		}
		me
			.hide()
			.after(data)
		;
		var root = $("#wdfb-fb_groups_selection");
		
		$("#wdfb-fb_groups_selection-map_cancel").click(function () {
			root.remove();
			me.show();
			return false;
		});
		
		$("#wdfb-fb_groups_selection-map").click(function () {
			var bp_group = me.attr("data-wdfb-bp_group_id")
				fb_group = $("#wdfb-fb_groups_selection-selection").val()
			;
			root.append('<img id="wdfb_sync_group-waiting" src="' + _wdfb_root_url + '/img/waiting.gif" />');
			$.post(_wdfb_ajaxurl, {
				"action": "wdfb_map_facebook_group",
				"token": token,
				"bp_group": bp_group,
				"fb_group": fb_group
			}, function (data) {
				$("#wdfb-fb_groups_selection").remove();
				me.show();
				if (!data.length) {
					return false;
				}
				window.location.reload();
				return false;
			});
			return false;
		});

	});
}

$(function () {
	$("#wdfb_sync_group").click(function () {
		var me = $(this);
		FB.login(function (response) {
			if (response.authResponse && response.authResponse.accessToken) get_user_groups(me, response.authResponse.accessToken);
		}, {"scope": "user_groups"});
		return false;
	});
});
})(jQuery);