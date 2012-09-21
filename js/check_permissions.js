(function ($) { 
	
function check_perms () {
	var $perms = $(".wdfb_grant_perms:first");
	if (!$perms.length) return false;
	var query = "SELECT " + $perms.attr("data-wdfb_perms") + " FROM permissions WHERE uid=me()";

	FB.api({
		"method": "fql.query",
		//"access_token": FB.getAccessToken(),
		"query": query
	}, function (resp) {
		var all_good = true;
		try {
			$.each(resp[0], function (idx, el) {
				if(el !== "1") all_good = false;
			});
		} catch (e) {
			all_good = false;
		}
		$("img.wdfb_perms_waiting").remove();
		if (all_good) {
			$("p.wdfb_perms_not_granted, div.wdfb_perms_not_granted").hide();
			$("p.wdfb_perms_granted, div.wdfb_perms_granted").show();
		} else {
			$("p.wdfb_perms_not_granted, div.wdfb_perms_not_granted").show();
			$(".wdfb_grant_perms").show();
			$("p.wdfb_perms_granted, div.wdfb_perms_granted").hide();
		}
	});
}


$(window).load(function () {
	if (typeof FB == 'object') {
		FB.getLoginStatus(function (resp) {
			check_perms();
		});
	}
});

$(function () { 

	$(".wdfb_perms_root").append('<img src="' + _wdfb_root_url + '/img/waiting.gif" class="wdfb_perms_waiting" />').show();
	$(".wdfb_grant_perms, .wdfb_perms_granted, .wdfb_perms_not_granted").hide();
		
	$(".wdfb_grant_perms").click(function () { 
		var $me = $(this);
		var perms = $me.attr("data-wdfb_perms"); 
		var locale = $me.attr("data-wdfb_locale");

		FB.login(function () {
			window.location.href = window.location.href;
		}, {
			"scope": perms
		});
		return false; 
	}); 
	
}); 
})(jQuery);