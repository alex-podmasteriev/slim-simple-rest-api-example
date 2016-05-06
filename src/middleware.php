<?php
// Application middleware

$app->add( function( $req, $res, $next ) {
    require_once __DIR__ . '/../orm-custom/autoload.php';

    if( ! isset( $this->Book ) ){
        $this->Book = new Book();
    }
    if( ! isset( $this->Author ) ){
        $this->Author = new Author();
    }


    return $next( $req, $res );
});

function checkURL($url){
    $headers = @get_headers($url);
    return $headers ? TRUE : FALSE;
}

function vd ( $arg) {
    var_dump($arg); die();
}

function redirect( $way = NULL ) {
    if( $way === NULL ){
        $way = '/';
    }
    exit( header('Location: '.$way) );
}

function back(){
    if( isset( $_SERVER['HTTP_REFERER'] ) ) {
        exit( header( 'Location: '. $_SERVER['HTTP_REFERER'] ) );
    } else {
        redirect( '/'.Config::$default_controller );
    }
}

function now(){
    return date('Y-m-d H:i:s');
}