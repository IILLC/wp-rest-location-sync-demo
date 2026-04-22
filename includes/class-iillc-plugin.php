<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin bootstrap and wires the main services together.
 */
class IILLC_WPRLSD_Plugin {

	/**
	 * @var IILLC_WPRLSD_Location_Sync
	 */
	private $sync_service;

	/**
	 * @var IILLC_WPRLSD_Admin_Page
	 */
	private $admin_page;

	/**
	 * @var IILLC_WPRLSD_Scheduler
	 */
	private $scheduler;

	/**
	 * Initialize plugin services.
	 */
	public function init() {
		$repository = new IILLC_WPRLSD_Location_Repository();
		$client     = new IILLC_WPRLSD_API_Client();
		$mapper     = new IILLC_WPRLSD_Location_Mapper();

		$this->sync_service = new IILLC_WPRLSD_Location_Sync( $client, $mapper, $repository );
		$this->admin_page   = new IILLC_WPRLSD_Admin_Page( $this->sync_service );
		$this->scheduler    = new IILLC_WPRLSD_Scheduler( $this->sync_service, $repository );

		add_action( 'init', array( $repository, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'template_redirect', array( $this->scheduler, 'maybe_refresh_on_frontend_visit' ) );
		add_action( 'iillc_wprlsd_daily_sync', array( $this->scheduler, 'run_daily_sync' ) );

		$this->admin_page->init();
	}

	/**
	 * Register a small shortcode so the repo demonstrates data consumption,
	 * while keeping presentation intentionally secondary to the sync pattern.
	 */
	public function register_shortcode() {
		add_shortcode( 'iillc_location_summary', array( $this, 'render_location_summary' ) );
	}

	/**
	 * Render a minimal location summary by external ID.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_location_summary( $atts ) {
		$atts = shortcode_atts(
			array(
				'external_id' => '',
			),
			$atts,
			'iillc_location_summary'
		);

		$external_id = sanitize_text_field( $atts['external_id'] );

		if ( '' === $external_id ) {
			return '';
		}

		$post = get_posts(
			array(
				'post_type'      => 'iillc_location',
				'posts_per_page' => 1,
				'meta_key'       => '_iillc_external_id',
				'meta_value'     => $external_id,
			)
		);

		if ( empty( $post ) ) {
			return '<p>No synced location found.</p>';
		}

		$post_id = (int) $post[0]->ID;
		$name    = get_the_title( $post_id );
		$status  = get_post_meta( $post_id, '_iillc_status', true );
		$lat     = get_post_meta( $post_id, '_iillc_latitude', true );
		$lng     = get_post_meta( $post_id, '_iillc_longitude', true );
		$url     = get_post_meta( $post_id, '_iillc_reservation_url', true );

		ob_start();
		?>
		<div class="iillc-location-summary">
			<p><strong><?php echo esc_html( $name ); ?></strong></p>
			<?php if ( $status ) : ?>
				<p>Status: <?php echo esc_html( $status ); ?></p>
			<?php endif; ?>
			<?php if ( $lat && $lng ) : ?>
				<p>Coordinates: <?php echo esc_html( $lat ); ?>, <?php echo esc_html( $lng ); ?></p>
			<?php endif; ?>
			<?php if ( $url ) : ?>
				<p><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">View Reservation Info</a></p>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
