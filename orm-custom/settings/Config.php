<?php
class Config {
    /* Параметры подключения к MySQL БД */
    static $db_hostname =  'localhost';
    static $db_name = 'slim';
    static $db_login = 'root';
    static $db_password = 'root';

    /* Чтобы посмотреть SQL-код перед выполнением запроса к БД, поменяйте значение соответствующей переменной на TRUE*/
    static $debug_model_get = FALSE;
    static $debug_model_together = FALSE;
    static $debug_model_one = FALSE;
    static $debug_model_counter = FALSE;
    static $debug_model_upd = FALSE;
    static $debug_model_set = FALSE;

    /* Режим вывода ошибок*/
    static $debug_mode = TRUE;
}