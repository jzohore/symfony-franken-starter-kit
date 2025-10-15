<?php

declare(strict_types=1);
namespace App\Infrastructure\User\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use App\Domain\User\Entity\User;
use App\Domain\User\Port\Out\UserRepositoryInterface as UserRepositoryInterface;

final class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function getById(Uuid $id): ?User
    {
        return $this->getEntityManager()->find(User::class, $id);
    }

    public function save(User $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function delete(User $entity): void
    {
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
    }

    public function search(?string $querySearch = null, ?array $status = [], ?bool $isDeleted = null): QueryBuilder
    {
        $em = $this->getEntityManager();

        $alias = strtolower((new \ReflectionClass(User::class))->getShortName());

        $qb = $em->createQueryBuilder()
            ->from(User::class, $alias)
            ->select($alias)
            ->orderBy($alias . '.createdAt', 'DESC');

        if ($isDeleted !== null) {
            $qb->andWhere(
                $isDeleted
                    ? $qb->expr()->isNotNull($alias . '.deletedAt')
                    : $qb->expr()->isNull($alias . '.deletedAt')
            );
        }

        if (!empty($status)) {
            $qb->andWhere($alias . '.status IN (:status)')
               ->setParameter('status', array_map(static fn($s) => (string) $s, $status));
        }

        if (!empty($querySearch)) {
            $search = '%' . trim(mb_strtolower($querySearch)) . '%';

            $meta = $em->getClassMetadata(User::class);
            $stringFields = [];
            foreach ($meta->getFieldNames() as $field) {
                $type = $meta->getTypeOfField($field);
                if (in_array($type, ['string', 'text'], true)) {
                    $stringFields[] = $field;
                }
            }

            if ($stringFields) {
                $orX = $qb->expr()->orX();
                foreach ($stringFields as $field) {
                    $orX->add($qb->expr()->like('LOWER(' . $alias . '.' . $field . ')', ':query'));
                }
                $qb->andWhere($orX)->setParameter('query', $search);
            }
        }

        return $qb;
    }
}
