<?php

namespace Skills\WcEasypay\Core;

use Exception;
use WC_Order;

class EpayClient {
    public function __construct( public string $secret, public string $client_number ) {}

    public function payment_request( WC_Order $order ) {
        $invoice_id = $this->generate_invoice_num();

        $data = [
            'MIN'      => $this->client_number,
            'email'    => get_bloginfo( 'admin_email' ),
            'invoice'  => $invoice_id,
            'amount'   => $order->get_total(),
            'currency' => 'BGN',
            'exp_time' => date( 'd.m.Y', strtotime( '+7 days' ) ),
            'DESCR'    => sprintf( 'Order #%s', $order->get_id() ),
            'ENCODING' => 'UTF-8',
        ];

        $data = array_map( function( $key, $value ) {
            return strtoupper( $key ) . '=' . $value;
        }, array_keys( $data ), $data );

        $encoded  = base64_encode( implode( "\n", $data ) );
        $checksum = hash_hmac( 'sha1', $encoded, $this->secret );

        $response = wp_remote_post( $this->get_request_billing_url(), [
            'body' => [
                'LANG'     => 'BG',
                'ENCODED'  => $encoded,
                'CHECKSUM' => $checksum,
            ],
        ] );

        $response_code = wp_remote_retrieve_response_code( $response );

        if( $response_code !== 200 ) {
            throw new Exception( 'Error occurred while trying to request payment', $response_code );
        }

        $body   = wp_remote_retrieve_body( $response );
        $tokens = explode( '=', trim( $body ) );

        if( count( $tokens ) !== 2 ) {
            throw new Exception( 'Error occurred while trying to request payment', 400 );   
        }

        // The generated IDN number
        return [
            'idn'        => $tokens[1],
            'invoice_id' => $invoice_id
        ];
    }

    public function get_request_billing_url() {
        if( Settings::get_setting( Settings::$keys->testmode ) === 'yes' ) {
            return 'https://demo.epay.bg/ezp/reg_bill.cgi';
        }

        return 'https://www.epay.bg/ezp/reg_bill.cgi';
    }

    private function generate_invoice_num() {
        $invoice_num = absint( Settings::get_setting( Settings::$keys->invoice_num ) );

        Settings::update_setting( Settings::$keys->invoice_num, $invoice_num + 1 );

        return $invoice_num;
    }
}