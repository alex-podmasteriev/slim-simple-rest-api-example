<?php
// Home page
$app->get('/', function( $req, $res, $args ) {
    $this->renderer->render( $res, 'index.phtml', []);
});

// List book page
$app->get('/books/page/{page}', function ( $req, $res, $args ) {
    $this->renderer->render( $res, 'books.phtml', [ 'books'=> $this->Book->notDeleted()->together(['book.id', 'book.name','author.firstname', 'author.lastname']) ] );
});

// Single book page
$app->get('/book/{id}', function ( $req, $res, $args) {
    $this->renderer->render( $res, 'book.phtml', [ 'book'=> $this->Book->id($args['id'])->notDeleted()->together(['book.id', 'book.name','author.firstname', 'author.lastname'])[0] ] );
});

// Create book
$app->get('/create/book', function($req, $res, $args){
    $this->renderer->render( $res, 'create_book.phtml', [ 'authors'=> $this->Author->notDeleted()->get() ] );
});

$app->post('/book', function( $req, $res, $args ) {
    $data = $req->getParsedBody();
    $validator = new Validator( $data );
    $validator->make();
    if ( $validator->success() ) {
        $id = $this->Book->author_id( $data['author_id'] )->name( $data['name'] )->set();
        redirect( '/book/' . $id );
    }
    back();
});

// Edit book
$app->get('/edit/book/{id}', function($req, $res, $args){
    $book =  $this->Book->id($args['id'])->one();
    $this->renderer->render( $res, 'edit_book.phtml', [ 'book'=> $book, 'authors' => $this->Author->notDeleted()->get() ] );
});

$app->post('/book/{id}', function($req, $res, $args){
    $data = $req->getParsedBody();
    $validator = new Validator( $data );
    $validator->make();
    if ( $validator->success() ) {
        $this->Book->author_id( $data['author_id'] )->name( $data['name'] )->id($args['id'])->where('id')->upd();
        redirect( '/book/' . $args['id'] );
    }
    back();
});

// Delete book
$app->get('/delete/book/{id}', function( $req, $res, $args ) {
    $this->Book->id($args['id'])->where('id')->softDel();
    redirect('/books/page/1');
});

