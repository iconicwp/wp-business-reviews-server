<?php
/**
 * Plugin Name: WP Business Reviews Server
 * Plugin URI:  https://wpbusinessreviews.com
 * Description: The WP Business Reviews Server plugin provides authentication for platforms in the WP Business Reviews Client plugin.
 * Version:     0.1.1
 * Author:      Team Impress
 * Author URI:  https://impress.org
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-business-reviews-server
 * Domain Path: /languages
 */

// Include Trustpilot API proxy
require_once __DIR__ . '/includes/trust-pilot-proxy.php';

define( 'WPBRS_FACEBOOK_GRAPH_API_VERSION', 'v11.0' );

if (
	isset( $_GET['wpbr_redirect'] )
	&& session_status() === PHP_SESSION_NONE
) {
	session_start();
}

require_once __DIR__ . '/vendor/autoload.php';

// Ensure rewrite rules are only flushed on activation/deactivation.
register_activation_hook( __FILE__, 'wpbrs_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/**
 * Fires on plugin activation.
 *
 * @since 0.1.0
 */
function wpbrs_activate() {
	wpbrs_add_facebook_token_rewrite();
	flush_rewrite_rules();
}

/**
 * Adds rewrite rules for handling Facebook token URLs.
 *
 * @since 0.1.0
 */
function wpbrs_add_facebook_token_rewrite() {
	add_rewrite_tag( '%facebook-token%', '([^&]+)' );
	add_rewrite_rule( '^facebook-token/([^/]*)/?', 'index.php?facebook-token=$matches[1]', 'top' );
}

add_action( 'init', 'wpbrs_add_facebook_token_rewrite' );

/**
 * Customizes template that is displayed upon receiving Facebook token.
 *
 * This template appears after a redirect from the Facebook confirmation screen
 * where user permissions are granted.
 *
 * @since 0.1.0
 */
function wpbrs_include_facebook_token_response_template( $template ) {
	if ( 'response' !== get_query_var( 'facebook-token' ) ) {
		return $template;
	}

	if ( isset( $_REQUEST['error'] ) ) {
		$redirect = isset( $_GET['wpbr_redirect'] ) ? sanitize_text_field( wp_unslash( $_GET['wpbr_redirect'] ) ) : '';

		if ( $redirect ) {
			wp_redirect( $redirect );
			exit;
		} else {
			wp_die( __( 'Sorry, an error occurred while connecting to Facebook and a redirect could not be found.', 'wp-business-reviews-server' ) );
		}
	}

	$custom_template = plugin_dir_path( __FILE__ ) . '/views/' . 'facebook-token-response-template.php';

	if ( file_exists( $custom_template ) ) {
		return $custom_template;
	}
}

add_filter( 'template_include', 'wpbrs_include_facebook_token_response_template' );

/**
 * Filters the allowed hosts that can be safely redirected.
 *
 * @since 0.1.0
 */
function wpbrs_filter_allowed_redirect_hosts( $content ) {
	$content[] = 'www.facebook.com';

	return $content;
}

add_filter( 'allowed_redirect_hosts', 'wpbrs_filter_allowed_redirect_hosts' );

/**
 * Redirects user to Facebook when a token is requested.
 *
 * This redirect is triggered when the following URL is requested:
 * `/facebook-token/request?wpbr_redirect={REDIRECT_TO_PLUGIN_SETTINGS}`
 *
 * @since 0.1.0
 */
function wpbrs_redirect_facebook_token_request() {

	// Bail out if query var or redirect parameter are not available.
	if (
		'request' !== get_query_var( 'facebook-token' )
		|| ! isset( $_GET['wpbr_redirect'] )
	) {
		return;
	}

	$redirect = sanitize_text_field( $_GET['wpbr_redirect'] );

	$fb = new \Facebook\Facebook(
		array(
			'app_id'                => WPBRS_FACEBOOK_APP_ID,
			'app_secret'            => WPBRS_FACEBOOK_APP_SECRET,
			'default_graph_version' => WPBRS_FACEBOOK_GRAPH_API_VERSION,
		)
	);

	$helper      = $fb->getRedirectLoginHelper();
	$url         = get_home_url() . '/facebook-token/response/?wpbr_redirect=' . urlencode( $redirect );
	$permissions = array( 'pages_show_list', 'pages_read_user_content', 'pages_read_engagement' );

	$login_url = $helper->getLoginUrl( $url, $permissions );

	wp_safe_redirect( $login_url );
	exit;
}

add_action( 'template_redirect', 'wpbrs_redirect_facebook_token_request' );

/**
 * Retrieves Facebook user access token.
 *
 * The Facebook PHP SDK is used to get the user access token based on a code
 * provided by Facebook in the redirect URL.
 *
 * @since 0.1.0
 * @see   https://developers.facebook.com/docs/php/howto/example_facebook_login
 */
function wpbrs_get_facebook_user_access_token() {
	$fb = new \Facebook\Facebook(
		array(
			'app_id'                => WPBRS_FACEBOOK_APP_ID,
			'app_secret'            => WPBRS_FACEBOOK_APP_SECRET,
			'default_graph_version' => WPBRS_FACEBOOK_GRAPH_API_VERSION,
		)
	);

	$helper = $fb->getRedirectLoginHelper();

	try {
		$access_token = $helper->getAccessToken();
	} catch ( Facebook\Exceptions\FacebookResponseException $e ) {
		// When Graph returns an error
		echo 'Graph returned an error: ' . $e->getMessage();
		exit;
	} catch ( Facebook\Exceptions\FacebookSDKException $e ) {
		// When validation fails or other local issues
		echo 'Facebook SDK returned an error: ' . $e->getMessage();
		exit;
	}

	if ( ! isset( $access_token ) ) {
		if ( $helper->getError() ) {
			header( 'HTTP/1.0 401 Unauthorized' );
			echo 'Error: ' . $helper->getError() . "\n";
			echo 'Error Code: ' . $helper->getErrorCode() . "\n";
			echo 'Error Reason: ' . $helper->getErrorReason() . "\n";
			echo 'Error Description: ' . $helper->getErrorDescription() . "\n";
		} else {
			header( 'HTTP/1.0 400 Bad Request' );
			echo 'Bad request';
		}
		exit;
	}

	// The OAuth 2.0 client handler helps us manage access tokens
	$oauth2_client = $fb->getOAuth2Client();

	// Get the access token metadata.
	$token_metadata = $oauth2_client->debugToken( $access_token );

	error_log( print_r( 'Token Metadata:', true ) );
	error_log( print_r( $token_metadata, true ) );

	// Validate token (these will throw FacebookSDKException's when they fail).
	$token_metadata->validateAppId( WPBRS_FACEBOOK_APP_ID ); // Replace {app-id} with your app id
	$token_metadata->validateExpiration();

	if ( ! $access_token->isLongLived() ) {
		// Exchanges a short-lived access token for a long-lived one
		try {
			$access_token = $oauth2_client->getLongLivedAccessToken( $access_token );
		} catch ( Facebook\Exceptions\FacebookSDKException $e ) {
			echo '<p>Error getting long-lived access token: ' . $helper->getMessage() . "</p>\n\n";
			exit;
		}
	}

	return $access_token->getValue();
}
