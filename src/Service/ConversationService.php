<?php

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ConversationService
{
    public function __construct(private EntityManagerInterface $entityManager, private ConversationRepository $conversations, private MessageRepository $messages) {}

    public function addUserMessage(?int $conversationId, string $content): Conversation
    {
        $conversation = $conversationId === null ? new Conversation() : $this->conversations->find($conversationId);
        if (!$conversation instanceof Conversation) {
            throw new ConversationNotFoundException();
        }
        $isNew = $conversation->getId() === null;
        if ($isNew) {
            $conversation->setTitleFromFirstMessage($content);
            $this->entityManager->persist($conversation);
        }
        $conversation->addMessage(new Message('user', $content));
        $this->entityManager->flush();
        return $conversation;
    }

    public function addAssistantMessage(Conversation $conversation, string $content): void
    {
        $conversation->addMessage(new Message('assistant', $content));
        $this->entityManager->flush();
    }

    /** @return list<Message> */
    public function getMessages(Conversation $conversation): array { return $this->messages->findChronological($conversation); }
    /** @return list<Conversation> */
    public function list(): array { return $this->conversations->findRecent(); }
    public function find(int $id): ?Conversation { return $this->conversations->find($id); }
    public function delete(Conversation $conversation): void { $this->entityManager->remove($conversation); $this->entityManager->flush(); }
}
