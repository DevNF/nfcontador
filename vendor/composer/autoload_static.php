<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit55a04729ea13f99c9ff026fd6a5704fc
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'NFContador\\Common\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'NFContador\\Common\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInit55a04729ea13f99c9ff026fd6a5704fc::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit55a04729ea13f99c9ff026fd6a5704fc::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit55a04729ea13f99c9ff026fd6a5704fc::$classMap;

        }, null, ClassLoader::class);
    }
}
