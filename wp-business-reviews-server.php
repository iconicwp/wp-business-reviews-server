<?php
/**
 * Plugin Name: WP Business Reviews Server
 */
session_start();

require_once __DIR__ . '/vendor/autoload.php';

function wpbrs_rewrite_facebook_token_tag() {
	add_rewrite_tag( '%facebook-token%', '([^&]+)' );
}
add_action( 'init', 'wpbrs_rewrite_facebook_token_tag' );

function wpbrs_rewrite_facebook_token_rule() {
	add_rewrite_rule( '^facebook-token/([^/]*)/?', 'index.php?facebook-token=$matches[1]', 'top' );
	flush_rewrite_rules();
}
add_action( 'init', 'wpbrs_rewrite_facebook_token_rule' );

function wpbrs_include_facebook_token_response_template( $template ) {
	if ( 'response' === get_query_var( 'facebook-token' ) ) {
		$custom_template = plugin_dir_path( __FILE__ ) . '/views/' . 'facebook-token-response-template.php';

		if( file_exists( $custom_template ) ) {
			return $custom_template;
		}
	}

	return $template;
}
add_filter( 'template_include', 'wpbrs_include_facebook_token_response_template' );

function wpbrs_filter_allowed_redirect_hosts( $content ){
	$content[] = 'facebook.com';

	return $content;
}
add_filter( 'allowed_redirect_hosts' , 'wpbrs_filter_allowed_redirect_hosts' );

function wpbrs_redirect_facebook_token_request() {
	if ( 'request' !== get_query_var( 'facebook-token' ) ) {
		return;
	}

	$redirect = isset( $_GET['wpbr_redirect'] ) ? sanitize_text_field( $_GET['wpbr_redirect'] ) : '';

	$fb = new \Facebook\Facebook( [
		'app_id'                => WPBRS_FACEBOOK_APP_ID,
		'app_secret'            => WPBRS_FACEBOOK_APP_SECRET,
		'default_graph_version' => 'v2.11',
	] );

	$helper = $fb->getRedirectLoginHelper();
	$permissions = ['manage_pages'];

	$url = 'http://wpbr-facebook-server.dev/facebook-token/response/?wpbr_redirect=' . urlencode( $redirect );
	$loginUrl = $helper->getLoginUrl( $url, $permissions );

	wp_safe_redirect( $loginUrl );
	exit;
}
add_action( 'template_redirect', 'wpbrs_redirect_facebook_token_request' );

function my_plugin_menu() {
	add_options_page(
		'Request Page',
		'Request Page',
		'manage_options',
		'wpbr_facebook_server_request',
		'wpbr_facebook_server_request'
	);

	add_options_page(
		'Response Page',
		'Response Page',
		'manage_options',
		'wpbr_facebook_server_response',
		'wpbr_facebook_server_response'
	);
}
add_action( 'admin_menu', 'my_plugin_menu' );

function wpbr_facebook_server_request() {
	$fb = new \Facebook\Facebook([
		'app_id'                => WPBRS_FACEBOOK_APP_ID,
		'app_secret'            => WPBRS_FACEBOOK_APP_SECRET,
		'default_graph_version' => 'v2.11',
	]);

	$helper = $fb->getRedirectLoginHelper();
	$permissions = ['manage_pages'];
	$loginUrl = $helper->getLoginUrl( 'http://wpbr-facebook-server.dev/wp-admin/options-general.php?page=wpbr_facebook_server_response', $permissions );

	echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';

	var_dump($_SESSION);
}

function wpbr_facebook_server_response() {
	$fb = new \Facebook\Facebook([
		'app_id'                => WPBRS_FACEBOOK_APP_ID,
		'app_secret'            => WPBRS_FACEBOOK_APP_SECRET,
		'default_graph_version' => 'v2.11',
	]);

	$helper = $fb->getRedirectLoginHelper();

	try {
		$accessToken = $helper->getAccessToken();
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		// When Graph returns an error
		echo 'Graph returned an error: ' . $e->getMessage();
		exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		// When validation fails or other local issues
		echo 'Facebook SDK returned an error: ' . $e->getMessage();
		exit;
	}

	if (! isset($accessToken)) {
		if ($helper->getError()) {
		header('HTTP/1.0 401 Unauthorized');
		echo "Error: " . $helper->getError() . "\n";
		echo "Error Code: " . $helper->getErrorCode() . "\n";
		echo "Error Reason: " . $helper->getErrorReason() . "\n";
		echo "Error Description: " . $helper->getErrorDescription() . "\n";
		} else {
		header('HTTP/1.0 400 Bad Request');
		echo 'Bad request';
		}
		exit;
	}

	// Logged in
	echo '<h3>Access Token</h3>';
	// var_dump($accessToken->getValue());
	var_dump($accessToken);

	// The OAuth 2.0 client handler helps us manage access tokens
	$oAuth2Client = $fb->getOAuth2Client();

	// Get the access token metadata from /debug_token
	$tokenMetadata = $oAuth2Client->debugToken($accessToken);
	echo '<h3>Metadata</h3>';

	// Validation (these will throw FacebookSDKException's when they fail)
	$tokenMetadata->validateAppId(WPBRS_FACEBOOK_APP_ID); // Replace {app-id} with your app id
	// If you know the user ID this access token belongs to, you can validate it here
	//$tokenMetadata->validateUserId('123');
	$tokenMetadata->validateExpiration();

	if (! $accessToken->isLongLived()) {
		// Exchanges a short-lived access token for a long-lived one
		try {
			$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
			exit;
		}

		echo '<h3>Long-lived</h3>';
		var_dump($accessToken->getValue());
	}

	$_SESSION['fb_access_token'] = (string) $accessToken;

	// User is logged in with a long-lived access token.
	// You can redirect them to a members-only page.
	//header('Location: https://example.com/members.php');

	var_dump($tokenMetadata);

	$accounts = $fb->sendRequest('GET', '/me/accounts', [], $accessToken, 'eTag', 'v2.11');

	echo '<h2>Accounts</h2>';
	echo '<pre>' . print_r($accounts, true) . '</pre>';
}

