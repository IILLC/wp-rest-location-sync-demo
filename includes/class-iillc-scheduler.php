<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedules a daily sync and provides a lightweight frontend fallback.
 */
class IILLC_WPRLSD_Scheduler {

	/**
	 * @var IILLC_WPRLSD_Location_Sync
	 */
	private $sync_service;

	/**
	 * @var IILLC_WPRLSD_Location_Repository
	 */
	private $repository;

	/**
	 * @param IILLC_WPRLSD_Location_Sync       $sync_service Sync service.
	 * @param IILLC_WPRLSD_Location_Repository $repository Repository.
	 */
	public function __construct( $sync_service, $repository ) {
		$this->sync_service = $sync_service;
		$this->repository   = $repository;
	}

	/**
	 * Register daily sync on activation.
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( 'iillc_wprlsd_daily_sync' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'iillc_wprlsd_daily_sync' );
		}
	}

	/**
	 * Remove scheduled events on deactivation.
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'iillc_wprlsd_daily_sync' );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'iillc_wprlsd_daily_sync' );
		}
	}

	/**
	 * Run the scheduled daily sync.
	 */
	public function run_daily_sync() {
		$this->sync_service->sync_sample_locations();
	}

	/**
	 * Perform a stale-data fallback on frontend visits.
	 *
	 * This keeps the demo practical for lower-traffic sites where cron may not
	 * fire exactly on schedule, without turning page views into full-time workers.
	 */
	public function maybe_refresh_on_frontend_visit() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! $this->repository->is_stale() ) {
			return;
		}

		if ( get_transient( 'iillc_wprlsd_frontend_refresh_lock' ) ) {
			return;
		}

		set_transient( 'iillc_wprlsd_frontend_refresh_lock', 1, 5 * MINUTE_IN_SECONDS );
		$this->sync_service->sync_sample_locations();
	}
}
