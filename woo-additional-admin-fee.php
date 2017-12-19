<?php
/**
 * Plugin Name: Woo Additional Admin Fee Percentage
 * Description: Add an additional administration fee to your cart
 * Version: 1.0.0
 * Author: SkyPress LLC
 * Author URI: https://skypress.io
 * Text Domain: woocommerce-extension
 * Domain Path: /languages
 *
 * WC requires at least: 3.2
 * WC tested up to: 3.2
 *
 * Copyright: © 2017 SkyPress LLC
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Additonal Administration Fee.
 *
 * Manage settings and functionality for adding an additional administration fee to any WooCommerce product.
 *
 * @since 1.0.0
 **/
class Additional_Admin_Fee {

  /**
   * Slug to note additional admin fee settings.
   *
   * @since 1.0.0
   * @var string $additional_admin_fee_slug Must be lowercase with underscores to seperate words.
   **/
  private $additional_admin_fee_slug;

  /**
   * Title to be used for additional admin fee settings
   *
   * @since 1.0.0
   * @var string $additional_admin_fee_title
   **/
  private $additional_admin_fee_title;

  /**
   * Expanded description of the additional admin fee
   *
   * @since 1.0.0
   * @var string $additional_admin_fee_description
   **/
  private $additional_admin_fee_description;

  /**
   * Array of all products in cart that have an additional admin fee
   *
   * @since 1.0.0
   * @var array $additional_admin_fee Empty to begin with, will be populated when cart total is calculated.
   **/
  private $additional_admin_fees = [];


  /**
   * Assign proper vlaues to this classes properties
   *
   * @since 1.0.0
   *
   **/
  public function __construct() {
    $this->additional_admin_fee_slug        = 'additional_admin_fee';
    $this->additional_admin_fee_title       = __( 'Additional Admin Fee', 'woocommerce' );
    $this->additional_admin_fee_description = __( 'Add an additional administration fee (percentage)', 'woocommerce' );

  }

  /**
   * Initiate the proper Woo Hooks to enable the Aditional Admin Fee
   *
   * @since 1.0.0
   *
   **/
  public function init() {
    // Action Hook to alter WooCommerce Product Data metaBox
    // add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_to_product_options_group' ] );
    add_action( 'woocommerce_product_options_advanced', [ $this, 'add_to_product_options_group' ] );
    // Action Hook to save the Adittional Admin Fee to a single product
    add_action( 'woocommerce_process_product_meta', [ $this, 'save_to_product_meta' ] );
    // Action Hook to calculate the total additional admin fees in the cart
    add_action( 'woocommerce_cart_calculate_fees', [ $this, 'add_addional_fee_to_cart_total' ] );

  }

  /**
   * Define Arguments and add new input to the WooCommerce Product Data metabox
   *
   * @since 1.0.0
   *
   **/
  public function add_to_product_options_group() {
    $args = [
      'id'          => $this->additional_admin_fee_slug,
      'label'       => sanitize_text_field( $this->additional_admin_fee_title ),
      'placeholder' => '%',
      'desc_tip'    => true,
      'description' => sanitize_text_field( $this->additional_admin_fee_description ),
    ];

    woocommerce_wp_text_input( $args );

  }

  /**
   * Save the additional admin fee meta data for this product
   *
   * @since 1.0.0
   *
   * @param integer $post_id ID of the product to save.
   * @return boolean If checks do not pass, return false to exit this method.
   **/
  public function save_to_product_meta( $post_id ) {
    if ( ! ( isset( $_POST['woocommerce_meta_nonce'], $_POST[ $this->additional_admin_fee_slug ] ) ) ) {
      return false;
    }

    if( ! wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {
      return false;
    }

    $fee_value = sanitize_text_field(
      wp_unslash( $_POST[ $this->additional_admin_fee_slug ] )
    );

    if( ! is_numeric( $fee_value ) ) return false;

    update_post_meta(
      $post_id,
      $this->additional_admin_fee_slug,
      esc_attr( $fee_value )
    );

  }

  /**
   * Summarize contents of the cart and add the total additional admin fee to the cart total
   *
   * @since 1.0.0
   *
   * @global class $woocommerce
   *
   **/
  public function add_addional_fee_to_cart_total() {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
      return false;
    }

    global $woocommerce;

    array_walk( $woocommerce->cart->cart_contents, function( &$product ) {
      $this->additional_admin_fees[] = $this->check_product_for_additional_fee( $product['product_id'] );
    } );

    $fee_total = array_sum( $this->additional_admin_fees );
    $surcharge = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * ( $fee_total / 100 );

    $woocommerce->cart->add_fee( $this->additional_admin_fee_title, $surcharge, true, '' );

  }

  /**
   * Check if this product has an additional admin fee
   *
   * @since 1.0.0
   *
   * @param integer $product_id Product ID to check. 0
   * @return integer Admin fee percentage to be added to the cart total
   **/
  private function check_product_for_additional_fee( $product_id = 0 ) {
    $post_meta = get_post_meta( $product_id, $this->additional_admin_fee_slug )[0];

    if( empty( $post_meta ) || ! is_numeric( $post_meta ) ) {
      return 0;
    }

    return $post_meta;

  }

}

/**
 * Check if WooCommerce is active and instantiate our class
 *
 * @since 1.0.0
 *
 **/
add_action( 'plugins_loaded', function() {
  if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

      $additional_admin_fee = new Additional_Admin_Fee;
      $additional_admin_fee->init();

  }

} );
