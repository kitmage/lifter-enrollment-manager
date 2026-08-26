<?php
namespace Aspen\TrainingEntitlements;

defined( 'ABSPATH' ) || exit;

final class Activator {
	public static function activate() {
		self::install();
		add_rewrite_rule( '^training/redeem/([A-Za-z0-9_-]+)/?$', 'index.php?ate_token=$matches[1]', 'top' );
		add_rewrite_endpoint( 'training-enrollments', EP_ROOT | EP_PAGES );
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( ATE_SCHEMA_VERSION !== get_option( 'ate_schema_version' ) ) {
			self::install();
		}
	}

	private static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$batches = $wpdb->prefix . 'training_entitlement_batches';
		$redemptions = $wpdb->prefix . 'training_entitlement_redemptions';
		$audit = $wpdb->prefix . 'training_entitlement_audit';
		dbDelta( "CREATE TABLE {$batches} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			token_hash char(64) NOT NULL,
			customer_user_id bigint unsigned NOT NULL DEFAULT 0,
			order_id bigint unsigned NOT NULL,
			order_item_id bigint unsigned NOT NULL,
			subscription_id bigint unsigned NOT NULL DEFAULT 0,
			product_id bigint unsigned NOT NULL,
			variation_id bigint unsigned NOT NULL DEFAULT 0,
			course_id bigint unsigned NOT NULL,
			entitlements_total int unsigned NOT NULL,
			entitlements_used int unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_by bigint unsigned NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY source_item (order_item_id), UNIQUE KEY token_hash (token_hash),
			KEY customer (customer_user_id), KEY order_id (order_id), KEY subscription_id (subscription_id), KEY course_status (course_id,status), KEY expires_at (expires_at)
		) {$charset};" );
		dbDelta( "CREATE TABLE {$redemptions} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			batch_id bigint unsigned NOT NULL,
			user_id bigint unsigned NOT NULL,
			first_name_snapshot varchar(100) NOT NULL DEFAULT '',
			last_name_snapshot varchar(100) NOT NULL DEFAULT '',
			email_snapshot varchar(190) NOT NULL DEFAULT '',
			redeemed_at datetime NULL,
			status varchar(20) NOT NULL,
			course_id bigint unsigned NOT NULL,
			error_context varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY batch_user (batch_id,user_id), KEY batch_status (batch_id,status), KEY user_id (user_id)
		) {$charset};" );
		dbDelta( "CREATE TABLE {$audit} (
			id bigint unsigned NOT NULL AUTO_INCREMENT, batch_id bigint unsigned NOT NULL, action varchar(50) NOT NULL,
			previous_value longtext NULL, new_value longtext NULL, actor_user_id bigint unsigned NOT NULL DEFAULT 0, created_at datetime NOT NULL,
			PRIMARY KEY (id), KEY batch_id (batch_id), KEY action (action)
		) {$charset};" );
		update_option( 'ate_schema_version', ATE_SCHEMA_VERSION, false );
	}
}
