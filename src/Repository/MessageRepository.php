<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Message> */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Message::class); }

    /** @return list<Message> */
    public function findChronological(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')->andWhere('m.conversation = :conversation')->setParameter('conversation', $conversation)->orderBy('m.createdAt', 'ASC')->addOrderBy('m.id', 'ASC')->getQuery()->getResult();
    }
}
