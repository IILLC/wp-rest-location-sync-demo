<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles remote API requests through the WordPress HTTP API.
 */
class IILLC_WPRLSD_API_Client {

	/**
	 * Fetch a single location payload.
	 *
	 * The demo defaults to a bundled JSON fixture so the repository is fully
	 * self-contained. Filters allow the same client to be pointed at a real API
	 * later without changing the sync architecture.
	 *
	 * @param string $external_id External campsite/location ID.
	 * @return array|WP_Error
	 */
	public function fetch_location( $external_id ) {
		$external_id = sanitize_text_field( $external_id );
		$source_mode = (string) apply_filters( 'iillc_wprlsd_data_source_mode', 'fixture' );

		if ( 'remote' !== $source_mode ) {
			return $this->get_fixture_location( $external_id );
		}

		return $this->get_remote_location( $external_id );
	}

	/**
	 * Load one location from the bundled JSON fixture.
	 *
	 * @param string $external_id External ID.
	 * @return array|WP_Error
	 */
	private function get_fixture_location( $external_id ) {
		$fixture_path = (string) apply_filters(
			'iillc_wprlsd_fixture_path',
			IILLC_WPRLSD_PATH . 'data/sample-locations.json'
		);

		if ( ! file_exists( $fixture_path ) || ! is_readable( $fixture_path ) ) {
			return new WP_Error( 'iillc_wprlsd_missing_fixture', 'Demo fixture file could not be read.' );
		}

		$contents = file_get_contents( $fixture_path );

		if ( false === $contents ) {
			return new WP_Error( 'iillc_wprlsd_fixture_read_failed', 'Demo fixture file could not be loaded.' );
		}

		$data = json_decode( $contents, true );

		if ( JSON_ERROR_NONE !== json_last_error() || empty( $data['RECDATA'] ) || ! is_array( $data['RECDATA'] ) ) {
			return new WP_Error( 'iillc_wprlsd_invalid_fixture', 'Demo fixture JSON is invalid.' );
		}

		foreach ( $data['RECDATA'] as $location ) {
			if ( isset( $location['CampsiteID'] ) && $external_id === (string) $location['CampsiteID'] ) {
				return $location;
			}
		}

		return new WP_Error(
			'iillc_wprlsd_fixture_not_found',
			sprintf( 'No fixture location found for external ID %s.', $external_id )
		);
	}

	/**
	 * Fetch one location from a remote API.
	 *
	 * @param string $external_id External location ID.
	 * @return array|WP_Error
	 */
	private function get_remote_location( $external_id ) {
		$base_url = trailingslashit( apply_filters( 'iillc_wprlsd_api_base_url', 'https://example.com/wp-rest-location-sync-demo/v1/' ) );
		$api_key  = (string) apply_filters( 'iillc_wprlsd_api_key', '' );
		$url      = add_query_arg(
			array(
				'location_id' => rawurlencode( $external_id ),
			),
			$base_url . 'locations'
		);

		$args = array(
			'timeout' => 15,
			'headers' => array(),
		);

		if ( '' !== $api_key ) {
			$args['headers']['apikey'] = $api_key;
		}

		$response = wp_remote_get( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== (int) $code ) {
			return new WP_Error(
				'iillc_wprlsd_http_error',
				sprintf( 'Remote API returned HTTP %d.', (int) $code )
			);
		}

		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			return new WP_Error( 'iillc_wprlsd_invalid_json', 'Remote API response was not valid JSON.' );
		}

		/**
		 * Allow integrations to normalize list-style responses or alternate
		 * endpoint shapes before the mapper receives the payload.
		 */
		$data = apply_filters( 'iillc_wprlsd_api_response', $data, $external_id );

		if ( isset( $data['RECDATA'][0] ) && is_array( $data['RECDATA'][0] ) ) {
			return $data['RECDATA'][0];
		}

		return $data;
	}
}
