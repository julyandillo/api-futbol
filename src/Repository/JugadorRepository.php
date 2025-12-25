<?php

namespace App\Repository;

use App\Entity\Jugador;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ApiRepository<Jugador>
 *
 * @method Jugador|null find($id, $lockMode = null, $lockVersion = null)
 * @method Jugador|null findOneBy(array $criteria, array $orderBy = null)
 * @method Jugador[]    findAll()
 * @method Jugador[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JugadorRepository extends ApiRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Jugador::class, 'j');
    }

}
