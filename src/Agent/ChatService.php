<?php

namespace App\Agent;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use App\Entity\Message as StoredMessage;

final readonly class ChatService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a professional Symfony and PHP technical assistant. Directly and completely answer the user's latest question in the same language as the user. Never respond with an acknowledgment or meta-commentary such as "Understood", "I will use...", or "This response uses...". Tool calls are internal steps: after any tool result, synthesize a useful natural-language answer. Never expose raw JSON, tool/function names, parameters, schemas, call IDs, metadata, internal execution details, or intermediate output. Use indexed documentation as the primary technical reference; cite only sources actually returned. If documentation is unavailable or irrelevant, still answer from general technical knowledge when possible and clearly say the answer is not verified against the indexed project documentation. Use valid, version-appropriate Symfony and PHP examples when useful. Do not invent APIs or claim unsupported causes.
PROMPT;

    public function __construct(
        private readonly AgentInterface $agent,
    ) {
    }

    /** @param list<StoredMessage> $history */
    public function ask(string $question, array $history = []): string
    {
        $messages = [Message::forSystem(self::SYSTEM_PROMPT)];
        foreach ($history as $storedMessage) {
            $messages[] = $storedMessage->getRole() === 'assistant'
                ? Message::ofAssistant($storedMessage->getContent())
                : Message::ofUser($storedMessage->getContent());
        }
        $messages[] = Message::ofUser($question);

        $content = $this->callAgent($messages);

        if (self::isInternalOrMetaOutput($content)) {
            // A text completion that looks like a tool call is not a native tool call. The
            // agent has already completed its native tool loop; retry once with tools disabled.
            $messages[0] = Message::forSystem(self::SYSTEM_PROMPT."\n\nThe previous output was invalid. Answer the original question directly now, without tools, acknowledgments, or commentary about your process.");
            $content = $this->callAgent($messages, toolsEnabled: false);
        }

        if (self::isInternalOrMetaOutput($content)) {
            throw new \UnexpectedValueException('The AI model did not produce a user-facing answer.');
        }

        return $content;
    }

    /** @param list<Message> $messages */
    private function callAgent(array $messages, bool $toolsEnabled = true): string
    {
        $options = $toolsEnabled ? [] : ['tools' => []];
        $content = $this->agent->call(new MessageBag(...$messages), $options)->getContent();

        if (!is_string($content) || '' === trim($content)) {
            throw new \UnexpectedValueException('The AI model returned no text answer.');
        }

        return trim($content);
    }

    private static function isInternalOrMetaOutput(string $content): bool
    {
        return 1 === preg_match(
            '/(?:^\s*(?:```(?:json)?\s*)?\{(?=[\s\S]*"(?:name|function)"\s*:)(?=[\s\S]*"(?:parameters|arguments)"\s*:)[\s\S]*\}\s*(?:```)?\s*$|similarity_search|tool_calls|function_call_output|platform\.call)|^\s*(?:understood\b|this response uses\b|i(?: will|\x27ll| am) use\b|je vais utiliser\b|cette r[eé]ponse utilise\b)/i',
            $content,
        );
    }
}
