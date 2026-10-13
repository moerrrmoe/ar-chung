<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use TexHub\Telegram\Telegram;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/set-webhook', function () {
    $exact_root_url = 'https://' . $_SERVER['HTTP_HOST'];
    $bot = Telegram::bot(env('TELEGRAM_BOT_TOKEN'));
    $res = $bot->setWebhook($exact_root_url . '/api/webhook');
    echo json_encode($res, JSON_PRETTY_PRINT);
    return;
});
