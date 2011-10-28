<?php get_header(); ?>

<?php
$redirect_url = is_multisite() ?
	'/wp-signup.php?action=register&fb_register=1'
	:
	'/wp-login.php?action=register&fb_register=1'
;
?>

	<h2>Register with Facebook</h2>

<?php foreach ($errors as $error) { ?>
	<?php $error = is_array($error) ? array_reduce($error, create_function('$val,$el', 'return "$val <br />$el";')) : $error; ?>
	<div class="error fade"><p><?php echo $error; ?></p></div>
<?php } ?>

<div style="margin-top:2em">

<iframe src="http://www.facebook.com/plugins/registration.php?
             client_id=<?php echo $this->data->get_option('wdfb_api', 'app_key');?>&
             redirect_uri=<?php echo urlencode(site_url($redirect_url));?>&
             fields=<?php echo wdfb_get_registration_fields();?>"
        scrolling="auto"
        frameborder="no"
        style="border:none"
        allowTransparency="true"
        width="100%"
        height="530">
</iframe>

</div>

<?php //if ($this->data->get_option('wdfb_connect', 'force_facebook_registration')) get_footer(); ?>
<?php get_footer(); ?>