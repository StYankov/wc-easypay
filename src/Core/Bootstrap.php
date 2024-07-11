<?php

namespace Skills\WcEasypay\Core;

class Bootstrap {
    public function __construct() {
        $this->init();

        add_filter( 'woocommerce_payment_gateways', [ $this, 'register_payment_gateway' ] );
    }

    public function init() {
        Settings::init();
    }

    public function register_payment_gateway( $gateways ) {
        $gateways[] = WC_Gateway_Easypay::class;

        return $gateways;
    }
}