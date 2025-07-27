<?php

namespace App\Repository;

use App\ApiCursor\ApiCursor;
use App\Entity\Estadio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Estadio>
 *
 * @method Estadio|null find($id, $lockMode = null, $lockVersion = null)
 * @method Estadio|null findOneBy(array $criteria, array $orderBy = null)
 * @method Estadio[]    findAll()
 * @method Estadio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstadioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Estadio::class);
    }

    public function save(Estadio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Estadio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Estadio[]
     * @throws Exception
     */
    public function getTodosLosEstadiosDelEquipoconId(int $idEquipo): array
    {
        $conexion = $this->getEntityManager()->getConnection();

        $sql = "SELECT estadio_id FROM equipos_estadios WHERE equipo_id = :equipo";
        $statement = $conexion->prepare($sql);
        $resultSet = $statement->executeQuery(['equipo' => $idEquipo]);

        $estadios = [];
        $results = $resultSet->fetchAllAssociative();

        if (!empty($results)) {
            foreach ($results as $result) {
                $estadio = $this->find($result['estadio_id']);
                if (!$estadio) continue;

                $estadios[] = $estadio;
            }
        }

        return $estadios;
    }

    /**
     * @throws Exception
     */
    public function guardaRelacionConEquipo(Estadio $estadio, int $idEquipo): void
    {
        $conexion = $this->getEntityManager()->getConnection();

        $sql = "INSERT INTO equipos_estadios (equipo_id, estadio_id) VALUES (:equipo, :estadio)";
        $conexion->executeStatement($sql, [
            'equipo' => $idEquipo,
            'estadio' => $estadio->getId(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function setEstadioEnUsoParaEquipoConId(Estadio $estadio, int $idEquipo): void
    {
        $conexion = $this->getEntityManager()->getConnection();
        $sql = "UPDATE equipos_estadios SET en_uso = false WHERE equipo_id = :equipo";
        $conexion->executeStatement($sql, ['equipo' => $idEquipo]);

        $sql = "UPDATE equipos_estadios SET en_uso = true WHERE estadio_id = :estadio AND equipo_id = :equipo";
        $conexion->executeStatement($sql, [
            'equipo' => $idEquipo,
            'estadio' => $estadio->getId(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function getEstadioActualDelEquipoConId(int $idEquipo): ?Estadio
    {
        $conexion = $this->getEntityManager()->getConnection();
        $sql = "SELECT estadio_id FROM equipos_estadios WHERE equipo_id = :equipo AND en_uso = true";
        $statement = $conexion->prepare($sql);
        $resultSet = $statement->executeQuery(['equipo' => $idEquipo]);
        $idEstadio = $resultSet->fetchOne();

        if (!$idEstadio) {
            return null;
        }

        return $this->find($idEstadio);
    }

    public function findByCursor(ApiCursor $cursor, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('e');

        if (empty($cursor->getOrderBy())) {
            $queryBuilder
                ->where("e.id > :ordering")
                ->setParameter('ordering', $cursor->getLastValue())
                ->orderBy('e.id', 'ASC');

        } else {
            foreach ($cursor->getOrderBy() as $field => $order) {
                $queryBuilder
                    ->orderBy("e.$field", $order);

                if ($cursor->getLastValue()) {
                    $queryBuilder
                        ->andWhere("e.$field " . ($order === 'ASC' ? '>' : '<') . " :ordering")
                        ->setParameter('ordering', $cursor->getLastValue());
                }

            }
        }

        $query = $queryBuilder
            ->getQuery()
            ->setMaxResults($limit);

        return $query->getResult();
    }
}
