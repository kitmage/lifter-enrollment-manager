<?php
namespace Aspen\TrainingEntitlements;

defined( 'ABSPATH' ) || exit;

final class RedemptionRepository {
	private function table() { global $wpdb; return $wpdb->prefix . 'training_entitlement_redemptions'; }
	public function get( $batch_id, $user_id ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->table() . ' WHERE batch_id=%d AND user_id=%d', $batch_id, $user_id ) ); }
	public function pending( $batch_id, $user_id, $course_id ) {
		global $wpdb; $now = current_time( 'mysql', true );
		$inserted = $wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO ' . $this->table() . " (batch_id,user_id,status,course_id,created_at,updated_at) VALUES (%d,%d,'pending',%d,%s,%s)", $batch_id, $user_id, $course_id, $now, $now ) );
		if ( 1 === $inserted ) return true;
		// A failed attempt may be retried, but only one request can claim it.
		return 1 === $wpdb->query( $wpdb->prepare( 'UPDATE ' . $this->table() . " SET status='pending',error_context='',updated_at=%s WHERE batch_id=%d AND user_id=%d AND status='failed'", $now, $batch_id, $user_id ) );
	}
	public function complete( $batch_id, $user_id ) {
		global $wpdb; $user = get_userdata( $user_id ); if ( ! $user ) return false;
		return false !== $wpdb->update( $this->table(), array( 'status'=>'completed', 'first_name_snapshot'=>get_user_meta( $user_id, 'first_name', true ), 'last_name_snapshot'=>get_user_meta( $user_id, 'last_name', true ), 'email_snapshot'=>$user->user_email, 'redeemed_at'=>current_time( 'mysql', true ), 'updated_at'=>current_time( 'mysql', true ) ), array( 'batch_id'=>$batch_id, 'user_id'=>$user_id ) );
	}
	public function fail( $batch_id, $user_id, $message ) { global $wpdb; return $wpdb->update( $this->table(), array( 'status'=>'failed', 'error_context'=>sanitize_text_field( $message ), 'updated_at'=>current_time( 'mysql', true ) ), array( 'batch_id'=>$batch_id, 'user_id'=>$user_id ) ); }
	public function completed_for_batch( $batch_id ) { global $wpdb; return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->table() . " WHERE batch_id=%d AND status='completed' ORDER BY redeemed_at", $batch_id ) ); }
}
