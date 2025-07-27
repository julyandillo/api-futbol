<?php

namespace App\Repository;

use App\Entity\Arbitro;
use App\Entity\Competicion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Arbitro>
 */
class ArbitroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Arbitro::class);
    }

    public function save(mixed $arbitro, bool $flush = true): void
    {
        $this->getEntityManager()->persist($arbitro);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Arbitro $arbitro, bool $flush = true): void
    {
        $this->getEntityManager()->remove($arbitro);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCompetition(Competicion $competicion): array
    {
        $entityManager = $this->getEntityManager();
        $rsm = new ResultSetMapping();
        $rsm
            ->addEntityResult(Arbitro::class, 'a')
            ->addFieldResult('a', 'id', 'id')
            ->addFieldResult('name', 'name', 'name')
            ->addFieldResult('fullname', 'fullname', 'fullname')
            ->addFieldResult('country', 'country', 'country')
            ->addFieldResult('birthdate', 'birthdate', 'birthdate');

        /*
        select a.*
        from arbitro a
        where exists(select * from partido p where p.arbitro_id = a.id
            and exists (select * from jornada_partido jp where jp.partido_id = p.id
                and exists (select * from jornada j where j.id = jp.jornada_id)))
        */
        $query = $entityManager->createNativeQuery(
            'SELECT DISTINCT a.*
                    FROM arbitro a 
                    INNER JOIN partido p ON p.arbitro_id = a.id
                    INNER JOIN jornada_partido jp ON jp.partido_id = p.id
                    INNER JOIN jornada j ON j.id = jp.jornada_id AND j.competicion_id = ?', $rsm);
        $query->setParameter(1, $competicion->getId());
        return $query->getResult();
    }
}
