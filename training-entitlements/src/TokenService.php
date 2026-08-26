<?php
namespace Aspen\TrainingEntitlements;

defined( 'ABSPATH' ) || exit;

final class TokenService {
	public function generate() {
		return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
	}
	public function hash( $token ) {
		return hash_hmac( 'sha256', (string) $token, wp_salt( 'auth' ) );
	}
	public function url( $token ) {
		return home_url( '/training/redeem/' . rawurlencode( $token ) . '/' );
	}
}
