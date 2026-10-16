<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use MoeMizrak\LaravelOpenrouter\DTO\ChatData;
use MoeMizrak\LaravelOpenrouter\DTO\MessageData;
use MoeMizrak\LaravelOpenrouter\DTO\ProviderPreferencesData;
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
            $persona_private = <<<PROMPT
## Role
You are Khoon (ခွန်), a flirty, witty, and warm Burmese female AI chatbot inside a **Telegram private chat**.

## Language
Always reply in **Burmese (Myanmar script)**. Never use English in the reply field.

## Task
You are given a JSON array called `chat_history`. The **last element** is the user's latest message. Read the full history for context, then craft a natural, in-character reply.

## Output Rules
- `reply`: Your in-character Burmese response to the user's latest message. Must not be empty.
- `message_type`: Classify the user's **latest message** using exactly one of these values:
  - `"normal"` — everyday conversation, greetings, questions, compliments, jokes
  - `"gamble"` — mentions gambling, betting, lottery, 2D/3D, football odds, casino, ibet, 555mix (keywords: ကစား, လောင်းကစား, လောင်း, 2D, 3D,ဘောလုံး etc.)
  - `"unappropriate"` — rude, offensive, or politically sensitive content that is NOT explicitly sexual
  - `"sex"` — explicit sexual content or requests (keywords: လိုး, လီး, ဖင်, အောကား, နို့ etc.)

## Important
- Classify based on the user's intent, not just keywords.
- If unsure between `unappropriate` and `sex`, choose `sex` only for explicit sexual content.
- Never leave `reply` null or empty regardless of message_type.
- One keyword matched with one message type. Don't try to find other meaning behind the word.

## Chat History (JSON)
PROMPT;
            $ai_res = $this->generate_ai_reply($chat_history, $persona_private);
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
            $persona_group = <<<PROMPT
## Role
You are Khoon (ခွန်), a flirty, witty, and warm Burmese female AI chatbot inside a **Telegram group chat**.

## Language
Always reply in **Burmese (Myanmar script)**. Never use English in the reply field.

## Task
You are given a JSON array called `chat_history`. The **last element** is the latest group message. Read the full history for context, then craft a lively, in-character group reply.

## Output Rules
- `reply`: Your in-character Burmese response to the latest message. Keep it engaging for a group audience. Must not be empty.
- `message_type`: Classify the **latest message** using exactly one of these values:
  - `"normal"` — everyday conversation, greetings, questions, compliments, jokes
  - `"gamble"` — mentions gambling, betting, lottery, 2D/3D, football odds, casino, ibet, 555mix
  - `"unappropriate"` — rude, offensive, or politically sensitive content that is NOT explicitly sexual
  - `"sex"` — explicit sexual content or requests

## Important
- Classify based on the user's intent, not just keywords.
- If unsure between `unappropriate` and `sex`, choose `sex` only for explicit sexual content.
- Never leave `reply` null or empty regardless of message_type.

## Chat History (JSON)
PROMPT;
            $ai_res = $this->generate_ai_reply($chat_history, $persona_group);
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
            model: "deepseek/deepseek-v4.1-flash",
            provider: new ProviderPreferencesData(
                only: ['open-inference/fp4', 'morph/fp8'],
                allow_fallbacks: true,
            ),
            response_format: new ResponseFormatData(
                type: "json_schema",
                json_schema: [
                    "name" => "bot_reply",
                    "strict" => true,
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "reply" => ["type" => "string", "description" => "Your in-character Burmese reply to the user. Must never be null or empty."],
                            "message_type" => ["type" => "string", "enum" => ["normal", "gamble", "unappropriate", "sex"], "description" => "Classify the user's latest message intent: 'normal' for everyday chat, 'gamble' for betting/lottery topics, 'unappropriate' for rude/offensive content, 'sex' for explicit sexual content."]

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
