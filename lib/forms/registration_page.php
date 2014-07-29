<?php get_header(); ?>

<?php

$redirect_url = is_multisite() ? site_url( '/wp-signup.php?action=register&fb_register=1' ) : site_url( '/wp-login.php?action=register&fb_register=1' );
$redirect_url = apply_filters( 'wdfb_registration_redirect_url', $redirect_url );

$opts = Wdfb_OptionsRegistry::get_instance();

//Check if only fb registration is allowed
$fb_only = $opts->get_option( 'wdfb_connect', 'force_facebook_registration' ) && $opts->get_option( 'wdfb_connect', 'require_facebook_account' );

//Compose the facebook registration URL
$registration_url = WDFB_PROTOCOL . "www.facebook.com/plugins/registration.php";
if ( $fb_only ) {
	$registration_url = add_query_arg(
		array(
			'fb_only' => true
		),
		$registration_url
	);
}
//Add other parameters
$registration_url = add_query_arg(
	array(
		'client_id'    => $this->data->get_option( 'wdfb_api', 'app_key' ),
		'redirect_uri' => urlencode( $redirect_url ),
		'fields'       => wdfb_get_registration_fields(),
		'locale'       => wdfb_get_locale()
	),
	$registration_url
);
/**
 * Fires before the facebook registration form is loaded
 *
 * @since Ultimate Facebook 2.7.3
 */
do_action('wdfb_before_registration_form');
?>
	<div id="content" class="site-content">
		<div class="wdfb-registration-page">
			<h2><?php _e( 'Register with Facebook', 'wdfb' ); ?></h2><?php

			foreach ( $errors as $error ) {
				$error = is_array( $error ) ? array_reduce( $error, create_function( '$val,$el', 'return "$val <br />$el";' ) ) : $error; ?>
				<div class="error fade">
				<p><?php echo $error; ?></p>
				</div><?php
			} ?>

			<div style="margin-top:2em">

				<iframe src="<?php echo $registration_url; ?>"
					scrolling="auto"
					frameborder="no"
					style="border:none"
					allowTransparency="true"
					width="100%"
					height="530">
				</iframe>

			</div>
		</div>
	</div>  <!-- Close Content -->


<?php
/**
 * Fires after the facebook registration form is loaded
 *
 * @since Ultimate Facebook 2.7.3
 */
do_action('wdfb_after_registration_form'); ?>
<?php get_footer(); ?>