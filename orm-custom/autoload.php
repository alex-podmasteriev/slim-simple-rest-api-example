<?php

function __autoLoadORM( $class ){

    $iterator = new \RecursiveIteratorIterator(

        new \RecursiveDirectoryIterator( __DIR__, \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO),
        \RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ( $iterator as $file => $fileInfo ) {
        if($fileInfo->isFile()){
            if($fileInfo->getFileName() === $class.'.php'){
                require_once($file);
                if ( class_exists($class) ) {
                    return;
                }
            }
        }
    }
}

spl_autoload_register('__autoLoadORM');
