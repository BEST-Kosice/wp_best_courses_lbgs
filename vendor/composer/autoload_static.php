<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite658008a4f40a5cc71f4348e1d0e57fc
{
    public static $prefixesPsr0 = array (
        'S' => 
        array (
            'Sunra\\PhpSimple\\HtmlDomParser' => 
            array (
                0 => __DIR__ . '/..' . '/sunra/php-simple-html-dom-parser/Src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInite658008a4f40a5cc71f4348e1d0e57fc::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
