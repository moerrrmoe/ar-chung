<?php
// app/Http/Controllers/ChatController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MoeMizrak\LaravelOpenrouter\Facades\LaravelOpenRouter;
use MoeMizrak\LaravelOpenrouter\DTO\ChatData;
use MoeMizrak\LaravelOpenrouter\DTO\MessageData;
use MoeMizrak\LaravelOpenrouter\DTO\ResponseFormatData;
use MoeMizrak\LaravelOpenrouter\Types\RoleType;

class ChatController extends Controller
{
    public function extract(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string',
        ]);

        $chatData = new ChatData(
            messages: [new MessageData(content: $validated['prompt'], role: RoleType::USER)],
            model: 'openai/gpt-4o-mini',
            response_format: new ResponseFormatData(
                type: 'json_schema',
                json_schema: [
                    'name' => 'user_data',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'skills' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                        'required' => ['name', 'skills'],
                        'additionalProperties' => false,
                    ],
                ]
            ),
        );

        $response = LaravelOpenRouter::chatRequest($chatData);
        $extracted = json_decode($response->choices[0]['message']['content'], true);

        return response()->json([
            'status' => 'success',
            'data' => $extracted,
        ]);
    }
}
