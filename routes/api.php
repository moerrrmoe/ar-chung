<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Services\BotHandler;
use TexHub\Telegram\Telegram;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/chat/extract', [ChatController::class, 'extract']);

Route::post('/webhook', function (Request $request) {
    response('done', 200)->send();
    try {
        $handler = new BotHandler();
        $bot = Telegram::bot(env('TELEGRAM_BOT_TOKEN'));
        $update_content = file_get_contents('php://input');
        $handler->handleRequest($bot, $update_content);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Webhook error: ' . $e->getMessage(), [
            'exception' => $e,
        ]);
    }
    exit;
});
