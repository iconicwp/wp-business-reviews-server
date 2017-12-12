<?php
$token         = wpbrs_get_facebook_user_access_token();
$wpbr_redirect = isset( $_GET['wpbr_redirect'] ) ? sanitize_text_field( $_GET['wpbr_redirect'] ): '';
get_header();
?>

<form action="<?php echo esc_attr( $wpbr_redirect ); ?>" method="post">
	<label for="wpbr_facebook_token"><?php esc_html_e( 'Token', 'wp-business-reviews-server' ) ?></label>
	<input id="wpbr_facebook_token" type="text" name="wpbr_facebook_token" value="<?php echo esc_attr( $token ); ?>">
	<input type="submit" value="<?php esc_html_e( 'Return to WP Business Reviews Settings', 'wp-business-reviews-server' ) ?>">
</form>

<?php get_footer() ?>
