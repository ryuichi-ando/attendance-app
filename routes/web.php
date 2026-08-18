<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', function () {
    return view('user.register');
});

Route::get('/login', function () {
    return view('user.user-application-list');
});