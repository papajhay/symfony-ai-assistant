<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'chat_conversation')]
#[ORM\Index(
    name: 'idx_chat_conversation_updated_at',
    columns: ['updated_at']
)]
class Conversation
{
    /** @var Collection<int, Message> */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: Message::class,  cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $messages;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(?string $title = null)
    {
        $this->messages = new ArrayCollection();
        $this->createdAt = $this->updatedAt = new \DateTimeImmutable();
        $this->title = $title ?? 'Nouvelle conversation';
    }

    public function getId(): ?string { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    /** @return Collection<int, Message> */
    public function getMessages(): Collection { return $this->messages; }

    public function setTitleFromFirstMessage(string $content): void
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($content)) ?? trim($content);
        preg_match_all('/./us', $normalized, $characters);
        $this->title = implode('', array_slice($characters[0], 0, 252)).(count($characters[0]) > 252 ? '…' : '');
    }

    public function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function addMessage(Message $message): void
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }
        $this->touch();
    }
}