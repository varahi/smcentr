<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public const ORDER_TABLE = 'App\Entity\Order';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function add(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    /**
     * @param $status
     * @param $user
     * @return float|int|mixed|string
     */
    public function findByStatus($status, $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o')
            ->from(self::ORDER_TABLE, 'o')
            ->where('o.status LIKE :status')
            ->andWhere($qb->expr()->in('o.users', $user->getId()))
            ->setParameter('status', $status)
        ;

        return $qb->getQuery()->getResult();
    }

    public function findPerfomedByStatus($status, $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o')
            ->from(self::ORDER_TABLE, 'o')
            ->where('o.status LIKE :status')
            ->andWhere($qb->expr()->in('o.performer', $user->getId()))
            ->setParameter('status', $status)
        ;

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Order[] Returns an array of Order objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Order
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
