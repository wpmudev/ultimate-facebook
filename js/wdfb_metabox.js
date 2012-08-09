(function ($) {

function remove_repeatable () {
	var $me = $(this);
	if ($me.parents(".wdfb_metabox_field").find(".wdfb_repeatable").length <= 1) return false;
	$me.parents(".wdfb_repeatable").remove();
	return false;
}

$(function () {

/* ----- Metabox element initialization ----- */

$(".wdfb_metabox_field_trigger").click(function () {
	var $me = $(this);
	var $field = $me.parents(".wdfb_metabox_container").find(".wdfb_metabox_field");
	if ($field.is(":visible")) {
		$field.hide();
		$me.removeClass("wdfb_metabox_field_trigger-active");
	} else {
		$field.show();
		$field.find(":input:visible:first").focus();
		$me.addClass("wdfb_metabox_field_trigger-active");
	}

	return false;
});
$(".wdfb_metabox_field").each(function () {
	var $me = $(this);
	$me.find(":input").each(function () {
		if ('' != $(this).val()) {
			$me.show();
			$me.parents(".wdfb_metabox_container").find(".wdfb_metabox_field_trigger").addClass("wdfb_metabox_field_trigger-active");
			return false;
		}
	});
});

/* ----- Repeatables ----- */

	$(".wdfb_repeatable_trigger").click(function () {
		var type = $(this).attr('data-type');
		if (!type) return false;

		var $src = $(".wdfb_repeatable_" + type + ":first");
		if (!$src.length) return false;
		var $clone = $src.clone();
		
		$clone.find("input").each(function () {
			var $field = $(this);
			$field.attr("name", $field.attr("name").replace(/\[\d*?\]/, "[]")).val("");
		});
		$clone.find(".wdfb_repeatable_remove").unbind("click").click(remove_repeatable);

		$(".wdfb_repeatable_" + type + ":last").after($clone);
		return false;
	});

	$(".wdfb_repeatable_remove").click(remove_repeatable);
});
})(jQuery);