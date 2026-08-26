<?php
namespace Aspen\TrainingEntitlements;

defined( 'ABSPATH' ) || exit;

final class AuditRepository {
	public function log( $batch_id, $action, $previous = null, $new = null, $actor = null ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'training_entitlement_audit', array(
			'batch_id' => absint( $batch_id ), 'action' => sanitize_key( $action ),
			'previous_value' => null === $previous ? null : wp_json_encode( $previous ),
			'new_value' => null === $new ? null : wp_json_encode( $new ),
			'actor_user_id' => null === $actor ? get_current_user_id() : absint( $actor ), 'created_at' => current_time( 'mysql', true ),
		) );
	}
	public function for_batch( $batch_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}training_entitlement_audit WHERE batch_id=%d ORDER BY id DESC", $batch_id ) );
	}
}
