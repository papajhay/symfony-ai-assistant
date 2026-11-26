<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'chat_message')]
#[ORM\Index(
    name: 'idx_chat_message_conversation_created',
    columns: ['conversation_id', 'created_at']
)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[ORM\Column(length: 20)]
    private string $role;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $role, string $content)
    {
        if (!in_array($role, ['user', 'assistant'], true)) {
            throw new \InvalidArgumentException('Message role must be user or assistant.');
        }
        $this->role = $role;
        $this->content = $content;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string { return $this->id; }
    public function getConversation(): Conversation { return $this->conversation; }
    public function setConversation(Conversation $conversation): void { $this->conversation = $conversation; }
    public function getRole(): string { return $this->role; }
    public function getContent(): string { return $this->content; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

 