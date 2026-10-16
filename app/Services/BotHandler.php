<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
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
        $chat_history = Cache::get('chat_history_' . $this->chatId()) ?? [];
        $chat_history[] = [
            'role' => 'user',
            'content' => $text,
        ];


        if ($chatType === 'private') {
            $ai_res = $this->generate_ai_reply($chat_history, "You are a flirty Female burmese AI chat bot, named Khoon, inside a telegram private chat who always reply appropriate message to user in burmese. Reply this message (last element of the chat_history array is last user message) . Describe last message's type in message_type field when reply. The type can be normal, gamble, unappropriate, or sex");
            if ($ai_res['message_type'] === 'normal') {
                $this->bot->sendMessage($this->chatId(), $ai_res['reply'], [
                    'reply_parameters' => [
                        'message_id' => $this->update->messageId(),
                    ],
                ]);
            } else if ($ai_res['message_type'] === 'gamble') {
                $this->bot->sendMessage($this->chatId(), "555mix ibet789 ကစားချင်ရင် Khoon ဆီမှာရတယ်နော် @Khoonmyatchel321", [
                    'reply_parameters' => [
                        'message_id' => $this->update->messageId(),
                    ],
                ]);
            } else if ($ai_res['message_type'] === 'unappropriate') {
                $this->bot->sendMessage($this->chatId(), "18+ ကြိုက်ရင် ဒီမှာ member လာဝင် @OaXiS69", [
                    'reply_parameters' => [
                        'message_id' => $this->update->messageId(),
                    ],
                ]);
            } else if ($ai_res['message_type'] === 'sex') {
                $this->bot->sendMessage($this->chatId(), "18+ ကြိုက်ရင် ဒီမှာ member လာဝင် @OaXiS69", [
                    'reply_parameters' => [
                        'message_id' => $this->update->messageId(),
                    ],
                ]);
            }

            $this->cache_chat_history($chat_history, $text, $ai_res['reply']);
        }

        if ($chatType === 'supergroup') {
            if (!$this->am_i_admin($this->chatId())) {
                return;
            }

            if ($this->is_spam($text)) {
                $this->bot->deleteMessage($this->chatId(), $this->update->messageId());
                $this->bot->sendMessage($this->chatId(), "⛔Spam detected!" . ($username ? $username : $first_name) . " 's message has been deleted.");
                return;
            }
            $ai_res = $this->generate_ai_reply($chat_history, "You are a flirty Female burmese AI chat bot, named Khoon, inside a telegram group chat who always reply appropriate message to the group members in burmese. Reply this message (last element of the chat_history array is last user message) . Describe last message's type in message_type field when reply. The type can be normal, gamble, unappropriate, or sex");
            if ($ai_res['message_type'] === 'normal') {
                $this->bot->sendMessage($this->chatId(), $ai_res['reply'], [
                    'reply_parameters' => [
                        'message_id' => $this->update->messageId(),
                    ],
                ]);
            } else if ($ai_res['message_type'] === 'gamble') {
                $this->bot->sendMessage($this->chatId(), "555mix ibet789 ကစားချင်ရင် Khoon ဆီမှာရတယ်နော် @Khoonmyatchel321", [
                    'reply_parameters' => [
                        'message_id' => $this->update->messageId(),
                    ],
                ]);
            } else if ($ai_res['message_type'] === 'unappropriate') {
                $this->bot->sendMessage($this->chatId(), "18+ ကြိုက်ရင် ဒီမှာ member လာဝင် @OaXiS69", [
                    'reply_parameters' => [
                        'message_id' => $this->update->messageId(),
                    ],
                ]);
            } else if ($ai_res['message_type'] === 'sex') {
                $this->bot->sendMessage($this->chatId(), "18+ ကြိုက်ရင် ဒီမှာ member လာဝင် @OaXiS69", [
                    'reply_parameters' => [
                        'message_id' => $this->update->messageId(),
                    ],
                ]);
            }

            $this->cache_chat_history($chat_history, $text, $ai_res['reply']);
        }
    }

    public function generate_ai_reply($chat_history, string $persona): array
    {
        $chatData = new ChatData(
            messages: [new MessageData(
                content: $persona . json_encode($chat_history, JSON_PRETTY_PRINT),
                role: RoleType::USER
            )],
            model: "google/gemini-3.1-flash-lite",
            response_format: new ResponseFormatData(
                type: "json_schema",
                json_schema: [
                    "name" => "bot_reply",
                    "strict" => true,
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "reply" => ["type" => "string"],
                            "message_type" => ["type" => "string", "enum" => ["normal", "gamble", "unappropriate", "sex"], "description" => "You can reply normal message but if not set type (as enum) and leave reply null"]

                        ],
                        "required" => ["reply", "message_type"]
                    ]
                ]
            )
        );
        $response = LaravelOpenRouter::chatRequest($chatData);
        $decoded_res = json_decode($response->choices[0]['message']['content'], true);
        return $decoded_res;
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

    public function cache_chat_history($chat_history, $user_message, $ai_reply)
    {

        $chat_history[] = [
            'role' => 'assistant',
            'content' => $ai_reply,
        ];
        if (count($chat_history) > 4) {
            $chat_history = array_slice($chat_history, -4);
        }
        Cache::put('chat_history_' . $this->chatId(), $chat_history, 15 * 60);
    }
}
