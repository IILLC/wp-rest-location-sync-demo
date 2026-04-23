# WP REST Location Sync Demo

A sanitized WordPress demo plugin showing a maintainable pattern for syncing structured location data from an external REST API into WordPress.

## Screenshot

/assets/restapi-demo-admin-manual-sync.jpg
/assets/restapi-demo-short-code-output.jpg

## Why This Repo Exists

Much of my production work involved integrating WordPress with third-party systems, refreshing stale data, and normalizing remote responses into something editorial teams could rely on. This repo extracts that pattern into a small, reviewable demo plugin.

It is intentionally **not** a full campground product. It focuses on the sync architecture.

## Pattern Demonstrated

- external REST API fetches through the WordPress HTTP API
- response normalization through a dedicated mapper
- upserts into WordPress using external IDs
- scheduled daily sync with a lightweight stale-data fallback
- manual backend refresh for reviewer/admin visibility
- self-contained fixture-driven demo mode plus a remote-ready integration path
- small, bounded demo scope instead of a production-scale importer

## What Reviewers Should Notice

- class-based plugin structure with separation between transport, mapping, scheduling, persistence, and admin UI
- Custom Post Type + post meta storage chosen for reviewer readability
- manual sync and scheduled sync both supported without adding heavy configuration
- stale-data fallback kept intentionally lightweight rather than turning page views into a full worker system
- bundled JSON fixture makes the repo runnable without credentials while still reflecting a realistic external payload shape
- filterable data source mode, API base URL, and API key keep the demo portable and easy to extend later
- presentation kept minimal; the repo is about the integration pattern, not front-end rendering

## Review Notes

- The demo syncs a small sample set of external IDs rather than trying to mirror a complete remote dataset.
- Live credentials are intentionally excluded.
- The bundled fixture is based on a campsite/location-style payload with nested attributes, equipment, and media.
- A minimal shortcode is included only to show downstream use of synced data.

## Architecture

```text
wp-rest-location-sync-demo/
├── wp-rest-location-sync-demo.php
├── data/
│   └── sample-locations.json
├── includes/
│   ├── class-iillc-plugin.php
│   ├── class-iillc-api-client.php
│   ├── class-iillc-location-mapper.php
│   ├── class-iillc-location-repository.php
│   ├── class-iillc-location-sync.php
│   ├── class-iillc-scheduler.php
│   └── class-iillc-admin-page.php
├── readme.md
└── uninstall.php
```

## Demo Workflow

1. A daily WP-Cron event triggers a bounded sync.
2. If cron is missed, a stale-data check can refresh once on a frontend visit.
3. The API client loads a bundled JSON fixture by default so the repo works out of the box.
4. The mapper converts one location payload into a clean normalized array.
5. The repository upserts the location using the external ID.
6. The admin page exposes a manual sync button and recent sync status.
7. A filter can switch the same client to a live remote endpoint later.

## Intentionally Excluded

- full production campground rendering
- geospatial nearby-water calculations
- review aggregation
- weather scoring / fishing quality logic
- multi-endpoint orchestration
- large admin settings surfaces

## Default Demo Mode

By default, the plugin reads from `data/sample-locations.json`.

That keeps the repo:

- self-contained
- easy to review
- safe to publish without credentials
- still representative of a real API response structure

## Sample Filter Usage

```php
add_filter( 'iillc_wprlsd_data_source_mode', function() {
	return 'remote';
} );

add_filter( 'iillc_wprlsd_api_base_url', function() {
	return 'https://your-endpoint.example/v1/';
} );

add_filter( 'iillc_wprlsd_api_key', function() {
	return 'your-api-key';
} );

add_filter( 'iillc_wprlsd_sample_external_ids', function() {
	return array( '100001', '100002', '100003' );
} );
```

## Shortcode

```text
[iillc_location_summary external_id="100001"]
```

## Naming Convention

This repo uses the `iillc_` / `IILLC_` prefix pattern intentionally. That reflects the same long-term maintainability and conflict-avoidance approach I use in production WordPress work.

## License

MIT