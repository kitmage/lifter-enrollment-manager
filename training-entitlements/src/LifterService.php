<?php
namespace Aspen\TrainingEntitlements;

defined( 'ABSPATH' ) || exit;

final class LifterService {
	public function course_exists( $id ) { return 'course' === get_post_type( $id ) && 'publish' === get_post_status( $id ); }
	public function enrolled( $user_id, $course_id ) { return function_exists( 'llms_is_user_enrolled' ) && llms_is_user_enrolled( $user_id, $course_id ); }
	public function enroll( $user_id, $course_id ) { return function_exists( 'llms_enroll_student' ) && (bool) llms_enroll_student( $user_id, $course_id, 'ate_redemption' ); }
	public function url( $course_id ) { return get_permalink( $course_id ); }
}
