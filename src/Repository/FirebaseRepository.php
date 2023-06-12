<?php

namespace App\Repository;

use App\Entity\Firebase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Firebase>
 *
 * @method Firebase|null find($id, $lockMode = null, $lockVersion = null)
 * @method Firebase|null findOneBy(array $criteria, array $orderBy = null)
 * @method Firebase[]    findAll()
 * @method Firebase[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FirebaseRepository extends ServiceEntityRepository
{
    public const TABLE = 'App\Entity\Firebase';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Firebase::class);
    }

    public function add(Firebase $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Firebase $entity, bool $flush = false): void
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
    public function findAllByOneUser($user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('f')
            ->from(self::TABLE, 'f')
            ->join('f.user', 'u')
            ->where($qb->expr()->eq('u.id', $user->getId()))
        ;

        return $qb->getQuery()->getResult();
    }


    public function findAllByUsers($users)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('f')
            ->from(self::TABLE, 'f')
            ->join('f.user', 'u')
            ->where($qb->expr()->in('u.id', $users))
        ;

        return $qb->getQuery()->getResult();
    }


    /**
     * @return float|int|mixed|string
     */
    public function findNonHidden()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $expr = $qb->expr();

        $qb->select('f')
            ->from(self::TABLE, 'f')
            ->where($expr->neq('f.hidden', 1))
            ->orderBy('f.created', 'ASC');

        $reviews = $qb->getQuery()->getResult();

        return $reviews;
    }

//    /**
//     * @return Firebase[] Returns an array of Firebase objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Firebase
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
