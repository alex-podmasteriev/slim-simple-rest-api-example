<?php
class Author extends Model{
    protected $table='author';
    public $has=[
        'book'=>'author_id'
    ];
}