<div class="wrap">

<h2><?php _e('Ultimate Facebook Shortcodes', 'wdfb');?></h2>

<h3>"Like" button shortcode</h3>
<p><em>Tag:</em> <code>[wdfb_like_button]</code></p>
<p><em>Attributes:</em> none</p>
<p>
	<em>Example:</em>
	<code>[wdfb_like_button]</code> - will create a Facebook Like/Send button with the settings you set up <a href="?page=wdfb">here</a>.
</p>
<p><strong>Note:</strong> you have to <a href="?page=wdfb">allow</a> usage of <em>Facebook "Like/Send" button</em> for this shortcode to have any effect. If you dislike the default button placement options, you can set the placement to "Manual" and use this shortcode in your posts to insert the button wherever you wish.</p>

<h3>Connect shortcode</h3>
<p><em>Tag:</em> <code>[wdfb_connect]</code></p>
<p>
	<em>Attributes:</em>
	<ul>
		<li><code>avatar_size</code> - <em>optional.<em> Size of the avatar shown, in pixels. Default size is <code>32</code>.</li>
		<li>Any text you supply between <code>[wdfb_connect]</code> and <code>[/wdfb_connect]</code> tags will be used as button text.</li>
	</ul>
</p>
<p>
	<em>Examples:</em>
	<ul>
		<li><code>[wdfb_connect]</code> - will create a Facebook Connect button with default text (&quot;Log in with Facebook&quot;)</li>
		<li><code>[wdfb_connect avatar_size="98"]</code> - will create a Facebook Connect button. Once logged in with Facebook, the displayed avatar will be 98px large.</li>
		<li><code>[wdfb_connect]Get in![/wdfb_connect]</code> - will create a a Facebook Connect button that says &quot;Get in!&quot;.</li>
	</ul>
</p>
<p><strong>Note:</strong> you have to <a href="?page=wdfb">allow</a> registering with Facebook in your plugin settings (under &quot;Facebook Connect&quot;) for this shortcode to work.</p>

<?php if ($this->data->get_option('wdfb_grant', 'allow_fb_photos_access')) { ?>
<h3>Album shortcode</h3>
<p><em>Tag:</em> <code>[wdfb_album]</code></p>
<p>
	<em>Attributes:</em>
	<ul>
		<li><code>id</code> - <strong>required.</strong> Valid Facebook album ID (e.g. <code>379473193359</code>)</li>
		<li><code>album_class</code> - <em>optional.</em> HTML class to be assigned to album wrapper in output.</li>
		<li><code>photo_class</code> - <em>optional.</em> HTML class to be assigned to each photo in output.</li>
		<li><code>columns</code> - <em>optional.</em> Show this many photos per row.</li>
		<li><code>photo_width</code> - <em>optional.</em> Width of your images in the album.</li>
		<li><code>photo_height</code> - <em>optional.</em> Height of your images in the album.</li>
		<li><code>crop</code> - <em>optional.</em> Crop images to fit height.</li>
		<li><code>limit</code> - <em>optional.</em> Limit output to this many photos.</li>
	</ul>
</p>
<p>
	<em>Examples:</em>
	<ul>
		<li><code>[wdfb_album id="379473193359"]</code> - default album output, rows will fill the width of your page.</li>
		<li><code>[wdfb_album id="379473193359" photo_class="thickbox" columns="5"]</code> - album with 5 images per row, images will open in Thickbox.</li>
		<li><code>[wdfb_album id="379473193359" photo_class="thickbox" columns="5" photo_height="50" crop="true"]</code> - same as above, but image height will be cropped to better fit the row height.</li>
	</ul>
</p>
<?php } ?>

</div>