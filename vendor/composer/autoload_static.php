<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0d3dec1e59443b531d4383450955d5a0
{
    public static $classMap = array (
        'WP_Async_Request' => __DIR__ . '/..' . '/a5hleyrich/wp-background-processing/classes/wp-async-request.php',
        'WP_Background_Process' => __DIR__ . '/..' . '/a5hleyrich/wp-background-processing/classes/wp-background-process.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit0d3dec1e59443b531d4383450955d5a0::$classMap;

        }, null, ClassLoader::class);
    }
}
