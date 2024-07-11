<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit55cadb5bcf1c58b961e03eeae9631010
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Skills\\WcEasypay\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Skills\\WcEasypay\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit55cadb5bcf1c58b961e03eeae9631010::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit55cadb5bcf1c58b961e03eeae9631010::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit55cadb5bcf1c58b961e03eeae9631010::$classMap;

        }, null, ClassLoader::class);
    }
}
