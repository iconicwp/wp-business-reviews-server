<?php
$user_access_token = wpbrs_get_facebook_user_access_token();
$wpbr_redirect     = isset( $_GET['wpbr_redirect'] ) ? sanitize_text_field( $_GET['wpbr_redirect'] ) : '';
wp_head();
?>

<style>
	html {
		margin: 0 !important;
	}
</style>

<header id="masthead" class="site-header" role="banner">
	<div class="site-header__container site-header__container--center">
		<span class="site-logo site-logo--header"><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo-wp-business-reviews.png' ); ?>" alt="<?php echo esc_attr__( 'WP Business Reviews', 'wpbr' ); ?>"></span>
	</div>
</header>

<form id="wpbr-facebook-user-access-token-form" action="<?php echo esc_attr( $wpbr_redirect ); ?>" method="post">
	<section class="band band--pad-v">
		<div class="layout layout--container">
			<div class="layout__item layout__item--island">
				<div class="card card--center">
					<div class="card__body">
						<h2 class="card__heading"><?php echo esc_html__( 'You\'re Connected!', 'wp-business-reviews-server' ); ?></h2>
						<p class="card__description"><?php echo esc_html__( 'Thank you for connecting to Facebook. You will be redirected to plugin settings in a moment.', 'wp-business-reviews-server' ); ?></p>
						<input type="hidden" name="wpbr_facebook_user_token" value="<?php echo esc_attr( $user_access_token ); ?>">
						<input type="submit" class="button button--primary button--x-large" value="<?php esc_html_e( 'Return to Plugin Settings', 'wp-business-reviews-server' ) ?>">
					</div>
				</div>
			</div>
		</div>
	</section>
</form>

<script>
	setTimeout( function() { document.getElementById( 'wpbr-facebook-user-access-token-form' ).submit(); }, 3000 );
</script>
