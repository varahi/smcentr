<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 *
 * @method Ticket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticket[]    findAll()
 * @method Ticket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketRepository extends ServiceEntityRepository
{
    public const TICKET_TABLE = 'App\Entity\Ticket';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function add(Ticket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ticket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param int $id
     * @return int|mixed|string
     */
    public function findByUserAndStatus(int $id, $status)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('t')
            ->from(self::TICKET_TABLE, 't')
            ->join('t.user', 'u')
            ->where($qb->expr()->eq('u.id', $id))
            ->andWhere('t.status LIKE :status')
            ->setParameter('status', $status)
            ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $status
     * @return float|int|mixed|string
     */
    public function findAllByStatus($status)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('t')
            ->from(self::TICKET_TABLE, 't')
            ->where('t.status LIKE :status')
            ->setParameter('status', $status)
        ;

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Ticket[] Returns an array of Ticket objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ticket
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
