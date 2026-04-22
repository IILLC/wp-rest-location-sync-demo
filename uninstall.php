<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$posts = get_posts(
	array(
		'post_type'      => 'iillc_location',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

foreach ( $posts as $post_id ) {
	wp_delete_post( $post_id, true );
}

delete_option( 'iillc_wprlsd_last_sync_status' );
delete_option( 'iillc_wprlsd_last_sync_at' );
delete_transient( 'iillc_wprlsd_frontend_refresh_lock' );
