<?php
$user_access_token = wpbrs_get_facebook_user_access_token();
$wpbr_redirect     = isset( $_GET['wpbr_redirect'] ) ? sanitize_text_field( $_GET['wpbr_redirect'] ): '';
get_header();
?>

<form id="wpbr-facebook-user-access-token-form" action="<?php echo esc_attr( $wpbr_redirect ); ?>" method="post">
	<input type="hidden" name="wpbr_facebook_user_access_token" value="<?php echo esc_attr( $user_access_token ); ?>">
	<input type="submit" value="<?php esc_html_e( 'Return to WP Business Reviews Settings', 'wp-business-reviews-server' ) ?>">
</form>

<script>
	document.getElementById( 'wpbr-facebook-user-access-token-form' ).submit();
</script>

<?php get_footer() ?>
