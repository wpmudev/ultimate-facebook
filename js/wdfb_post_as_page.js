(function ($) {
$(function () {
	
$("#post_as_page").change(function () {
	if ($("#post_as_page").is(":checked")) /*FB.ui({
		"method": "permissions.request",
		"scope": "offline_access"
	}, function (resp) { 
		if ("offline_access" != resp.perms) $("#post_as_page").attr("checked", false);
		else FB.Dialog.remove(FB.Dialog._active);
	});*/
	FB.getLoginStatus(function (resp) {
		FB.api({
			"method": "fql.query",
			"query": "SELECT offline_access FROM permissions WHERE uid=me()"
		}, function (resp) {
			var all_good = true;
			try {
				$.each(resp[0], function (idx, el) {
					if(el !== "1") all_good = false;
				});
			} catch (e) {
				all_good = false;
			}
			if (!all_good) {//$("#post_as_page").attr("checked", false);
				FB.login(function () {
					FB.Dialog.remove(FB.Dialog._active);
					window.location.reload(true);
				}, {
					"scope": 'offline_access'
				});
			}
		});
	});
	return true;
});

});
})(jQuery);