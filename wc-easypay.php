<?php
/**
 * Plugin Name: WooCommerce EasyPay
 * Description: EasyPay payment gateway for WooCommerce
 * Version: 1.0.0
 * Author: Stoil Yankov
 * Author URI: https://stoilyankov.com
 * License: GPL-2.0+
 * Text Domain: wc-easypay
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

\Skills\WcEasypay\Plugin::instance( __FILE__ );
