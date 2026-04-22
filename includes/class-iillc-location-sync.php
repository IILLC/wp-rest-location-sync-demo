<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orchestrates remote fetch, mapping, and persistence.
 */
class IILLC_WPRLSD_Location_Sync {

	/**
	 * @var IILLC_WPRLSD_API_Client
	 */
	private $client;

	/**
	 * @var IILLC_WPRLSD_Location_Mapper
	 */
	private $mapper;

	/**
	 * @var IILLC_WPRLSD_Location_Repository
	 */
	private $repository;

	/**
	 * @param IILLC_WPRLSD_API_Client          $client API client.
	 * @param IILLC_WPRLSD_Location_Mapper     $mapper Mapper.
	 * @param IILLC_WPRLSD_Location_Repository $repository Repository.
	 */
	public function __construct( $client, $mapper, $repository ) {
		$this->client     = $client;
		$this->mapper     = $mapper;
		$this->repository = $repository;
	}

	/**
	 * Sync a bounded set of configured sample locations.
	 *
	 * @return array
	 */
	public function sync_sample_locations() {
		$results = array(
			'success' => true,
			'synced'  => 0,
			'errors'  => array(),
		);

		foreach ( $this->repository->get_sample_external_ids() as $external_id ) {
			$result = $this->sync_location( $external_id );

			if ( is_wp_error( $result ) ) {
				$results['success']  = false;
				$results['errors'][] = $external_id . ': ' . $result->get_error_message();
				continue;
			}

			$results['synced']++;
		}

		$this->repository->save_sync_status( $results );

		return $results;
	}

	/**
	 * Sync one location by external ID.
	 *
	 * @param string $external_id External ID.
	 * @return int|WP_Error
	 */
	public function sync_location( $external_id ) {
		$payload = $this->client->fetch_location( $external_id );

		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$normalized = $this->mapper->map_location( $payload );

		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		return $this->repository->upsert_location( $normalized );
	}
}
