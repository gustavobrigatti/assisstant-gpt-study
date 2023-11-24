<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\MessagesController;

//-----------------------------------------------------------// - Rotas de autenticação
Route::group([
    'prefix' => 'auth',
    'as' => 'auth.',
], function () {
    Route::get('/', [AuthController::class, 'login'])->name('login');
    Route::post('login', [AuthController::class, 'makeLogin'])->name('makeLogin');
    Route::post('loginCode', [AuthController::class, 'makeLoginCode'])->name('makeLoginCode');
    Route::get('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('forget-password', [AuthController::class, 'forgetPassword'])->name('forgetPassword');
    Route::post('forget-password', [AuthController::class, 'forgetPassword'])->name('forgetPassword');
    Route::get('reset-password/{token}', [AuthController::class, 'resetPassword'])->name('resetPassword');
    Route::post('reset-password-action', [AuthController::class, 'resetPasswordAction'])->name('resetPasswordAction');
});

//-----------------------------------------------------------// - Rotas do painel de administrador
Route::group([
    'middleware' => 'auth',
    'prefix' => 'admin',
    'as' => 'admin.'
], function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('index');
    Route::group([
        'prefix' => 'messages',
        'as' => 'messages.'
    ], function () {
        Route::get('/run', [MessagesController::class, 'runAssistant'])->name('run');
    });
    Route::resource('messages', MessagesController::class);
});

Route::get('/makeUser', function () {
    $user = new \App\Models\User();
    $user->name = 'Impetus';
    $user->lastname = 'Sistemas';
    $user->description = 'Administrador do sistema.';
    $user->email = 'admin@impetussistemas.com.br';
    $user->password = \Illuminate\Support\Facades\Hash::make('123456');
    $user->status = 1;
    $user->office = 'Super Admin';
    $user->save();
});
