<?php
trait Validation {

    protected $status = TRUE;
    protected $data = [];


    public function __construct( $data = [] )
    {
        if( is_array($data) ) {
            $this->data = $data;
        }

    }

    public function __get($name){
        if (isset($this->data[$name])){
            return $this->data[$name];
        }
    }

    public function __set($name, $value){
        return $this->data[$name] = $value;
    }

    public function make() {
        foreach($this->data as $key => $value ) {
            if ($this->status === FALSE ){
                break;
            }
            if( method_exists($this, $key) ) {
                $this->status = $this->$key( $value );
            } else {

                $this->status = FALSE;
            }
        }
    }

    public function success(){
        return $this->status;
    }

    protected  function valEmail($name){
        trim($this->$name);
        $email =  explode('@', $this->$name);
        $count_parts = count($email);
        if($count_parts != 2 || !preg_match("/^[a-zA-Z0-9_.-]{2,36}$/",$email[0])){
            return FALSE;
        } else {
            $email[1] = 'http://www.'.$email[1];
            if(checkURL($email[1]) != TRUE){
                return FALSE;
            }
            return TRUE;
        }
    }


    protected function valUrl($name){
        if(checkURL($this->$name) != TRUE){
            return FALSE;
        }
        return TRUE;
    }

    protected function valBoolean($name){
        if(!preg_match("/^[01]{1}$/", $this->$name)){
            return FALSE;
        }
        return TRUE;
    }

    protected function valOperand($name){
        if(!preg_match("/^[<>=]{1,2}$/", $this->$name)){
            return FALSE;
        }
        return TRUE;
    }

    protected function valInteger ($name){
        if(!preg_match("/^[0-9]{1,}$/", $this->$name)){
            return FALSE;
        }
        return TRUE;
    }

    protected function valHash($name){
        trim($this->$name);
        if(!preg_match("/^[a-zA-Z0-9]{32}$/",$this->$name)){
            return FALSE;
        }
        return TRUE;
    }

    protected function valText($name){
        $mysqli = Database::getConnect();
        $this->$name = mysqli_real_escape_string($mysqli, trim($this->$name));
        unset ($mysqli);
        return TRUE;
    }


    protected function valMulti($name){
        trim($this->$name);
        if(!preg_match("/^[a-zA-Zа-яА-ЯёЁ0-9_-]{1,}$/",$this->$name)){
            return FALSE;
        }
        return TRUE;
    }

    protected function valYear($name){
        if(!preg_match("/^[0-9]{4}$/", $this->$name)){
            return FALSE;
        }
        return TRUE;
    }

    protected function valDate($name){
        if(!preg_match("/^[0-9-]{10}$/", $this->$name)){
            return FALSE;
        }
        return TRUE;
    }

    protected function valDateTime($name){
        if(!preg_match("/^[0-9-:\s]{16,19}$/", $this->$name)){
            return FALSE;
        }
        return TRUE;
    }

}

