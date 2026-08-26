<?php
namespace Aspen\TrainingEntitlements;

defined( 'ABSPATH' ) || exit;

final class ProvisioningService {
	private $batches; private $settings;
	public function __construct( BatchRepository $batches, ProductSettings $settings ) { $this->batches=$batches; $this->settings=$settings; }
	public function hooks() { add_action( 'woocommerce_payment_complete', array( $this, 'provision' ) ); add_action( 'woocommerce_order_status_processing', array( $this, 'provision' ) ); add_action( 'woocommerce_order_status_completed', array( $this, 'provision' ) ); add_action( 'woocommerce_order_fully_refunded', array( $this, 'refund' ) ); }
	public function provision( $order_id ) {
		$order = wc_get_order( $order_id ); if ( ! $order || ! $order->is_paid() ) return;
		$subscription_id=0; if ( function_exists( 'wcs_get_subscriptions_for_renewal_order' ) ) { $subs=wcs_get_subscriptions_for_renewal_order( $order ); if ( $subs ) $subscription_id=(int) array_key_first( $subs ); } if ( ! $subscription_id && function_exists( 'wcs_get_subscriptions_for_order' ) ) { $subs=wcs_get_subscriptions_for_order( $order, array( 'order_type'=>'parent' ) ); if ( $subs ) $subscription_id=(int) array_key_first( $subs ); }
		foreach ( $order->get_items( 'line_item' ) as $item_id=>$item ) { $product=$item->get_product(); if ( ! $product ) continue; $variation=$item->get_variation_id(); $config=$this->settings->config( $item->get_product_id(), $variation ); if ( ! $config ) continue; $created=new \DateTimeImmutable( 'now', wp_timezone() ); $expires=$created->modify( '+' . max( 1, $config['days'] ?: 30 ) . ' days' ); $result=$this->batches->create( array( 'customer_user_id'=>(int)$order->get_customer_id(), 'order_id'=>(int)$order_id, 'order_item_id'=>(int)$item_id, 'subscription_id'=>$subscription_id, 'product_id'=>(int)$item->get_product_id(), 'variation_id'=>(int)$variation, 'course_id'=>$config['course'], 'entitlements_total'=>$config['seats'] * max( 1, (int)$item->get_quantity() ), 'expires_at'=>get_gmt_from_date( $expires->format( 'Y-m-d H:i:s' ) ), 'created_by'=>(int)$order->get_customer_id() ) ); if ( $result['created'] ) $item->add_meta_data( '_ate_redemption_token', $result['token'], true ); $item->save(); }
	}
	public function refund( $order_id ) { $this->batches->revoke_order( $order_id ); }
}
