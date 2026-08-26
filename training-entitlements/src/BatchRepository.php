<?php
namespace Aspen\TrainingEntitlements;

defined( 'ABSPATH' ) || exit;

final class BatchRepository {
	private $tokens;
	private $audit;
	public function __construct( TokenService $tokens, AuditRepository $audit ) { $this->tokens = $tokens; $this->audit = $audit; }
	private function table() { global $wpdb; return $wpdb->prefix . 'training_entitlement_batches'; }
	public function create( array $data ) {
		global $wpdb;
		$token = $this->tokens->generate();
		$now = current_time( 'mysql', true );
		$row = wp_parse_args( $data, array( 'subscription_id' => 0, 'variation_id' => 0, 'entitlements_used' => 0, 'status' => 'active', 'created_by' => 0 ) );
		$row['token_hash'] = $this->tokens->hash( $token ); $row['created_at'] = $now; $row['updated_at'] = $now;
		if ( ! $wpdb->insert( $this->table(), $row ) ) {
			return array( 'id' => 0, 'token' => '', 'created' => false );
		}
		$id = (int) $wpdb->insert_id; $this->audit->log( $id, 'batch_created', null, $row, $row['created_by'] );
		do_action( 'ate_batch_created', $id, (int) $row['order_id'], (int) $row['course_id'] );
		return array( 'id' => $id, 'token' => $token, 'created' => true );
	}
	public function find( $id ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->table() . ' WHERE id=%d', $id ) ); }
	public function find_by_token( $token ) {
		global $wpdb; $hash = $this->tokens->hash( $token );
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->table() . ' WHERE token_hash=%s', $hash ) );
		return $row && hash_equals( $row->token_hash, $hash ) ? $row : null;
	}
	public function for_customer( $user_id ) { global $wpdb; return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->table() . ' WHERE customer_user_id=%d ORDER BY created_at DESC,id DESC', $user_id ) ); }
	public function reserve( $batch_id ) {
		global $wpdb; $now = current_time( 'mysql', true );
		return 1 === $wpdb->query( $wpdb->prepare( 'UPDATE ' . $this->table() . " SET entitlements_used=entitlements_used+1, updated_at=%s, status=IF(entitlements_used+1>=entitlements_total,'exhausted','active') WHERE id=%d AND status='active' AND expires_at>%s AND entitlements_used<entitlements_total", $now, $batch_id, $now ) );
	}
	public function release( $batch_id ) { global $wpdb; return $wpdb->query( $wpdb->prepare( 'UPDATE ' . $this->table() . " SET entitlements_used=GREATEST(entitlements_used-1,0),status=IF(status='revoked','revoked','active'),updated_at=%s WHERE id=%d", current_time( 'mysql', true ), $batch_id ) ); }
	public function update_admin( $id, array $changes, $action ) {
		global $wpdb; $old = $this->find( $id ); if ( ! $old ) return false;
		$changes['updated_at'] = current_time( 'mysql', true );
		$ok = false !== $wpdb->update( $this->table(), $changes, array( 'id' => $id ) );
		if ( $ok ) $this->audit->log( $id, $action, $old, $changes ); return $ok;
	}
	public function regenerate( $id ) { $token = $this->tokens->generate(); return $this->update_admin( $id, array( 'token_hash' => $this->tokens->hash( $token ) ), 'token_regenerated' ) ? $token : false; }
	public function revoke_order( $order_id ) {
		global $wpdb; $ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . $this->table() . " WHERE order_id=%d AND status!='revoked'", $order_id ) );
		foreach ( $ids as $id ) $this->update_admin( $id, array( 'status' => 'revoked' ), 'order_refunded' );
	}
	public function search( array $filters = array() ) {
		global $wpdb; $where = array( '1=1' ); $args = array();
		foreach ( array( 'status' => '%s', 'course_id' => '%d', 'customer_user_id' => '%d', 'order_id' => '%d', 'subscription_id' => '%d' ) as $key => $format ) if ( ! empty( $filters[ $key ] ) ) { $where[] = "$key=$format"; $args[] = $filters[ $key ]; }
		$sql = 'SELECT * FROM ' . $this->table() . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY created_at DESC LIMIT 200';
		return $wpdb->get_results( $args ? $wpdb->prepare( $sql, $args ) : $sql );
	}
}
