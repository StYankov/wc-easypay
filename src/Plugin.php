<?php

namespace Skills\WcEasypay;

final class Plugin {
    private static ?self $instance      = NULL;
    private static ?string $plugin_file = NULL;

    private function __construct( string $plugin_file ) {
        self::$plugin_file = $plugin_file;
        $this->init();

        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'wp_log_init', [ $this, 'register_logger' ] );
    }
    
    public function init() {
        new Core\Bootstrap();
    }

    public function register_logger() {
        wp_log()->add_logger(
            \WPLog\Plugin::create_file_logger( 'easypay', 'easypay.log' )
        );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'wc-easypay', false, dirname( plugin_basename( self::$plugin_file ) ) . '/languages' );
    }

    public static function get_plugin_url() {
        return plugin_dir_url( self::$plugin_file ) . '/src';
    }
    
    public function get_plugin_path() {
        return plugin_dir_path( self::$plugin_file ) . '/src';
    }

    public static function instance( string $plugin_file ) {
        if ( ! self::$instance ) {
            self::$instance = new self( $plugin_file );
        }

        return self::$instance;
    }
}