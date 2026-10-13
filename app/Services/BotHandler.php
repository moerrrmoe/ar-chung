<?php

namespace App\Services;

use MoeMizrak\LaravelOpenrouter\DTO\ChatData;
use MoeMizrak\LaravelOpenrouter\DTO\MessageData;
use TexHub\Telegram\Handler\UpdateHandler;
use TexHub\Telegram\Keyboard\InlineKeyboard;
use TexHub\Telegram\Keyboard\Button;
use MoeMizrak\LaravelOpenrouter\Facades\LaravelOpenRouter;
use MoeMizrak\LaravelOpenrouter\Types\RoleType;
use MoeMizrak\LaravelOpenrouter\DTO\ResponseFormatData;


class BotHandler extends UpdateHandler
{
    public function start(): void
    {
        $this->chat->message('Welcome! 👋')->send();
    }

    public function onText(string $text): void
    {
        $chatType = $this->update->toArray()['message']['chat']['type'];
        $username = isset($this->update->from()['username']) ? '@' . $this->update->from()['username'] : null;
        $first_name = $this->update->from()['first_name'];

        if ($chatType === 'supergroup') {
            if (!$this->am_i_admin($this->chatId())) {
                return;
            }

            if ($this->is_spam($text)) {
                $this->bot->deleteMessage($this->chatId(), $this->update->messageId());
                $this->bot->sendMessage($this->chatId(), "⛔Spam detected!" . ($username ? $username : $first_name) . " 's message has been deleted.");
                return;
            }
            $ai_reply = $this->generate_ai_reply($text);
            $this->bot->sendMessage($this->chatId(), $ai_reply, [
                'reply_parameters' => [
                    'message_id' => $this->update->messageId(),
                ],
            ]);
        }
    }

    public function generate_ai_reply(string $text): string
    {
        $chatData = new ChatData(
            messages: [new MessageData(
                content: "You are a Female burmese AI chat bot, named Hnin Si, inside a telegram group chat who always reply appropriate message to the group members in burmese. Reply this message ." . $text,
                role: RoleType::USER
            )],
            model: "google/gemini-3.5-flash-lite",
            response_format: new ResponseFormatData(
                type: "json_schema",
                json_schema: [
                    "name" => "bot_reply",
                    "strict" => true,
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "reply" => ["type" => "string"]
                        ],
                        "required" => ["reply"]
                    ]
                ]
            )
        );
        $response = LaravelOpenRouter::chatRequest($chatData);
        $decoded_res = json_decode($response->choices[0]['message']['content'], true);
        return $decoded_res['reply'];
    }

    public function is_spam(string $text)
    {
        if (str_contains($text, 'http') || str_contains($text, 'https') || str_contains($text, '@') || str_contains($text, 't.me') || str_contains($text, '.com')) {
            return true;
        }
        return false;
    }

    public function am_i_admin($chatId)
    {
        $membership = $this->bot->getChatMember(
            $chatId,
            env('TELEGRAM_BOT_ID')
        );
        if ($membership['status'] === 'administrator') {
            return true;
        }
        return false;
    }
}
