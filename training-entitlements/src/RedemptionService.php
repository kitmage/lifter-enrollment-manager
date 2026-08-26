<?php
namespace Aspen\TrainingEntitlements;

defined( 'ABSPATH' ) || exit;

final class RedemptionService {
	private $batches; private $redemptions; private $lifter;
	public function __construct( BatchRepository $b, RedemptionRepository $r, LifterService $l ) { $this->batches=$b; $this->redemptions=$r; $this->lifter=$l; }
	public function redeem( $token, $user_id ) {
		$batch=$this->batches->find_by_token( $token ); if ( ! $batch ) return 'invalid';
		$existing=$this->redemptions->get( $batch->id, $user_id ); if ( $existing && 'completed' === $existing->status ) return 'already_redeemed';
		if ( 'revoked' === $batch->status ) return 'revoked'; if ( strtotime( $batch->expires_at . ' UTC' ) <= time() ) return 'expired'; if ( 'active' !== $batch->status || $batch->entitlements_used >= $batch->entitlements_total ) return 'exhausted';
		if ( ! $this->lifter->course_exists( $batch->course_id ) ) return 'course_missing'; if ( $this->lifter->enrolled( $user_id, $batch->course_id ) ) return 'already_enrolled';
		if ( ! $this->redemptions->pending( $batch->id, $user_id, $batch->course_id ) ) return 'already_redeemed';
		if ( ! $this->batches->reserve( $batch->id ) ) { $this->redemptions->fail( $batch->id, $user_id, 'Capacity unavailable.' ); return 'exhausted'; }
		if ( ! $this->lifter->enroll( $user_id, $batch->course_id ) ) { $this->batches->release( $batch->id ); $this->redemptions->fail( $batch->id, $user_id, 'LifterLMS enrollment failed.' ); return 'failed'; }
		// Do not release capacity after LifterLMS succeeds: doing so could oversubscribe
		// the course. A finalization failure remains reserved for administrator repair.
		if ( ! $this->redemptions->complete( $batch->id, $user_id ) ) { $this->redemptions->fail( $batch->id, $user_id, 'Enrollment succeeded but finalization failed.' ); return 'failed'; }
		do_action( 'ate_redemption_completed', (int)$batch->id, (int)$user_id, (int)$batch->course_id, (int)$batch->order_id ); return 'success';
	}
}
