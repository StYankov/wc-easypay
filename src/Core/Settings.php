<?php

namespace Skills\WcEasypay\Core;

use Skills\WcEasypay\Core\Enums\Keys;

class Settings {
    const SETTING_KEY = 'easypay';

    public static ?Keys $keys = NULL;

    public static function init() {
        self::$keys = new Keys();
    }
    
    public static function get_setting( string $key, mixed $default = NULL ) {
        $settings = self::get_settings();

	    $passed_default = func_num_args() > 1;

        if( empty( $settings[ $key ] ) && ! $passed_default ) {
            return $default;
        }

        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    public static function update_setting( string $key, mixed $value ): bool {
        $settings = self::get_settings();
        $settings[ $key ] = $value;

        return update_option( sprintf( 'woocommerce_%s_settings', self::SETTING_KEY ), $settings );
    }

    public static function get_settings(): array {
        return get_option( sprintf( 'woocommerce_%s_settings', self::SETTING_KEY ), [] );
    }
}