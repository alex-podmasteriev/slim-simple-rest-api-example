<?php
abstract class Model {

    public $params = [];
    public $belongs = [];
    public $has = [];
    public $count;
    protected $sql;
    protected $table;
    protected $where = [];
    protected $where_signs = [];
    protected $where_operands = [];
    protected $protected;

    /* В конструкторе - подключение к базе, создание экземпляра класса SQL( конструктор запросов )
    и формирование ORM-матрицы - массива $params, каждый элемент которого является объектом класса Field .  .  . */

    public function __construct( $id = NULL ) {
        try{
            if( ! $this->table ) {
                throw new Exception( 'Incomplete model <b>' . get_class( $this ) . '</b> table was not defined! ' );
            }
            $this->sql = new SQL( );
            $this->protected = new ProtectedResult( );
            $table_query =  mysqli_query( $this->sql->mysqli, "SHOW TABLES FROM `" . Config::$db_name . "` " );
            $tables = [];
            while ( $row = mysqli_fetch_assoc( $table_query ) ) {
                array_push($tables, $row['Tables_in_'. Config::$db_name]);
            }
            if( ! in_array( $this->table, $tables ) ) {
                throw new Exception(
                    'Table <b>' . $this->table . '</b> does not exist in database <b>' . Config::$db_name . '</b>'
                );
            }
            $params_query = mysqli_query( $this->sql->mysqli, "SHOW COLUMNS FROM `" . $this->table . "` " );
            while ( $row = mysqli_fetch_assoc( $params_query ) ) {
                $this->params[$row['Field']]= new Field( $row['Field'] );
            }
            $count_query = mysqli_query( $this->sql->mysqli, "SELECT count( * ) FROM  `" . $this->table . "` " );
            $result = mysqli_fetch_assoc( $count_query );
            $this->count = $result['count( * )'];
            if( $id != NULL ) {
                return $this->find( $id );
            }
        } catch( Exception $e ) {
            die( $e->getMessage( ) );
        }
    }

    public function describe( ) {
        return $this->sql->queryD( 'DESCRIBE ' . $this->table );
    }

    /* Методы-акксессоры */
    public function __get( $field ) {
        if ( isset( $this->params[$field] ) ) {
            return  $this->params[$field]->value;
        }
        elseif ( isset( $this->has[ lcfirst( $field ) ] ) ) {
            $field = lcfirst( $field );
            return $this->sql
                ->select( )
                ->from( $field )
                ->where( $field . '.' . $this->has[$field], '=', $this->id )
                ->tables( );
        }
        elseif( isset( $this->belongs[lcfirst( $field )] ) ) {
            $field = lcfirst( $field );
            $param = $this->belongs[$field];
            return $this->sql
                ->select( )
                ->from( $field )
                ->where( $field . '.id', '=', $this->$param )
                ->table( );
        }
        elseif ( isset( $this->params[lcfirst( $field ) . '_id'] ) ) {
            $field = lcfirst( $field );
            $table='';
            foreach( $this->belongs as $belong => $value ) {
                if( strstr( $belong, ' AS ' . $field ) ) {
                    $array = explode( ' AS ' . $field, $belong );
                    $table = $array[0];
                }
            }
            $param = $this->params[$field . '_id'];
            return $this->sql
                ->select( )
                ->from( $table )
                ->where( $table . '.id', '=', $param->value )
                ->table( );
        }
    }

    /*   */
    public function __set( $field, $value ) {
        if ( isset( $this->params[$field] ) ) {
            $this->params[$field]->value = $value;
            $this->params[$field]->sign = ' = ';
            $this->params[$field]->operand = 'AND';
        }
        return $this;
    }

    /*   */
    public function __unset( $field ) {
        if ( isset( $this->params[$field] ) ) {
            $this->params[$field]->value = '';
            $this->params[$field]->sign = '';
            $this->params[$field]->operand = '';
        }
        return $this;
    }

    /*   */
    public function __call( $function, $data = [] ) {
        try{
            if( isset( $this->params[$function] ) && $this->params[$function]->value == '' ) {
                if ( count( $data ) == 1 ) {
                    $data[1] = $data[0];
                    $data[0] = '=';
                    $data[2] = ' AND ';
                } elseif( count( $data ) == 2 ) {
                    $data[2] = ' AND ';
                }
                $this->params[$function]->sign = $data[0];
                $this->params[$function]->value = $data[1];
                $this->params[$function]->operand = $data[2];
                return $this;
            }

            if ( isset( $this->params[$function] ) && $this->params[$function]->value != '' ) {
                if ( count( $data ) == 1 ) {
                    $data[1] = $data[0];
                    $data[0] = '=';
                    $data[2] = ' AND ';
                } elseif( count( $data ) == 2 ) {
                    $data[2] = ' AND ';
                }
                $this->where[$function] = $data[1];
                $this->where_signs[$function] = $data[0];
                $this->where_operands[$function] = $data[2];
                return $this;
            }

            if( class_exists( $function . '_model' ) && isset( $this->has[ lcfirst( $function ) ] ) ) {
                $model = $function . '_model';
                $object = new $model( $data[0] );
                $param = $this->has[ lcfirst( $function )];
                if( $object->$param == $this->id ) {
                    return $object;
                } else {
                    return FALSE;
                }
            }
            if( class_exists( $function . '_model' ) && isset( $this->belongs[lcfirst( $function )] ) ) {
                $model = $function . '_model';
                $param = $this->belongs[lcfirst( $function )];
                $object = new $model( $this->$param );
                if( $object instanceof Model ) {
                    return $object;
                }
                return FALSE;
            }
            if ( isset( $this->params[ lcfirst( $function ) . '_id'] ) ) {
                $function = lcfirst( $function );
                foreach( $this->belongs as $belong => $value ) {
                    if( strstr( $belong, ' AS ' . $function ) ) {
                        $array = explode( ' AS ' . $function, $belong );
                        $model = ucfirst( $array[0] ) . '_model';
                        $param = $function . '_id';
                        $object = new $model( $this->$param );
                        if( $object instanceof Model ) {
                            return $object;
                        }
                    }
                }
                return FALSE;
            }
            elseif( ! method_exists( $this, $function ) ) {
                throw new Exception( 'Method <b>' . $function . '( )</b> was not defined in class <b>' . get_class( $this ) . '</b>! ' );
            }
        } catch( Exception $e ) {
            die( $e->getMessage( ) );
        }

    }

    /* Методы для работы с таблицами БД */

    /* Возвращает заполненную модель, соответствующую записи с id = $id   */
    public function find( $id = NULL ) {
        try{
            if( $id === NULL ) {
                throw new Exception( 'Argument <b>$id</b> can not be <b>NULL! </b>' );
            }
            $object = $this->id( $id )->one();
            if( is_object( $object ) ) {
                $array = get_object_vars( $object );
                foreach( $array as $k=>$v ) {
                    if( isset( $this->params[$k] ) ) {
                        $this->params[$k]->value = $v;
                        $this->params[$k]->sign = '=';
                        $this->params[$k]->operand = 'AND';
                    }
                }
            }
            return $this;
        } catch( Exception $e ) {
            die( $e->getMessage( ) );
        }

    }

    /*    */
    public function dist( $param ) {
        try{
            if( ! isset( $this->params[ $param ] ) ) {
                throw new Exception( 'The model <b>' . get_class( $this ) . '</b> does not have property <b>' . $param . '</b>! ' );
            }
            $result = $this->sql
                ->select( array( 'DISTINCT ' . $param ) )
                ->from( $this->table )
                ->get( );
            $this->clear( );
            return $result;
        }catch( Exception $e ) {
            die( $e->getMessage( ) );
        }
    }

    /* Проверяет, есть ли в таблице запись со значением поля
    $field соответствующим значению свойства модели, в случае успеха возвращает TRUE  */
    public function check( $field ) {
        try{
            if( ! isset( $this->params[$field] ) ) {
                throw new Exception( 'The model <b>' . get_class( $this ) . '</b> does not have property <b>' . $field . '</b>! ' );
            }
            $what = array( 'COUNT( ' . $field . ' ) AS count' );
            $this->sql->select( $what )->from( $this->table );
            $i=0;
            foreach( $this->params as $k ) {
                if ( $k->value != '' ) {
                    $this->sql->where( $k->key, $k->sign, $k->value, $k->operand );
                    $i++;
                }
            }
            $result = $this->sql->get( );
            $this->clear( );
            $count = $result[0]->count;
            if( $count > 0 ) {
                return TRUE;
            } else {
                return FALSE;
            }
        }catch( Exception $e ) {
            die( $e->getMessage( ) );
        }
    }

    /* Возвращает целое число, соответствующее количеству записей в таблице   */
    public function counter( ) {
        $what = array( 'COUNT( * ) AS count' );
        $this->sql->select( $what )->from( $this->table );
        $i=0;
        foreach( $this->params as $k ) {
            if ( $k->value != '' ) {
                $this->sql->where( $k->key, $k->sign, $k->value, $k->operand );
                $i++;
            }
        }
        $result = $this->sql->get( );
        $this->clear( );
        $count = $result[0]->count;
        return $count;
    }

    /* Возвращает массив объектов,     */
    public function get( 
        $fields = [],
        $offset = NULL,
        $limit = NULL,
        $order_by = NULL,
        $group_by = NULL,
        $having = NULL ) {
        $this->sql->select( $fields )->from( $this->table );
        $i=0;
        foreach( $this->params as $k ) {
            if ( $k->value != '' ) {
                $this->sql->where( $this->table . '.' . $k->key, $k->sign, $k->value, $k->operand );
                if( isset( $this->where[$k->key] ) ) {
                    $this->sql->where(
                        $this->table . '.' . $k->key,
                        $this->where_signs[$k->key],
                        $this->where[$k->key],
                        $this->where_operands[$k->key]
                    );
                }
                $i++;
            }
        }
        if( ! is_null( $group_by ) ) {
            $this->sql->group( $group_by );
        }
        if( ! is_null( $having ) ) {
            $array = explode( '|', $having );
            $this->sql->having( $array[0],$array[1], $array[3] );
        }
        if( ! is_null( $order_by ) ) {
            $array = explode( '|', $order_by );
            $this->sql->order( $array[0],$array[1] );
        } elseif( $order_by === NULL ) {
            $this->sql->order( 'id','DESC' );
        }
        if( is_null( $offset ) && ! is_null( $limit ) ) {
            $this->sql->limit( NULL, $limit );
        }
        if( ! is_null( $offset ) && ! is_null( $limit ) ) {
            $this->sql->limit( $offset,$limit );
        }
        if( Config::$debug_model_get ) {
            vd( $this->sql->query );
        }
        $this->clear( );
        return $this->sql->get( );
    }

    public function dget( $fields = [],
        $offset = NULL,
        $limit = NULL,
        $order_by = NULL,
        $group_by = NULL,
        $having = NULL ) {
        $this->sql->select( $fields )->from( $this->table );
        $i=0;
        foreach( $this->params as $k ) {
            if ( $k->value != '' ) {
                $this->sql->where( $this->table . '.' . $k->key, $k->sign, $k->value, $k->operand );
                if( isset( $this->where[$k->key] ) ) {
                    $this->sql->where(
                        $this->table . '.' . $k->key,
                        $this->where_signs[$k->key],
                        $this->where[$k->key],
                        $this->where_operands[$k->key]
                    );
                }
                $i++;
            }
        }
        if( ! is_null( $group_by ) ) {
            $this->sql->group( $group_by );
        }
        if( ! is_null( $having ) ) {
            $array = explode( '|', $having );
            $this->sql->having( $array[0],$array[1], $array[3] );
        }
        if( ! is_null( $order_by ) ) {
            $array = explode( '|', $order_by );
            $this->sql->order( $array[0],$array[1] );
        } elseif( $order_by === NULL ) {
            $this->sql->order( 'id','DESC' );
        }
        if( is_null( $offset ) && ! is_null( $limit ) ) {
            $this->sql->limit( NULL, $limit );
        }
        if( ! is_null( $offset ) && ! is_null( $limit ) ) {
            $this->sql->limit( $offset,$limit );
        }
        if( Config::$debug_model_get ) {
            vd( $this->sql->query );
        }
        return $this->sql->get( );
    }

    /* Возвращает массив объектов, свойства которых соответствуют перечисленным в массиве $fields полям   */
    public function together( $fields = [],
        $offset = NULL,
        $limit = NULL,
        $order_by = NULL,
        $group_by = NULL,
        $having = NULL, $tables = [] ) {

        $this->sql->select( $fields )->from( $this->table );
        foreach( $this->has as $k=>$v ) {
            if( ! empty( $tables ) ) {
                if( in_array( $k, $tables ) ) {
                    $this->sql->join( $k, $k . '.' . $v . ' = ' . $this->table . '.id' );
                }

            } else {
                $this->sql->join( $k, $k . '.' . $v . ' = ' . $this->table . '.id' );
            }
        }
        foreach( $this->belongs as $k=>$v ) {
            if( ! empty( $tables ) ) {
                if( in_array( $k,$tables ) ) {

                    $this->sql->join( $k, $k . '.id = ' . $this->table . '.' . $v );

                }
            } else {
                $this->sql->join( $k, $k . '.id = ' . $this->table . '.' . $v );
            }
        }
        foreach( $this->params as $k ) {
            if ( $k->value != '' ) {
                $this->sql->where( $this->table . '.' . $k->key, $k->sign, $k->value, $k->operand );
                if( isset( $this->where[$k->key] ) ) {
                    $this->sql->where(
                        $this->table . '.' . $k->key,
                        $this->where_signs[$k->key],
                        $this->where[$k->key],
                        $this->where_operands[$k->key]
                    );
                }
            }
        }
        if( ! is_null( $group_by ) ) {
            $this->sql->group( $group_by );
        }
        if( ! is_null( $having ) ) {
            $array = explode( '|', $having );
            $this->sql->having( $array[0],$array[1], $array[3] );
        }
        if( ! is_null( $order_by ) ) {
            $array = explode( '|', $order_by );
            $this->sql->order( $array[0],$array[1] );
        } elseif( $order_by === NULL ) {
            $this->sql->order( 'id','DESC' );
        }
        if( is_null( $offset ) && ! is_null( $limit ) ) {
            $this->sql->limit( NULL, $limit );
        }
        if( ! is_null( $offset ) && ! is_null( $limit ) ) {
            $this->sql->limit( $offset,$limit );
        }
        if( Config::$debug_model_together ) {
            vd( $this->sql->query );
        }
        $this->clear( );
        return $this->sql->get( );
    }

    public function dtogether( $fields = [],
        $offset = NULL,
        $limit = NULL,
        $order_by = NULL,
        $group_by = NULL,
        $having = NULL, $tables = [] ) {

        $this->sql->select( $fields )->from( $this->table );
        foreach( $this->has as $k=>$v ) {
            if( ! empty( $tables ) ) {
                if( in_array( $k, $tables ) ) {
                    $this->sql->join( $k, $k . '.' . $v . ' = ' . $this->table . '.id' );
                }

            } else {
                $this->sql->join( $k, $k . '.' . $v . ' = ' . $this->table . '.id' );
            }
        }
        foreach( $this->belongs as $k=>$v ) {
            if( ! empty( $tables ) ) {
                if( in_array( $k,$tables ) ) {

                    $this->sql->join( $k, $k . '.id = ' . $this->table . '.' . $v );

                }
            } else {
                $this->sql->join( $k, $k . '.id = ' . $this->table . '.' . $v );
            }
        }
        foreach( $this->params as $k ) {
            if ( $k->value != '' ) {
                $this->sql->where( $this->table . '.' . $k->key, $k->sign, $k->value, $k->operand );
                if( isset( $this->where[$k->key] ) ) {
                    $this->sql->where(
                        $this->table . '.' . $k->key,
                        $this->where_signs[$k->key],
                        $this->where[$k->key],
                        $this->where_operands[$k->key]
                    );
                }
            }
        }
        if( ! is_null( $group_by ) ) {
            $this->sql->group( $group_by );
        }
        if( ! is_null( $having ) ) {
            $array = explode( '|', $having );
            $this->sql->having( $array[0],$array[1], $array[3] );
        }
        if( ! is_null( $order_by ) ) {
            $array = explode( '|', $order_by );
            $this->sql->order( $array[0],$array[1] );
        } elseif( $order_by === NULL ) {
            $this->sql->order( 'id','DESC' );
        }
        if( is_null( $offset ) && ! is_null( $limit ) ) {
            $this->sql->limit( NULL, $limit );
        }
        if( ! is_null( $offset ) && ! is_null( $limit ) ) {
            $this->sql->limit( $offset,$limit );
        }
        if( Config::$debug_model_together ) {
            vd( $this->sql->query );
        }
        return $this->sql->get( );
    }

    /* Возвращает объект, со свойствами соответствующими значениям полей первой найденной записи в БД или FALSE    */
    public function one() {
        $this->sql->select( )->from( $this->table );
        $i=0;
        foreach( $this->params as $k ) {
            if ( $k->value != '' ) {
                $this->sql->where( $k->key, $k->sign, $k->value, $k->operand );
                $i++;
            }
        }
        $this->sql->limit( NULL, 1 );
        if( Config::$debug_model_one ) {
            vd( $this->sql->query );
        }
        $data =  $this->sql->get( );
        $this->clear( );
        if( ! empty( $data ) ) {
            return $data[0];
        } else {
            return FALSE;
        }
    }

    /* Добавление записи в таблицу БД со значениями полей, соответствующими свойствам модели    */
    public function set( ) {
        if( Config::$debug_model_set ) {
            vd( $this->sql->query );
        }
        $this->sql
            ->insert_into( $this->table )
            ->set( $this->params );
        if( Config::$debug_model_set ) {
            vd( $this->sql->query );
        }
        $result = $this->sql->ins( );
        $this->clear( );
        return $result;
    }

    /* "Жесткое" удаление, метод, которым лучше не пользоваться   */
    public function del( $limit = NULL, $offset = NULL ) {
        try{
            if( ! Config::$soft_delete ) {
                $this->sql->delete( )->from( $this->table );
                $i=0;
                foreach( $this->params as $k ) {
                    if ( $k->value != '' ) {
                        $this->sql->where( $k->key, $k->sign, $k->value, $k->operand );
                        $i++;
                    }
                }
                $this->sql->limit( $offset, $limit );
                $this->clear( );
                return $this->sql->del( );
            } else {
                throw new Exception( 'You should use $this->softDel( ) method <br>or disable this option in the configuration file! ' );
            }
        }catch( Exception $e ) {
            die( $e->getMessage( ) );
        }
    }

    /* "Мягкое" удаление   */
    public function softDel( ) {
        return $this->deleted_at( now( ) )->upd( );

    }

    /* Откат softdelete( )    */
    public function restore( ) {
        return $this->deleted_at( '0000-00-00 00:00:00' )->upd( );
    }

    /* Вовзращает модель со свойством  deleted_at  равным значению по умолчанию,
    т . е .  не удаленным с помощью метода softdelete( )   */
    public function notDeleted( ) {
        $this->deleted_at( '=', '0000-00-00 00:00:00','AND' );
        return $this;
    }

    /* Вовзращает модель со свойством  deleted_at не равным значению по умолчанию,
     т . е .  удаленным с помощью метода softdelete( )  */
    public function deleted( ) {
        $this->deleted_at( '<>', '0000-00-00 00:00:00','AND' );
        return $this;
    }

    /* Вовзращает модель со свойством  touched_at( '>', $need, 'AND' ),
     где $need - текущее время минус указанный интервал в секундах $seconds_interval
    Например, если текущее время на момент запроса было 2015-01-17 20:00:00,
    то $this->touched( 600 )->get( ) вернет массив объектов, у которых поле
    touched_at обновлялось в течении прошедших 10 минут */
    public function touched( $seconds_interval ) {
        try{
            if( ! is_int( $seconds_interval ) || $seconds_interval < 1 ) {
                throw new Exception ( 'The method <b>touched()</b> of <b>Model</b> needs positive integer argument! ' );
            }
            $time = strtotime( now( ) ) - $seconds_interval;
            $need = date( 'Y-m-d H:i:s', $time );
            $this->touched_at( '>', $need, 'AND' );
            return $this;
        }catch( Exception $e ) {
            die( $e->getMessage( ) );
        }
    }

    public function created( $seconds_interval ) {
        try{
            if( ! is_int( $seconds_interval ) || $seconds_interval < 1 ) {
                throw new Exception ( 'The method <b>created( )</b> of <b>Model</b> needs positive integer argument! ' );
            }
            $time = strtotime( now( ) ) - $seconds_interval;
            $need = date( 'Y-m-d H:i:s', $time );
            $this->created_at( '>', $need, 'AND' );
            return $this;
        }catch( Exception $e ) {
            die( $e->getMessage( ) );
        }

    }

    /* Метод, определяющий, по каким полям будут указаны в условии WHERE запроса к БД .
    Аргумент - строка, в которой через запятую перечислены нужные поля .
    Возвращает модель */
    public function where( $params ) {
        $array = explode( ',', $params );
        foreach ( $array as $i ) {
            trim( $i );
            try{
                if( ! isset( $this->params[$i] ) ) {
                    throw new Exception ( '' );
                }
                if( isset( $this->where[$i] ) ) {
                    array_push( $this->where, $this->params[$i]->value );
                    array_push( $this->where_signs, $this->params[$i]->sign );
                    array_push( $this->where_operands, $this->params[$i]->operand );
                } else {
                    $this->where[$i] = $this->params[$i]->value;
                    $this->where_signs[$i] = $this->params[$i]->sign;
                    $this->where_operands[$i] = $this->params[$i]->operand;
                }
                return $this;

            } catch( Exception $e ) {
                die( $e->getMessage( ) );
            }
        }
    }

    /*  Обновление записей в таблице, возвращает TRUE в случае успеха  */
    public function upd( ) {
        $params = [];
        foreach( $this->params as $k ) {
            if( $k->value != '' && ! isset( $this->where[$k->key] ) ) {
                $params[$k->key] = $k;
            }
        }
        $this->sql
            ->update( $this->table )
            ->set( $params );
        foreach( $this->params as $k ) {
            if( isset( $this->where[$k->key] ) && $k->value != '' ) {
                $this->sql->where( $k->key,$this->where_signs[$k->key],
                    $this->where[$k->key],$this->where_operands[$k->key] );
            }
        }
        if( Config::$debug_model_upd ) {
            vd( $this->sql->query );
        }
        $this->sql->upd( );
        $this->clear( );
        return TRUE;
    }

    private function intUpd( ) {
        $params = [];
        foreach( $this->params as $k ) {
            if( $k->value != '' && ! isset( $this->where[$k->key] ) ) {
                $params[$k->key] = $k;
            }
        }
        $this->sql
            ->update( $this->table )
            ->setWithoutQuotes( $params );
        foreach( $this->params as $k ) {
            if( isset( $this->where[$k->key] ) && $k->value != '' ) {
                $this->sql->where(
                    $k->key,
                    $this->where_signs[$k->key],
                    $this->where[$k->key],
                    $this->where_operands[$k->key]
                );
            }
        }
        if( Config::$debug_model_upd ) {
            vd( $this->sql->query );
        }
        $this->sql->upd( );
        $this->clear( );
        return TRUE;
    }

    /* Приватный метод, очищает модель после выполнения запроса к БД .
    Вызывается внутри других методов, возвращает пустую модель */
    private function clear( ) {
        foreach( $this->params as $param ) {
            $param->value = '';
            $param->sign = '';
            $param->operand = '';
        }
        $this->where = [];
        $this->where_signs = [];
        $this->where_operands = [];
        return $this;
    }

    public function incr( $field ) {
        try{
            if( ! isset( $this->params[$field] ) ) {
                throw new Exception( 'The model <b>' . get_class( $this ) . '</b> does not have property <b>' . $field . '</b>! ' );
            }
            $this
                ->$field( '=', '`' . $field . '` + 1' )
                ->intUpd( );

        }catch( Exception $e ) {
            die( $e->getMessage( ) );
        }
    }

    public function decr( $field ) {
        try{
            if( ! isset( $this->params[$field] ) || $field === 'id' ) {
                throw new Exception( 'The model <b>' . get_class( $this ) . '</b> does not have property <b>' . $field . '</b>! ' );
            }
            $this
                ->$field( '=', '`' . $field . '` - 1' )
                ->intUpd( );
        }catch( Exception $e ) {
            die( $e->getMessage( ) );
        }
    }

    public function query( $query ) {
        $this->sql->query = $query;
        return $this->sql->query( );
    }

    public function range( $field, $range, $including = FALSE ) {
        if( isset( $this->params[$field] ) ) {
            $array = explode( '-',$range );
            $min = $array[0];
            $max = $array[1];
            if( $including ) {
                $this
                    ->$field( '>=',$min,'AND' )
                    ->$field( '<=',$max,'AND' )
                    ->where( $field );
            } else {
                $this
                    ->$field( '>',$min,'AND' )
                    ->$field( '<',$max )
                    ->where( $field );
            }
        }
        return $this;
    }

    /* Дополнительные методы | Авторизация пользователей */


    /* Обновляет поле touched_at для записи с id = $id */
    public function touch( $id ) {
        $this
            ->id( $id )
            ->where( 'id' )
            ->touched_at( now( ) )
            ->upd( );
    }

    /* Ищет запись в таблице со значением поля $field равным $value и полем password равным $password
    Если такая запись есть, возвращает объект, соответствующий этой записи и обновляет поле logined_at этой записи */

    public function login( $field, $value, $password ) {
        $object = $this->$field( $value )->password( $password )->notDeleted( )->one();
        if( $object ) {
            $this->id( $object->id )->logined_at( now( ) )->where( 'id' )->upd( );
            foreach( $object as $k=>$v ) {
                $this->protected->$k = $v;
            }
            return  $this->protected;
        }
        return FALSE;
    }

}

