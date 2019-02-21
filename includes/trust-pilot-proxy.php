<?php
/**
 * Trust Pilot proxy server
 *
 * Relays API calls to and fro.
 *
 * @since reflects
 */

class WPBR_Trustpilot_API {


	/**
	 * Is this a valid request?
	 *
	 * @var bool
	 * @access private
	 * @since  1.1
	 */
	private $is_valid_request = false;

	/**
	 * Instantiates the Trust_Pilot object.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Trustpilot API key.
		$this->key = 'lypqGFDgARolJ1xNbqx98kWoXrTxWDvG';

		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_action( 'wp', array( $this, 'process_query' ) );
	}

	/**
	 * Registers a new rewrite endpoint for accessing the API
	 *
	 * @access public
	 *
	 * @since  1.1
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( 'tp-api', EP_ALL );
	}

	/**
	 * Listens for the API and then processes the API requests
	 *
	 * @access public
	 * @global $wp_query
	 * @since  1.1
	 * @return void
	 */
	public function process_query() {

		global $wp_query;

		// Check for give-api var. Get out if not present
		if ( ! isset ( $wp_query->query_vars['tp-api'] ) ) {
			return;
		}

		/**
		 * Begin API requests.
		 */
		status_header( 200 );
		header( 'Content-Type: application/json' );

		// Check license validation before passing to API.
		$license_key = isset( $_GET['license'] ) ? sanitize_text_field( $_GET['license'] ) : '';

		// Save status check in transient.
		if ( false === ( $license_status = get_transient( 'tp_api_' . $license_key ) ) ) {
			$license_status = edd_software_licensing()->get_license_status( $license_key );
			set_transient( 'tp_api_' . $license_key, $license_status, HOUR_IN_SECONDS );
		}

		if ( 'active' === $license_status ) {

			// If doing a Business ID lookup:
			$domain = isset( $_GET['domain'] ) ? $_GET['domain'] : '';
			if ( ! empty( $domain ) ) {

				$business_id   = $this->search_review_source( $domain );
				$review_source = $this->get_review_source( $business_id );
				$web_links     = $this->get_web_links( $business_id );

				$response = array_merge( $business_id, $review_source, $web_links );
				echo json_encode( $response );


			}
//			$tp_profile     = $this->get_review_source( $this->key );
//			$public_profile = $this->get_public_profile( $this->key );
//			$web_links      = $this->get_web_links( $this->key );
//			$profile_img    = $this->get_logo_url( $this->key );


		}

		exit;

	}

	/**
	 * Retrieves the platform status based on a test request.
	 *
	 * @since 1.3.0
	 *
	 * @return string The platform status.
	 */
	public function get_platform_status() {

		// Now check API response.
		$response = $this->search_review_source( 'trustpilot.com' );

		if ( is_wp_error( $response ) ) {
			return 'disconnected';
		}

		return 'connected';
	}

	/**
	 * Searches review sources based on search terms and location.
	 *
	 * @since 1.3.0
	 *
	 * @param string $domain The domain of the business on Trustpilot.
	 * @param string $location Unused for Trustpilot, search is based on URL.
	 *
	 * @return array|\WP_Error Associative array containing response or WP_Error
	 *                        if response structure is invalid.
	 */
	public function search_review_source( $domain, $location = '' ) {
		$url = add_query_arg(
			array(
				'name'   => $domain,
				'apikey' => $this->key,
			),
			'https://api.trustpilot.com/v1/business-units/find'
		);

		$response = $this->get( $url, array() );

		if ( ! isset( $response['id'] ) ) {
			return new \WP_Error( 'wpbr_no_review_sources', __( 'No results found. Enter the primary domain of your business as it appears on Trustpilot for best results.', 'wp-business-reviews' ) );
		}

		return $response;
	}

	/**
	 * Retrieves review source public details based on Trustpilot business ID.
	 *
	 * @see https://developers.trustpilot.com/business-units-api#get-public-business-unit
	 *
	 * @since 1.3.0
	 *
	 * @param string $id The domain of the business.
	 *
	 * @return array|\WP_Error Associative array containing response or WP_Error
	 *                        if response structure is invalid.
	 */
	public function get_public_profile( $id ) {
		$url = add_query_arg(
			array(
				'apikey' => $this->key,
			),
			"https://api.trustpilot.com/v1/business-units/{$id}"
		);


		$response = $this->get( $url, array() );

		return $response;
	}

	/**
	 * Retrieves review source web links details based on Trustpilot business ID.
	 *
	 * @see https://developers.trustpilot.com/business-units-api#get-a-business-unit's-web-links
	 *
	 * @since 1.3.0
	 *
	 * @param string $id The domain of the business.
	 *
	 * @return array|\WP_Error Associative array containing response or WP_Error
	 *                        if response structure is invalid.
	 */
	public function get_web_links( $id ) {
		$url = add_query_arg(
			array(
				'apikey' => $this->key,
				// For some reason TP uses "en-US" rather than the "en_US" WP returns.
				'locale' => str_replace( '_', '-', get_locale() ),
			),
			"https://api.trustpilot.com/v1/business-units/{$id}/web-links"
		);

		$response = $this->get( $url, array() );

		return $response;
	}


	/**
	 * Returns the business logo.
	 *
	 * @see https://developers.trustpilot.com/business-units-api#get-business-unit-company-logo
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public function get_logo_url( $id ) {
		$url = add_query_arg(
			array(
				'apikey' => $this->key,
			),
			"https://api.trustpilot.com/v1/business-units/{$id}/images/logo"
		);

		$response = $this->get( $url, array() );

		return $response;
	}

	/**
	 * Retrieves review source details based on Trustpilot business ID.
	 *
	 * @see https://developers.trustpilot.com/business-units-api#get-a-business-unit's-reviews
	 *
	 * @since 1.3.0
	 *
	 * @param string $id The Trustpilot Business ID.
	 *
	 * @return array
	 */
	public function get_review_source( $id ) {
		$url = add_query_arg(
			array(
				'apikey' => $this->key,
			),
			"https://api.trustpilot.com/v1/business-units/{$id}/profileinfo"
		);

		$response = $this->get( $url, array() );
		// Ensure we pass along the ID for normalizer.
		$response['id'] = $id;

		return $response;
	}

	/**
	 * Retrieves reviews based on Yelp business ID.
	 *
	 * @since 1.3.0
	 *
	 * @param string $id The Yelp business ID.
	 *
	 * @return array|\WP_Error Associative array containing response or WP_Error
	 *                        if response structure is invalid.
	 */
	public function get_reviews( $id ) {

		$url = add_query_arg(
			array(
				'apikey'  => $this->key,
				'perPage' => 100, // Max allowed
			),
			"https://api.trustpilot.com/v1/business-units/{$id}/reviews"
		);

		$response = $this->get( $url, array() );

		if ( ! isset( $response['reviews'] ) ) {
			return new \WP_Error( 'wpbr_no_reviews', __( 'No reviews found. Although reviews may exist on the platform, none were returned from the platform API.', 'wp-business-reviews' ) );
		}

		$reviews = $response['reviews'];

		return $reviews;
	}

	/**
	 * Retrieves a response from a safe HTTP request using the GET method.
	 *
	 * @since 1.0.0
	 *
	 * @see wp_safe_remote_get()
	 *
	 * @param string $url Site URL to retrieve.
	 * @param array $args Request arguments.
	 *
	 * @return array Associative array containing the response body.
	 */
	public function get( $url, array $args = array() ) {
		$response = wp_safe_remote_get( $url, $args );

		return $this->process_response( $response );
	}

	/**
	 * Validates and decodes the response body.
	 *
	 * @param mixed $response The raw response.
	 *
	 * @return mixed Associative array of the response body.
	 */
	private function process_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( isset( $body['body'] ) ) {
			$body = $body['body'];
		}

		return json_decode( $body, true );
	}

}

new WPBR_Trustpilot_API();
