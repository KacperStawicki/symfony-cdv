<?php

namespace App\Repository;

use App\Entity\Articles;
use App\Entity\Reaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reaction>
 */
class ReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reaction::class);
    }

    public function findByArticle(Articles $article): array
    {
        return $this->findBy(['article' => $article]);
    }

    public function findByArticleAndUser(Articles $article, User $user): ?Reaction
    {
        return $this->findOneBy(['article' => $article, 'user' => $user]);
    }

    public function getReactionCounts(Articles $article): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.type, COUNT(r.id) as count')
            ->where('r.article = :article')
            ->setParameter('article', $article)
            ->groupBy('r.type');

        $results = $qb->getQuery()->getResult();

        $counts = [
            Reaction::LIKE => 0,
            Reaction::DISLIKE => 0
        ];

        foreach ($results as $result) {
            $counts[$result['type']] = (int) $result['count'];
        }

        return $counts;
    }
}
