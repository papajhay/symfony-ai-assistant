<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Conversation> */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Conversation::class); }

    /** @return list<Conversation> */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('c')->orderBy('c.updatedAt', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }
}
