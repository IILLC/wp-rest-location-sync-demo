<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encapsulates storage of synced locations using a CPT plus post meta.
 */
class IILLC_WPRLSD_Location_Repository {

	/**
	 * Register the demo post type.
	 */
	public function register_post_type() {
		register_post_type(
			'iillc_location',
			array(
				'labels'       => array(
					'name'          => 'Synced Locations',
					'singular_name' => 'Synced Location',
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => false,
				'supports'     => array( 'title' ),
			)
		);
	}

	/**
	 * Upsert a location by external ID.
	 *
	 * @param array $location_data Normalized location data.
	 * @return int|WP_Error
	 */
	public function upsert_location( array $location_data ) {
		$existing_id = $this->get_post_id_by_external_id( $location_data['external_id'] );

		$postarr = array(
			'post_type'   => 'iillc_location',
			'post_status' => 'publish',
			'post_title'  => $location_data['name'],
		);

		if ( $existing_id ) {
			$postarr['ID'] = $existing_id;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_iillc_external_id', $location_data['external_id'] );
		update_post_meta( $post_id, '_iillc_facility_id', $location_data['facility_id'] );
		update_post_meta( $post_id, '_iillc_type', $location_data['type'] );
		update_post_meta( $post_id, '_iillc_status', $location_data['status'] );
		update_post_meta( $post_id, '_iillc_accessible', (int) $location_data['accessible'] );
		update_post_meta( $post_id, '_iillc_latitude', $location_data['latitude'] );
		update_post_meta( $post_id, '_iillc_longitude', $location_data['longitude'] );
		update_post_meta( $post_id, '_iillc_last_remote_update', $location_data['last_remote_update'] );
		update_post_meta( $post_id, '_iillc_reservation_url', $location_data['reservation_url'] );
		update_post_meta( $post_id, '_iillc_equipment_summary', $location_data['equipment_summary'] );
		update_post_meta( $post_id, '_iillc_attributes_summary', $location_data['attributes_summary'] );
		update_post_meta( $post_id, '_iillc_primary_image_url', $location_data['primary_image_url'] );
		update_post_meta( $post_id, '_iillc_last_synced_at', current_time( 'mysql', true ) );

		return (int) $post_id;
	}

	/**
	 * Get a post ID from an external ID.
	 *
	 * @param string $external_id External location identifier.
	 * @return int
	 */
	public function get_post_id_by_external_id( $external_id ) {
		$posts = get_posts(
			array(
				'post_type'      => 'iillc_location',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_iillc_external_id',
				'meta_value'     => sanitize_text_field( $external_id ),
			)
		);

		return empty( $posts ) ? 0 : (int) $posts[0];
	}

	/**
	 * Determine whether a fallback on-visit refresh should run.
	 *
	 * @return bool
	 */
	public function is_stale() {
		$last_sync = get_option( 'iillc_wprlsd_last_sync_at', '' );

		if ( ! $last_sync ) {
			return true;
		}

		return ( time() - strtotime( $last_sync ) ) >= DAY_IN_SECONDS;
	}

	/**
	 * Persist the last sync status for reviewer visibility.
	 *
	 * @param array $status Sync status payload.
	 * @return void
	 */
	public function save_sync_status( array $status ) {
		update_option( 'iillc_wprlsd_last_sync_status', $status, false );
		update_option( 'iillc_wprlsd_last_sync_at', current_time( 'mysql', true ), false );
	}

	/**
	 * Get the latest sync status.
	 *
	 * @return array
	 */
	public function get_sync_status() {
		return (array) get_option( 'iillc_wprlsd_last_sync_status', array() );
	}

	/**
	 * Get a small set of configured sample IDs.
	 *
	 * Demo note: the repo intentionally syncs a bounded set rather than trying to
	 * mirror an entire external dataset.
	 *
	 * @return string[]
	 */
	public function get_sample_external_ids() {
		$ids = apply_filters(
			'iillc_wprlsd_sample_external_ids',
			array(
				'100001',
				'100002',
				'100003',
			)
		);

		return array_values( array_filter( array_map( 'strval', (array) $ids ) ) );
	}
}
