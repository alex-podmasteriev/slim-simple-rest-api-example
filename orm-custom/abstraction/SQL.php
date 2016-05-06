<?php
class SQL {
    public $query = '';
    public $mysqli;

    public function __construct() {
        if( $this->mysqli === NULL ) {
            $this->mysqli = Database::getConnect();
        }
    }

    public function select( $fields = [] ) {
        $string = '';
        if( !empty($fields) ){
            foreach($fields as $field){
                $field  = '`'.$field.'`';
                $field = str_replace('.', '`.`', $field );
                $field = str_replace(' AS ', '` AS `', $field);
                $field = str_replace('`MAX', 'MAX', $field);
                $field = str_replace('`MIN', 'MIN', $field);
                $field = str_replace('`AVG', 'AVG', $field);
                $field = str_replace('`SUM', 'SUM', $field);
                $field = str_replace('`COUNT', 'COUNT', $field);
                $field = str_replace('`DISTINCT ', 'DISTINCT `', $field);
                $field = str_replace('`(', '(', $field);
                $field = str_replace(')`', ')', $field);
                $field = str_replace(', ', '`, `', $field);
                $field = str_replace('(', '(`', $field);
                $field = str_replace(')', '`)', $field);
                $field = str_replace(')`', '`)', $field);
                $field = str_replace('`*`', '*', $field);
                $string .= $field.', ';
            }
            $string = substr( $string, 0, -2);
        } else {
            $string = ' * ';
        }
        $this->query = 'SELECT '.$string;
        return $this;
    }


    public function from($table){
        $this->query .= ' FROM '.'`'.$table.'` ';
        return $this;
    }

    public function join($table, $on){
        $array = explode(' AS ', $table);
        if (count($array) == 2){
            $alias = $array[1];
        } else{
            $alias = $table;
        }
        $on = str_replace($table, $alias, $on);
        $table = str_replace(' AS ', '` AS `', $table);
        $on  = '`'.$on.'`';
        $on = str_replace('.', '`.`', $on );
        $on = str_replace(' = ', '` = `' , $on);
        $this->query .= ' JOIN '.'`'.$table.'` ON '.$on;

        return $this;
    }

    public function where($field, $sign, $value, $condition = NULL){
        if(!strstr($this->query, 'WHERE')){
            $where = ' WHERE ';
        } else {
            $where = ' '.$condition.' ';
        }

        $field  = '`'.$field.'` ';
        $field = str_replace('.', '`.`', $field );
        if($sign == 'IN'){
            $value = " ".$value."";
        } else {
            $value = " '".$value."'";
        }
        $this->query .= $where. $field. $sign. $value;
        return $this;
    }


    public function group($field){
        $field  = '`'.$field.'`';
        $field = str_replace('.', '`.`', $field );
        $field = str_replace(' AS ', '` AS `', $field);
        $field = str_replace('`MAX', 'MAX', $field);
        $field = str_replace('`MIN', 'MIN', $field);
        $field = str_replace('`AVG', 'AVG', $field);
        $field = str_replace('`SUM', 'SUM', $field);
        $field = str_replace('`COUNT', 'COUNT', $field);
        $field = str_replace('(', '(`', $field);
        $field = str_replace(')`', '`)', $field);
        $this->query .= ' GROUP BY ' . $field;
        return $this;
    }

    public function having($field, $sign, $value, $condition = NULL){
        if(!strstr($this->query, 'HAVING')){
            $having = ' HAVING ';
        } else {
            $having = ' '.$condition.' ';
        }
        $field  = '`'.$field.'`';
        $field = str_replace('.', '`.`', $field );
        $field = str_replace(' AS ', '` AS `', $field);
        $field = str_replace('`MAX', 'MAX', $field);
        $field = str_replace('`MIN', 'MIN', $field);
        $field = str_replace('`AVG', 'AVG', $field);
        $field = str_replace('`SUM', 'SUM', $field);
        $field = str_replace('`COUNT', 'COUNT', $field);
        $field = str_replace('(', '(`', $field);
        $field = str_replace(')`', '`) ', $field);
        $value = " '".$value."'";

        $this->query .= $having.$field.$sign.$value;
        return $this;
    }

    public function order($field, $how = 'DESC'){
        $field  = '`'.$field.'`';
        $field = str_replace('.', '`.`', $field );
        $field = str_replace('`MAX', 'MAX', $field);
        $field = str_replace('`MIN', 'MIN', $field);
        $field = str_replace('`AVG', 'AVG', $field);
        $field = str_replace('`SUM', 'SUM', $field);
        $field = str_replace('`COUNT', 'COUNT', $field);
        $field = str_replace('(', '(`', $field);
        $field = str_replace(')`', '`)', $field);
        $this->query .= ' ORDER BY '.$field.' '.$how;
        return $this;
    }

    public function limit($offset = NULL, $limit){
        if(is_null($offset)){
            $this->query .= ' LIMIT '.$limit;
        } else {
            $this->query .= ' LIMIT ' . $offset . ', ' . $limit;
        }
        return $this;
    }


    public function insert_into($table){
        $this->query .= ' INSERT'.' INTO '.' `'.$table.'` ';
        return $this;
    }

    public function update($table){
        $this->query .= ' UPDATE '.'`'.$table.'` ';
        return $this;
    }

    public function set($fields = []){
        if(!empty($fields)){

            if(strstr($this->query, 'INSERT')){
                $set='';
                $values ='';
                foreach($fields as $k){
                    if($k->value != '') {
                        $key = '`' . $k->key . '`';
                        $key = str_replace('.', '`.`', $key);
                        $set .= $key . ', ';
                        $value = "'" . $k->value . "'";

                        $values .= $value . ', ';
                    }
                }
                $set = substr( $set, 0, -2);
                $values = substr( $values, 0, -2);
                $string = ' ('.$set.') VALUES ('.$values.") ";
                $this->query .= $string;
            }

            if( strstr($this->query, 'UPDATE') ) {
                $string = '';
                foreach($fields as $k){
                    if($k->value != '') {
                        $key = '`' . $k->key . '`';
                        $key = str_replace('.', '`.`', $key);
                        $value = "'" . $k->value . "'";
                        $string .= $key . ' = ' . $value . ', ';
                    }
                }
                $string = substr( $string, 0, -2);
                $string = ' SET '.$string;
                $this->query .= $string;
            }
        }
        return $this;
    }

    public function setWithoutQuotes($fields = []){
        if(!empty($fields)){

            if( strstr($this->query, 'UPDATE') ) {
                $string = '';
                foreach($fields as $k){
                    if($k->value != '') {
                        $key = '`' . $k->key . '`';
                        $key = str_replace('.', '`.`', $key);
                        $string .= $key . ' = ' . $k->value . ', ';
                    }
                }
                $string = substr( $string, 0, -2);
                $string = ' SET '.$string;
                $this->query .= $string;
            }
        }
        return $this;
    }

    public function delete(){
        $this->query = 'DELETE '.$this->query;

        return $this;    }

    //////////////////////////////////////////////

    public function get(){
        $query = mysqli_query($this->mysqli, $this->query);
        $data = [];
        if ($query) {
            $i=0;
            while ($row = mysqli_fetch_object($query))
            {
                $data[$i]=$row;
                $i++;
            }
        } else {
            $data = [];
        }
        $this->query = '';
        return $data;
    }

    public function table(){
        $query = mysqli_query($this->mysqli, $this->query);
        $data = [];
        if ($query) {
            while ( $row = mysqli_fetch_object( $query ) ) {
                $id = $row->id;
                $data[$id] = $row;
            }
        }
        $this->query = '';
        return $data[$id];
    }

    public function tables(){
        $query = mysqli_query($this->mysqli, $this->query);
        $data = [];
        if ($query)
        {
            while ($row = mysqli_fetch_object($query))
            {
                $id = $row->id;
                $data[$id] = $row;
            }
        }
        $this->query = '';
        return $data;
    }

    public function upd () {
        $this->mysqli->query( $this->query );
        $this->query = '';
        return;
    }

    public function ins () {
        $this->mysqli->query( $this->query );
        $this->query = '';
        return $this->mysqli->insert_id;
    }

    public function del(){
        $query = $this->mysqli->query( $this->query );
        $this->query = '';
        return $query;
    }
///////////////////////////////////////////////////////
    public function query($query = NULL){
        if($query != NULL){
            $this->query = $query;
        }
        $query = $this->mysqli->query( $this->query );
        $data = [];
        if($query){
            while ($row = mysqli_fetch_object($query))
            {
                $data[$row->id]=$row;
            }
        }
        return $data;
    }

    public function queryD($query = NULL){
        if($query != NULL){
            $this->query = $query;
        }
        $query = $this->mysqli->query( $this->query );
        $data = [];
        $i = 0;
        if($query){
            while ($row = mysqli_fetch_object($query))
            {
                $data[$i]=$row;
                $i++;
            }
        }
        return $data;
    }

    public function specialQuery($query){
        return $this->mysqli->query( $query );
    }


}

