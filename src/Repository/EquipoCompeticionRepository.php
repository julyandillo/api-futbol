<?php

namespace App\Repository;

use App\Entity\Competicion;
use App\Entity\Equipo;
use App\Entity\EquipoCompeticion;
use App\Entity\Plantilla;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EquipoCompeticion>
 *
 * @method EquipoCompeticion|null find($id, $lockMode = null, $lockVersion = null)
 * @method EquipoCompeticion|null findOneBy(array $criteria, array $orderBy = null)
 * @method EquipoCompeticion[]    findAll()
 * @method EquipoCompeticion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EquipoCompeticionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipoCompeticion::class);
    }

    public function save(EquipoCompeticion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EquipoCompeticion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function load(Equipo $equipo, Competicion $competicion, Plantilla $plantilla): ?EquipoCompeticion
    {
        return $this->findOneBy([
                'competicion' => $competicion,
                'equipo' => $equipo,
                'plantilla' => $plantilla
            ]);
    }

    public function getCompeticionesDePlantilla(Plantilla $plantilla): array
    {
        return $this->findBy([
            'plantilla' => $plantilla,
        ]);
    }

//    /**
//     * @return Participacion[] Returns an array of Participacion objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Participacion
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
