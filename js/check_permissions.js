(function ($) { 
	
function check_perms () {
	var perms_selector = jQuery(".wdfb_grant_perms:first");
	if (!perms_selector.length) return false;

	var perms = perms_selector.attr("data-wdfb_perms");
	perms = perms.split(',');

	FB.api('me/permissions', function (resp) {
		var all_good = true;
		try {
			var missing_perm = 0;
			$.each(perms, function (idx, el) {
				$.each(resp.data, function(index, val){
					if(val.permission !== el ) {
						return;
					}else{
						if( val.status !== 'granted' ){
							missing_perm++;
						}
					}
				});
			});
		} catch (e) {
			missing_perm = 1;
		}
		$("img.wdfb_perms_waiting").remove();
		if (!missing_perm) {
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