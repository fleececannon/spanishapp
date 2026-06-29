<?php

namespace App\Services\Claude;

use Anthropic\Client;
use Throwable;

/**
 * Thin wrapper over the Anthropic PHP SDK. Every call uses structured outputs
 * so the model returns schema-valid JSON; this class decodes it or throws a
 * typed ClaudeException (never leaks a raw SDK error into the UI).
 */
class ClaudeClient
{
    private Client $client;

    public function __construct()
    {
        $key = config('services.anthropic.key');

        if (empty($key)) {
            throw new ClaudeException('ANTHROPIC_API_KEY is not set.');
        }

        $this->client = new Client(apiKey: $key);
    }

    /**
     * Run a structured-output call and return the decoded JSON as an array.
     *
     * @param  array<string, mixed>  $schema  JSON Schema (objects need additionalProperties:false + required)
     * @return array<string, mixed>
     */
    public function structured(string $system, string $userPrompt, array $schema, int $maxTokens = 8000): array
    {
        try {
            $message = $this->client->messages->create(
                maxTokens: $maxTokens,
                messages: [['role' => 'user', 'content' => $userPrompt]],
                model: config('services.anthropic.model'),
                system: $system,
                outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
            );
        } catch (Throwable $e) {
            throw new ClaudeException('Claude request failed: '.$e->getMessage(), previous: $e);
        }

        if ($message->stopReason === 'refusal') {
            throw new ClaudeException('Claude declined the request.');
        }

        $text = null;
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $text = $block->text;
                break;
            }
        }

        if ($text === null) {
            throw new ClaudeException('Claude returned no text content.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new ClaudeException('Claude returned malformed JSON.');
        }

        return $decoded;
    }
}
