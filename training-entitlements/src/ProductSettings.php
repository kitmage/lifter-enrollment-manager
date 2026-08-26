<?php
namespace Aspen\TrainingEntitlements;

defined( 'ABSPATH' ) || exit;

final class ProductSettings {
	const ENABLED = '_ate_enabled'; const COURSE = '_ate_course_id'; const SEATS = '_ate_seats'; const DAYS = '_ate_days';
	public function hooks() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variation' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation' ), 10, 2 );
	}
	public function tab( $tabs ) { $tabs['ate'] = array( 'label'=>__( 'Training Entitlements', 'aspen-training-entitlements' ), 'target'=>'ate_product_data', 'class'=>array( 'show_if_simple','show_if_variable','show_if_subscription','show_if_variable-subscription' ) ); return $tabs; }
	private function courses() { $out = array( '' => __( 'Select a course', 'aspen-training-entitlements' ) ); foreach ( get_posts( array( 'post_type'=>'course', 'post_status'=>'publish', 'numberposts'=>-1, 'orderby'=>'title', 'order'=>'ASC' ) ) as $course ) $out[ $course->ID ] = $course->post_title; return $out; }
	public function panel() { echo '<div id="ate_product_data" class="panel woocommerce_options_panel hidden">'; woocommerce_wp_checkbox( array( 'id'=>self::ENABLED, 'label'=>__( 'Enable Training Entitlements', 'aspen-training-entitlements' ) ) ); woocommerce_wp_select( array( 'id'=>self::COURSE, 'label'=>__( 'LifterLMS Course', 'aspen-training-entitlements' ), 'options'=>$this->courses() ) ); woocommerce_wp_text_input( array( 'id'=>self::SEATS, 'label'=>__( 'Entitlements Per Unit', 'aspen-training-entitlements' ), 'type'=>'number', 'custom_attributes'=>array( 'min'=>1, 'step'=>1 ) ) ); woocommerce_wp_text_input( array( 'id'=>self::DAYS, 'label'=>__( 'Redemption Window (days)', 'aspen-training-entitlements' ), 'type'=>'number', 'value'=>get_post_meta( get_the_ID(), self::DAYS, true ) ?: 30, 'custom_attributes'=>array( 'min'=>1, 'step'=>1 ) ) ); echo '</div>'; }
	public function save( $id ) { update_post_meta( $id, self::ENABLED, isset( $_POST[ self::ENABLED ] ) ? 'yes' : 'no' ); foreach ( array( self::COURSE, self::SEATS, self::DAYS ) as $key ) if ( isset( $_POST[ $key ] ) ) update_post_meta( $id, $key, absint( wp_unslash( $_POST[ $key ] ) ) ); }
	public function variation( $loop, $data, $variation ) { echo '<div class="form-row form-row-full"><strong>' . esc_html__( 'Training Entitlement Overrides', 'aspen-training-entitlements' ) . '</strong></div>'; woocommerce_wp_select( array( 'id'=>self::COURSE."[$loop]", 'name'=>self::COURSE."[$loop]", 'label'=>__( 'Course (inherit if blank)', 'aspen-training-entitlements' ), 'value'=>get_post_meta( $variation->ID, self::COURSE, true ), 'options'=>$this->courses(), 'wrapper_class'=>'form-row form-row-full' ) ); foreach ( array( self::SEATS=>__( 'Seats (inherit if blank)', 'aspen-training-entitlements' ), self::DAYS=>__( 'Days (inherit if blank)', 'aspen-training-entitlements' ) ) as $key=>$label ) woocommerce_wp_text_input( array( 'id'=>$key."[$loop]", 'name'=>$key."[$loop]", 'label'=>$label, 'value'=>get_post_meta( $variation->ID, $key, true ), 'type'=>'number', 'wrapper_class'=>'form-row form-row-first', 'custom_attributes'=>array( 'min'=>1 ) ) ); }
	public function save_variation( $id, $loop ) { foreach ( array( self::COURSE,self::SEATS,self::DAYS ) as $key ) { $value = isset( $_POST[ $key ][ $loop ] ) ? absint( wp_unslash( $_POST[ $key ][ $loop ] ) ) : 0; $value ? update_post_meta( $id, $key, $value ) : delete_post_meta( $id, $key ); } }
	public function config( $product_id, $variation_id = 0 ) { $parent = $variation_id ? wp_get_post_parent_id( $variation_id ) : $product_id; if ( 'yes' !== get_post_meta( $parent ?: $product_id, self::ENABLED, true ) ) return null; $config=array(); foreach ( array( 'course'=>self::COURSE,'seats'=>self::SEATS,'days'=>self::DAYS ) as $name=>$key ) $config[$name] = absint( $variation_id ? get_post_meta( $variation_id, $key, true ) : 0 ) ?: absint( get_post_meta( $parent ?: $product_id, $key, true ) ); return $config['course'] && $config['seats'] ? $config : null; }
}
