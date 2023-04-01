<?php

namespace App\Repository;

use App\Entity\PlantillaJugador;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlantillaJugador>
 *
 * @method PlantillaJugador|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlantillaJugador|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlantillaJugador[]    findAll()
 * @method PlantillaJugador[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlantillaJugadorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlantillaJugador::class);
    }

    public function save(PlantillaJugador $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PlantillaJugador $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return PlantillaJugador[] Returns an array of PlantillaJugador objects
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

//    public function findOneBySomeField($value): ?PlantillaJugador
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
