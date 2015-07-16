(function ($) {
	$(function () {

		var $parent;

		function parseAlbumIdHref(href) {
			return parseInt(href.substr(1), 10);
		}

		function createAlbumsMarkup(data, $type) {
			var status = parseInt(data.status, 10);
			if (!status) {
				$("#wdfb_album_container").html(
					"Please log in to your FB account first"
				);
				return false;
			}
			var html = '<ul>';
			$.each(data.albums, function (idx, album) {
				album.count = ("count" in album) ? album.count : 0;
				if( typeof $type !== 'undefined' && $type == 'public') {
					$show_public = true;
				}else{
					$show_public = false;
				}
				if( !$show_public ) {
					html += '<li>';

					//Show cover photo, if available
					if( album.cover ) {
						html += '<img src="' + album.cover.picture + '" alt="' + album.name + '" style="display: block;" />';
					}
					html += album.name + ' (' + album.count + ') <br />';
					html += '<a class="wdfb_insert_album" href="#' + album.id + '" data-name="' + album.name + '">' + l10nWdfbEditor.insert_album + '</a>';

					html += '</li>';
				}else if( album.privacy == 'everyone' ){
					html += '<li>';

					//Show cover photo, if available
					if( album.cover ) {
						html += '<img src="' + album.cover.picture + '" alt="' + album.name + '" style="display: block;"/>';
					}
					html += album.name + ' (' + album.count + ') <br />';
					html += '<a class="wdfb_insert_album" href="#' + album.id + '" data-name="' + album.name + '">' + l10nWdfbEditor.insert_album + '</a>';

					html += '</li>';
				}
			});
			html += '</ul>';
			$("#wdfb_album_container").html(html);
			jQuery('#wdfb_album_container ul li').css({'display': 'inline-block', 'padding': '5px', 'width': '200px' });
		}

		function loadAlbums($type) {
			$("#wdfb_album_container").html(l10nWdfbEditor.please_wait + ' <img src="' + _wdfb_root_url + '/img/waiting.gif">');
			$.post(ajaxurl, {"action": "wdfb_list_fb_albums"}, function (response) {
				createAlbumsMarkup(response, $type);
			});
		}

		function insertAlbum($me) {
			var albumId = parseAlbumIdHref($me.attr('href'));
			$parent.find('input:text').val(albumId);
			var title = $parent.parent().find('.wdfb_album_title input');
			if( !title.val() ) {
				title.val($me.data('name'));
			}
			tb_remove();
			return false;
		}


		/**
		 * Inserts the map marker into editor.
		 * Supports TinyMCE and regular editor (textarea).
		 */
		function updateEditorContents(markup) {
			if (window.tinyMCE && !$('#content').is(':visible')) window.tinyMCE.execCommand("mceInsertContent", true, markup);
			else insertAtCursor($("#content").get(0), markup);
		}

		/**
		 * Inserts map marker into regular (textarea) editor.
		 */
		function insertAtCursor(fld, text) {
			// IE
			if (document.selection && !window.opera) {
				fld.focus();
				sel = window.opener.document.selection.createRange();
				sel.text = text;
			}
			// Rest
			else if (fld.selectionStart || fld.selectionStart == '0') {
				var startPos = fld.selectionStart;
				var endPos = fld.selectionEnd;
				fld.value = fld.value.substring(0, startPos) +
				text +
				fld.value.substring(endPos, fld.value.length)
				;
			} else {
				fld.value += text;
			}
		}

		function openWidgetEditor() {
			var height = $(window).height(), adminbar_height = 0;
			if ($('body.admin-bar').length) adminbar_height = 28;
			height = height - 85 - adminbar_height;
			tb_show(l10nWdfbEditor.add_fb_photo, '#TB_inline?width=640&height=' + height + '&inlineId=wdfb_album_root_container');
			loadAlbums('public');
			return false;
		}

		function init_ui() {
			// Create the needed editor container HTML
			$('body').append('<div id="wdfb_album_root_container" style="display:none"><div id="wdfb_album_container"></div></div>');

			// --- Bind events ---

			$(document).on('click', ".wdfb_grant_events_perms", function () {
				$parent = $(this).parents('.wdfb_album_widget_select_album');
				openWidgetEditor();
				return false;
			});
			jQuery('body').on('click', '.wdfb_widget_open_editor', function(){
				$parent = $(this).parents('.wdfb_album_widget_select_album');
				openWidgetEditor();
				return false;
			});

			$(document).on('click', 'a.wdfb_insert_album', function () {
				insertAlbum($(this));
			});
		}

		function init() {
			if (typeof FB != 'object') return false; // Don't even bother
			perms = new Array();
			perms.push('user_photos');

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
					$('.wdfb_album_widget_select_album').html(
						'<div class="error below-h2">' + l10nWdfbEditor.insuficient_perms + '<br />' +
						'<a class="wdfb_grant_albums_perms" href="#" >' + l10nWdfbEditor.grant_perms + '</a>' +
						'</div>'
					);
					$(document).on("click", ".wdfb_grant_albums_perms", function () {
						var $me = $(this);
						var locale = $me.attr("data-wdfb_locale");
						/*
						 FB.ui({
						 "method": "permissions.request",
						 "perms": 'user_photos',
						 "display": "iframe"
						 }, function () {
						 window.location.reload(true);
						 });
						 */
						FB.login(function () {
							window.location.reload(true);
						}, {
							"scope": 'user_photos'
						});
						return false;
					});
					$(document).on("change", ".wdfb_fb_open_wrapper input", function () {
						var $me = $(this);
						if( $me.attr('checked') ) {
							jQuery('.wdfb_thickbox_wrapper input').removeAttr('checked');

						}
					});
					$(document).on("change", ".wdfb_thickbox_wrapper input", function () {
						var $me = $(this);
						if( $me.attr('checked') ) {
							$('.wdfb_fb_open_wrapper input').removeAttr("checked");
						}
					});
				}
			});
		}

		FB.getLoginStatus(function (resp) {
			init();
		});

	});
})(jQuery);