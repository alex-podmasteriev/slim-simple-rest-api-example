<?php
class Validator {

    use Validation;

    function author_id(){
        return $this->valInteger('author_id');
    }

    function name(){
        return $this->valText('name');
    }

    function firstname(){
        return $this->valText('firstname');
    }

    function lastname(){
        return $this->valText('lastname');
    }
}

