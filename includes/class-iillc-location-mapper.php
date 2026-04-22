<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts a remote location payload into the plugin's normalized data shape.
 */
class IILLC_WPRLSD_Location_Mapper {

	/**
	 * Map a remote payload into a normalized array.
	 *
	 * The expected input shape is intentionally simple and based on a typical
	 * campsite/location response with nested attributes, media, and equipment.
	 *
	 * @param array $payload Remote payload.
	 * @return array|WP_Error
	 */
	public function map_location( array $payload ) {
		if ( empty( $payload['CampsiteID'] ) ) {
			return new WP_Error( 'iillc_wprlsd_missing_id', 'Remote location payload is missing CampsiteID.' );
		}

		$primary_image_url = '';
		$attributes        = array();
		$equipment         = array();

		if ( ! empty( $payload['ENTITYMEDIA'] ) && is_array( $payload['ENTITYMEDIA'] ) ) {
			foreach ( $payload['ENTITYMEDIA'] as $media ) {
				if ( ! empty( $media['IsPrimary'] ) && ! empty( $media['URL'] ) ) {
					$primary_image_url = esc_url_raw( $media['URL'] );
					break;
				}
			}
		}

		if ( ! empty( $payload['ATTRIBUTES'] ) && is_array( $payload['ATTRIBUTES'] ) ) {
			foreach ( $payload['ATTRIBUTES'] as $attribute ) {
				if ( ! empty( $attribute['AttributeName'] ) && isset( $attribute['AttributeValue'] ) ) {
					$attributes[] = sanitize_text_field( $attribute['AttributeName'] ) . ': ' . sanitize_text_field( $attribute['AttributeValue'] );
				}
			}
		}

		if ( ! empty( $payload['PERMITTEDEQUIPMENT'] ) && is_array( $payload['PERMITTEDEQUIPMENT'] ) ) {
			foreach ( $payload['PERMITTEDEQUIPMENT'] as $item ) {
				if ( empty( $item['EquipmentName'] ) ) {
					continue;
				}

				$summary = sanitize_text_field( $item['EquipmentName'] );

				if ( isset( $item['MaxLength'] ) && '' !== (string) $item['MaxLength'] ) {
					$summary .= ' (' . absint( $item['MaxLength'] ) . ' max)';
				}

				$equipment[] = $summary;
			}
		}

		return array(
			'external_id'        => sanitize_text_field( $payload['CampsiteID'] ),
			'facility_id'        => ! empty( $payload['FacilityID'] ) ? sanitize_text_field( $payload['FacilityID'] ) : '',
			'name'               => ! empty( $payload['CampsiteName'] ) ? sanitize_text_field( $payload['CampsiteName'] ) : 'Unnamed Location',
			'type'               => ! empty( $payload['CampsiteType'] ) ? sanitize_text_field( $payload['CampsiteType'] ) : '',
			'status'             => ! empty( $payload['TypeOfUse'] ) ? sanitize_text_field( $payload['TypeOfUse'] ) : '',
			'accessible'         => ! empty( $payload['CampsiteAccessible'] ),
			'latitude'           => isset( $payload['CampsiteLatitude'] ) ? (string) $payload['CampsiteLatitude'] : '',
			'longitude'          => isset( $payload['CampsiteLongitude'] ) ? (string) $payload['CampsiteLongitude'] : '',
			'last_remote_update' => ! empty( $payload['LastUpdatedDate'] ) ? sanitize_text_field( $payload['LastUpdatedDate'] ) : '',
			'reservation_url'    => ! empty( $payload['ReservationURL'] ) ? esc_url_raw( $payload['ReservationURL'] ) : '',
			'equipment_summary'  => implode( ', ', $equipment ),
			'attributes_summary' => implode( ' | ', $attributes ),
			'primary_image_url'  => $primary_image_url,
		);
	}
}
