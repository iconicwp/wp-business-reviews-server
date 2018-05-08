<?php
$user_access_token = wpbrs_get_facebook_user_access_token();
$wpbr_redirect     = isset( $_GET['wpbr_redirect'] ) ? sanitize_text_field( $_GET['wpbr_redirect'] ) : '';
wp_head();
?>

<style>
	body {
		background-color: #ECF0F1;
	}

	#wpbr-facebook-user-access-token-form {
		background-color: white;
		border-radius: 0.5rem;
		box-shadow: 0 0.375rem 0.375rem rgba(44, 62, 80, 0.05);
		margin: 1.5rem auto;
		padding: 1.5rem;
		max-width: 600px;
		text-align: center;
	}

	#wpbr-facebook-user-access-token-form .button {
		padding: 10px 20px;
	}
</style>

<header id="masthead" class="site-header" role="banner">
	<div class="site-header__container site-header__container--center">
		<span class="site-logo site-logo--header"><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo-wp-business-reviews.png' ); ?>"
		                                               alt="<?php echo esc_attr__( 'WP Business Reviews', 'wpbr' ); ?>"></span>
	</div>
</header>
<section class="band band--pad-v">
	<form id="wpbr-facebook-user-access-token-form" action="<?php echo esc_attr( $wpbr_redirect ); ?>" method="post">
		<p>Thank you for connecting to Facebook!</p>
		<input type="hidden" name="wpbr_facebook_user_token" value="<?php echo esc_attr( $user_access_token ); ?>">
		<input type="submit" class="button" value="<?php esc_html_e( 'Return to WP Business Reviews Settings', 'wp-business-reviews-server' ) ?>">
	</form>
</section>


<script>
	setTimeout( function() { document.getElementById( 'wpbr-facebook-user-access-token-form' ).submit(); }, 3000 );
</script>
