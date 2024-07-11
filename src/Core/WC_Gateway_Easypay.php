<?php

namespace Skills\WcEasypay\Core;

use Exception;
use WC_Payment_Gateway;

class WC_Gateway_Easypay extends WC_Payment_Gateway {
    public bool $testmode  = false;
    public ?string $secret = NULL;
    public ?string $kin    = NULL;
    
    public function __construct() {
        $this->id                 = 'easypay';
        $this->has_fields         = false;
        $this->method_title       = 'EasyPay';
        $this->method_description = 'EasyPay payment gateway for WooCommerce';

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled     = $this->get_option( 'enabled' );
        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->testmode    = 'yes' === $this->get_option( 'testmode' );
        $this->secret      = $this->get_option( 'secret' );
        $this->kin         = $this->get_option( 'client_number' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
        add_action( 'woocommerce_api_easypay', [ $this, 'webhook' ] );
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __( 'Enable', 'woocommerce' ),
                'type'    => 'checkbox',
                'default' => 'no'
            ],
            'title'           => [
                'title'       => __( 'Title', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default'     => __( 'EasyPay', 'wc-easypay' ),
            ],
            'description' => [
                'title'       => __( 'Description', 'woocommerce' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
            ],
            'testmode' => [
                'title'   => __( 'Test mode', 'wc-easypay' ),
                'type'    => 'checkbox',
                'default' => 'no'
            ],
            'secret' => [
                'title'       => __( 'Secret', 'wc-easypay' ),
                'type'        => 'password',
                'default'     => __( 'The secret key from epay.bg', 'wc-easypay' ),
            ],
            'client_number'   => [
                'title' => __( 'Client number', 'wc-easypay' ),
                'type'            => 'text',
                'description'     => __( 'The client number (KIN) from epay.bg', 'wc-easypay' ),
            ],
            'invoice_num'     => [
                'title'     => __( 'Invoice number', 'wc-easypay' ),
                'type'      => 'text',
                'default'   => 1,
            ],
            'webhook_url'    => [
                'title'    => __( 'Webhook URL', 'wc-easypay' ),
                'type'     => 'text',
                'default'  => home_url( 'wc-api/easypay' ),
                'custom_attributes' => [
                    'readonly' => 'readonly'
                ],
                'description' => __( 'Use this URL to set up the webhook in your EasyPay account', 'wc-easypay' )
            ]
        ];
    }

    /**
     * @param int $order_id
     */
    public function process_payment( $order_id ) {
        $order       = wc_get_order( $order_id );
        $epay_client = new EpayClient( $this->secret, $this->kin );

        try {
            $response = $epay_client->payment_request( $order );

            $order->set_status( 'on-hold' );
            $order->update_meta_data( '_easypay_idn', $response['idn'] );
            $order->update_meta_data( '_easypay_invoice_id', $response['invoice_id'] );
            $order->save();

            $order->add_order_note( sprintf( __( 'EasyPay payment request has been sent. IDN: %s, Invoice: %s', 'wc-easypay' ), $response['idn'], $response['invoice_id'] ) );
        } catch( Exception $e ) {
            $order->add_order_note( $e->getMessage() );
            return [
                'result'   => 'error',
                'redirect' => $order->get_checkout_payment_url( true ),
            ];
        }

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_order_received_url(),
        ];
    }

    public function webhook() {
        $encoded  = isset( $_POST['encoded'] ) ? $_POST['encoded'] : NULL;
        $checksum = isset( $_POST['checksum'] ) ? $_POST['checksum'] : NULL;

        if( empty( $encoded ) || empty( $checksum ) ) {
            echo 'STATUS=ERR:ERR=INVALID CHECKSUM';
            exit;
        }

        if( hash_hmac( 'sha1', $encoded, $this->secret ) !== $checksum ) {
            echo 'STATUS=ERR:ERR=INVALID CHECKSUM';
            exit;
        }

        $data = $this->parse_request( $encoded );

        if( $data['STATUS'] !== 'PAID' ) {
            echo 'STATUS=ERR:ERR=INVALID STATUS';
            exit;
        }

        $order = $this->get_order_by_invoice( $data['INVOICE'] );

        if( empty( $order ) ) {
            echo 'STATUS=ERR:ERR=INVALID ORDER';
            exit;
        }

        $order->add_order_note( __( 'Order payment confirmed automatically via EasyPay', 'wc-easypay' ) );
        $order->payment_complete();

        printf( 'INVOICE=%s:STATUS=OK', $data['INVOICE'] );
        exit;
    }

    private function parse_request( $encoded ) {
        $data = base64_decode( $encoded );
        $data = explode( ':', $data );

        $result = [];
        foreach( $data as $item ) {
            $item = explode( '=', $item );
            $result[ $item[0] ] = $item[1];
        }

        return $result;
    }

    /**
     * @param string|int $invoice_id
     * 
     * @return \WC_Order|null
     */
    private function get_order_by_invoice( $invoice_id ) {
        $orders = wc_get_orders( [
            'limit'      => 1,
            'meta_query' => [
                'key'   => '_easypay_invoice_id',
                'value' => $invoice_id
            ]
        ] );

        if( empty( $orders ) ) {
            return NULL;
        }

        return $orders[0];
    }
}