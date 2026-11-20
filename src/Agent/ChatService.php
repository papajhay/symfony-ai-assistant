<?php

namespace App\Agent;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

class ChatService
{
    public function __construct(
        private readonly AgentInterface $agent,
    ) {
    }

    public function ask(string $question): string
    {
        $messages = new MessageBag(
            Message::ofUser($question)
        );

        $response = $this->agent->call($messages);

        return $response->getContent();
    }
}