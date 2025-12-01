<?php

namespace App\Repository;

use App\ApiCursor\ApiCursor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template T of object
 * @template-extends ServiceEntityRepository<T>
 */
class ApiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, string $entityClass, private readonly string $entityAlias)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * @param <T> $entity
     * @param bool $flush
     * @return void
     */
    public function save($entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param <T> $entity
     * @param bool $flush
     * @return void
     */
    public function remove($entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param ApiCursor $cursor
     * @return T[]
     */
    public function findByCursor(ApiCursor $cursor): array
    {
        if ($cursor->isFirstFetch()) {
            // cuando en la petición no se encuentre el parámetro con la página siguiente codificada se calcularán
            // las páginas disponibles en función del total de filas
            $cursor->setTotalRows($this->count());
        }

        $queryBuilder = $this->createQueryBuilder($this->entityAlias);

        if ($cursor->hasFilters()) {
            foreach ($cursor->getFilters() as $filter) {
                $queryBuilder->andWhere("{$this->entityAlias}.$filter");
            }

            $queryBuilder->setParameters($cursor->getParameters());
        }

        if ($cursor->useDefaultOrder()) {
            $queryBuilder
                ->andWhere("{$this->entityAlias}.id > :ordering")
                ->setParameter('ordering', $cursor->getLastID())
                ->orderBy("{$this->entityAlias}.id", 'ASC');

        } else {
            foreach ($cursor->getOrderBy() as $field => $order) {
                $queryBuilder
                    ->setFirstResult($cursor->getOffset())
                    ->orderBy("{$this->entityAlias}.$field", $order);
            }
        }


        return $queryBuilder
            ->setMaxResults($cursor->getLimit())
            ->getQuery()
            ->getResult();
    }
}