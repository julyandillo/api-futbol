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
     * @param object $entity
     * @param bool $flush
     * @return void
     */
    public function save(object $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param object $entity
     * @param bool $flush
     * @return void
     */
    public function remove(object $entity, bool $flush = false): void
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
    public function findByCursor(ApiCursor $cursor, bool $debugSQL = false): array
    {
        $queryBuilder = $this->createQueryBuilder($this->entityAlias);

        if ($cursor->hasFilters()) {
            foreach ($cursor->getFilters() as $filter) {
                $queryBuilder->andWhere("{$this->entityAlias}.$filter");
            }

            $queryBuilder->setParameters($cursor->getParameters());
        }

        if ($cursor->isFirstFetch()) {
            // cuando en la petición no se encuentre el parámetro con la página siguiente codificada se calcularán
            // las páginas disponibles en función del total de filas

            $cursor->setTotalRows((int)$queryBuilder
                ->select("COUNT({$this->entityAlias}.id)")
                ->getQuery()
                ->getSingleScalarResult()
            );
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

        $query = $queryBuilder
            // en el caso de que se calcule también el número total de filas el select sería incorrecto aquí
            ->select($this->entityAlias)
            ->setMaxResults($cursor->getLimit())
            ->getQuery();

        if ($debugSQL) {
            $cursor->setSql($query->getSQL());
        }

        $results = $query->execute();

        if (!empty($results) && $cursor->useDefaultOrder()) {
            $lastResult = $results[count($results) - 1];
            $cursor->setLastID($lastResult->getId());
        }

        return $results;
    }
}