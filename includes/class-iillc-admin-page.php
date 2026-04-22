<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides a small admin page for manual sync and reviewer visibility.
 */
class IILLC_WPRLSD_Admin_Page {

	/**
	 * @var IILLC_WPRLSD_Location_Sync
	 */
	private $sync_service;

	/**
	 * @param IILLC_WPRLSD_Location_Sync $sync_service Sync service.
	 */
	public function __construct( $sync_service ) {
		$this->sync_service = $sync_service;
	}

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_iillc_wprlsd_run_sync', array( $this, 'handle_manual_sync' ) );
	}

	/**
	 * Register the admin screen.
	 */
	public function register_menu() {
		add_management_page(
			'Location Sync Demo',
			'Location Sync Demo',
			'manage_options',
			'iillc-wprlsd',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle manual sync submission.
	 */
	public function handle_manual_sync() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'wp-rest-location-sync-demo' ) );
		}

		check_admin_referer( 'iillc_wprlsd_run_sync' );

		$this->sync_service->sync_sample_locations();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'iillc-wprlsd',
					'synced' => '1',
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	/**
	 * Render the admin page.
	 */
	public function render_page() {
		$repository = new IILLC_WPRLSD_Location_Repository();
		$status     = $repository->get_sync_status();
		$last_sync  = get_option( 'iillc_wprlsd_last_sync_at', '' );
		$locations  = get_posts(
			array(
				'post_type'      => 'iillc_location',
				'posts_per_page' => 10,
				'post_status'    => 'publish',
			)
		);
		?>
		<div class="wrap">
			<h1>Location Sync Demo</h1>
			<p>This admin screen keeps the demo reviewer-friendly: a manual sync trigger, bounded sample IDs, and a small synced-record preview.</p>

			<?php if ( isset( $_GET['synced'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Manual sync completed.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="iillc_wprlsd_run_sync" />
				<?php wp_nonce_field( 'iillc_wprlsd_run_sync' ); ?>
				<?php submit_button( 'Run Manual Sync' ); ?>
			</form>

			<h2>Last Sync Status</h2>
			<p><strong>Last sync time:</strong> <?php echo $last_sync ? esc_html( $last_sync ) : 'Not yet synced'; ?></p>
			<p><strong>Last result:</strong> <?php echo ! empty( $status['success'] ) ? 'Success' : 'Pending / errors'; ?></p>
			<p><strong>Locations synced:</strong> <?php echo isset( $status['synced'] ) ? esc_html( (string) $status['synced'] ) : '0'; ?></p>

			<?php if ( ! empty( $status['errors'] ) ) : ?>
				<h3>Errors</h3>
				<ul>
					<?php foreach ( $status['errors'] as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<h2>Recent Synced Locations</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Name</th>
						<th>External ID</th>
						<th>Facility ID</th>
						<th>Last Remote Update</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $locations ) ) : ?>
						<tr>
							<td colspan="4">No synced locations yet.</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $locations as $location ) : ?>
							<tr>
								<td><?php echo esc_html( get_the_title( $location->ID ) ); ?></td>
								<td><?php echo esc_html( get_post_meta( $location->ID, '_iillc_external_id', true ) ); ?></td>
								<td><?php echo esc_html( get_post_meta( $location->ID, '_iillc_facility_id', true ) ); ?></td>
								<td><?php echo esc_html( get_post_meta( $location->ID, '_iillc_last_remote_update', true ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
