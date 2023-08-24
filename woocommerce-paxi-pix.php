<?php
/*
 * Plugin Name: PAXI - Módulo PIX
 * Plugin URI: https://paxi.com.br
 * Description: Aceite pagamentos em seu WooCommerce via PIX com a PAXI.
 * Author: PAXI
 * Author URI: https://paxi.com.br/blog/integrar-ao-wordpress-woocommerce
 * Version: 1.0.1
 */

defined( 'ABSPATH' ) || exit;

add_filter('woocommerce_payment_gateways', 'paxi_pix_add_gateway_class');
function paxi_pix_add_gateway_class($gateways)
{
    $gateways[] = 'WC_PAXI_PIX_Gateway';
    return $gateways;
}


add_action('plugins_loaded', 'paxi_pix_init_gateway_class');
function paxi_pix_init_gateway_class()
{
    include_once dirname( __FILE__ ) . '/vendor/autoload.php';
	include_once dirname( __FILE__ ) . '/includes/php/class-wc-paxi-pix-sdk.php';
	include_once dirname( __FILE__ ) . '/includes/php/class-wc-paxi-pix-gateway.php';
}
