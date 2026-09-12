<?php

use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Auth/Login');
});

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('register');

Route::get('/forgot-password', function () {
    return Inertia::render('Auth/ForgotPassword');
})->name('password.request');

Route::middleware('auth')->group(function (): void {
    Route::get('/perfil', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Profile', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('profile');

    Route::post('/logout', LogoutController::class)->name('logout');

    Route::get('/carteiras', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Financial/Wallets', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('financial.wallets');

    Route::get('/contas', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Financial/Accounts', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('financial.accounts');

    Route::get('/cartoes-de-credito', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Financial/CreditCards', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('financial.credit-cards');

    Route::get('/dashboard-financeiro', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Financial/Dashboard', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('financial.dashboard');

    Route::get('/transacoes', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Financial/Transactions', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('financial.transactions');

    Route::get('/recorrencias', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Financial/RecurringTransactions', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('financial.recurring-transactions');

    Route::get('/categorias', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Financial/Categories', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('financial.categories');

    Route::get('/metas', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Financial/Goals', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('financial.goals');

    Route::get('/investimentos', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Financial/Investments', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('financial.investments');

    Route::get('/open-finance', function (Request $request) {
        $user = $request->user();

        return Inertia::render('Financial/OpenFinance', [
            'auth' => ['user' => $user?->only(['id', 'name', 'email'])],
        ]);
    })->name('financial.open-finance');
});
