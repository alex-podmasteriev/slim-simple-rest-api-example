<?php
class Database {

    protected static $_connect;

    private function __construct() {
    }

    public static function getConnect() {
        if (self::$_connect === NULL) {
            self::$_connect = new mysqli(
                Config::$db_hostname,
                Config::$db_login,
                Config::$db_password,
                Config::$db_name)
            or die ('Can not connect with Database!');
            self::$_connect->set_charset("utf8");
        }
        return self::$_connect;
    }

    private function __clone() {
    }

    private function __wakeup() {
    }

    public function __destruct(){
    }

}
