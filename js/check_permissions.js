(function ($) { 
$(function () { 
	
function check_perms () {
	var $perms = $(".wdfb_grant_perms:first");
	if (!$perms.length) return false;
	var query = "SELECT " + $perms.attr("wdfb:perms") + " FROM permissions WHERE uid=me()";
	FB.api({
		"method": "fql.query",
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

$(".wdfb_perms_root").show();
$(".wdfb_grant_perms, .wdfb_perms_granted, .wdfb_perms_not_granted").hide();
check_perms();	
	
$(".wdfb_grant_perms").click(function () { 
	var $me = $(this);
	var perms = $me.attr("wdfb:perms"); 
	var locale = $me.attr("wdfb:locale");
	FB.ui({ 
		"method": "permissions.request", 
		"perms": perms, 
		"locale": locale,
		"display": "iframe"
	}, function () {
		window.location.href = window.location.href;
	}); 
	return false; 
}); 
	
}); 
})(jQuery);