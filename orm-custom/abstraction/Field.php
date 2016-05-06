<?php
class Field {

    public $key = '';
    public $sign = '';
    public $value = '';
    public $operand = '';


    public function __construct( $key ) {
        $this->key = $key;
    }

    public function __set( $key, $value ){
        if( $this->key == $key){
            $this->value = $value;
            $this->sign = ' = ';
            $this->operand = 'AND';
        }
    }

    public function __unset( $key ) {
        if( $this->key == $key ){
            $this->value = '';
            $this->sign = '';
            $this->operand = '';
        }
    }

    public function __get( $key ) {
            return $this->value;
    }

}
