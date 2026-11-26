<?php

namespace App\Agent;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use App\Entity\Message as StoredMessage;

final readonly class ChatService
{
    public function __construct(
        private readonly AgentInterface $agent,
    ) {
    }

    /** @param list<StoredMessage> $history */
    public function ask(string $question, array $history = []): string
    {
        $messages = [Message::forSystem('Utilise les documents Symfony et Symfony AI trouvés avec SimilaritySearch pour répondre à la question.')];
        foreach ($history as $storedMessage) {
            $messages[] = $storedMessage->getRole() === 'assistant'
                ? Message::ofAssistant($storedMessage->getContent())
                : Message::ofUser($storedMessage->getContent());
        }
        $messages[] = Message::ofUser($question);

        $response = $this->agent->call(new MessageBag(...$messages));

        return $response->getContent();
    }
}
