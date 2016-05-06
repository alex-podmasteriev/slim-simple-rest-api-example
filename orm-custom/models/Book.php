<?php
class Book extends Model {
    protected $table = 'book';
    public $belongs = [
        'author'=>'author_id'
    ];
}