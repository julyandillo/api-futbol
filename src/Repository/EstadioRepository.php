<?php

namespace App\Repository;

use App\Entity\Estadio;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ApiRepository<Estadio>
 *
 * @method Estadio|null find($id, $lockMode = null, $lockVersion = null)
 * @method Estadio|null findOneBy(array $criteria, array $orderBy = null)
 * @method Estadio[]    findAll()
 * @method Estadio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstadioRepository extends ApiRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Estadio::class, 'e');
    }

    /**
     * @return Estadio[]
     * @throws Exception
     */
    public function getTodosLosEstadiosDelEquipoconId(int $idEquipo): array
    {
        $conexion = $this->getEntityManager()->getConnection();

        $sql = "SELECT estadio_id FROM equipos_estadios WHERE equipo_id = :equipo";
        $resultSet = $conexion->executeQuery($sql, ['equipo' => $idEquipo]);

        $results = $resultSet->fetchAllAssociative();

        if (empty($results)) {
            return [];
        }

        foreach ($results as $result) {
            $estadio = $this->find($result['estadio_id']);
            if (!$estadio) {
                continue;
            }

            $estadios[] = $estadio;
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
        $resultSet = $conexion->executeQuery($sql, ['equipo' => $idEquipo]);
        $idEstadio = $resultSet->fetchOne();

        if (!$idEstadio) {
            return null;
        }

        return $this->find($idEstadio);
    }


}
