<?php
namespace Aspen\TrainingEntitlements;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	public static function boot() {
		Activator::maybe_upgrade();
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'llms_enroll_student' ) ) { add_action( 'admin_notices', array( __CLASS__, 'notice' ) ); return; }
		$tokens=new TokenService(); $audit=new AuditRepository(); $batches=new BatchRepository($tokens,$audit); $redemptions=new RedemptionRepository(); $settings=new ProductSettings(); $lifter=new LifterService(); $service=new RedemptionService($batches,$redemptions,$lifter);
		$settings->hooks(); (new ProvisioningService($batches,$settings))->hooks(); (new FrontendController($batches,$redemptions,$service,$tokens,$lifter))->hooks(); (new AdminController($batches,$redemptions,$audit,$tokens))->hooks();
	}
	public static function notice() { if ( current_user_can( 'activate_plugins' ) ) echo '<div class="notice notice-error"><p>' . esc_html__( 'Aspen Training Entitlements requires WooCommerce and LifterLMS to be active.', 'aspen-training-entitlements' ) . '</p></div>'; }
}
