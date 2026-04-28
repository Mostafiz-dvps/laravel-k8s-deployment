<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Laravel Kubernetes Deployment Test';
});

Route::get('/health', function () {
    return response('OK', 200);
});
