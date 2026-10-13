<?php

namespace Tests\Feature;

use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class ChatRouteTest extends TestCase
{
    public function test_chat_extract_route_returns_structured_data_successfully(): void
    {
        // 1. Prepare the JSON response that OpenRouter returns
        $fakeResponseBody = [
            'id'      => 'gen-12345',
            'model'   => 'openai/gpt-4o-mini',
            'object'  => 'chat.completion',
            'created' => time(),
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => json_encode([
                            'name' => 'Jane Doe',
                            'skills' => ['PHP', 'Laravel'],
                        ]),
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ],
        ];

        // 2. Queue the mocked HTTP response
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($fakeResponseBody)),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        // 3. Swap the Guzzle client that OpenRouterRequest resolves internally
        $this->app->bind(ClientInterface::class, fn() => $mockClient);

        // 4. Send request to the route
        $response = $this->postJson('/api/chat/extract', [
            'prompt' => 'Jane Doe is proficient in PHP and Laravel',
        ]);

        // 5. Assert status and response payload
        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'Jane Doe',
                    'skills' => ['PHP', 'Laravel'],
                ],
            ]);
    }

    public function test_chat_extract_route_fails_validation_without_prompt(): void
    {
        $response = $this->postJson('/api/chat/extract', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['prompt']);
    }
}
