<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Criteria;

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
     * @return float|int|mixed|string
     */
    public function findAllByStatus($status)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o')
            ->from(self::ORDER_TABLE, 'o')
            ->where('o.status LIKE :status')
            ->setParameter('status', $status)
            ->orderBy('o.created', 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $status
     * @return float|int|mixed|string
     */
    public function findAllByStatusProfessionAndJobTypes($status, array $professions, array $jobTypes)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o')
            ->from(self::ORDER_TABLE, 'o')
            ->where('o.status LIKE :status')
            ->setParameter('status', $status)
            ->orderBy('o.created', 'DESC')
        ;

        if (isset($professions) && !empty($professions)) {
            $qb->andWhere($qb->expr()->in('o.profession', $professions));
        } else {
            $qb->andWhere($qb->expr()->in('o.profession', [0]));
        }
        if (isset($jobTypes) && !empty($jobTypes)) {
            $qb->andWhere($qb->expr()->in('o.jobType', $jobTypes));
        } else {
            $qb->andWhere($qb->expr()->in('o.jobType', [0]));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $status
     * @return float|int|mixed|string
     */
    public function findAllByStatusProfessionJobTypesAndCity($status, array $professions, array $jobTypes, string $city, User $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o')
            ->from(self::ORDER_TABLE, 'o')
            ->where('o.status LIKE :status')
            ->setParameter('status', $status)
            ->orderBy('o.created', 'DESC')
        ;

        if (isset($professions) && !empty($professions)) {
            $qb->andWhere($qb->expr()->in('o.profession', $professions));
        } else {
            $qb->andWhere($qb->expr()->in('o.profession', [0]));
        }
        if (isset($jobTypes) && !empty($jobTypes)) {
            $qb->andWhere($qb->expr()->in('o.jobType', $jobTypes));
        } else {
            $qb->andWhere($qb->expr()->in('o.jobType', [0]));
        }
        if (isset($city) && !empty($city)) {
            $qb->andWhere($qb->expr()->eq('o.city', $city));
        }
        if ($user->getLevel()) {
            $qb->andWhere($qb->expr()->lte('o.level', $user->getLevel()));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $status
     * @param $user
     * @return float|int|mixed|string
     */
    public function findByStatus($status, $user, $id = '')
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o')
            ->from(self::ORDER_TABLE, 'o')
            ->where('o.status LIKE :status')
            ->andWhere($qb->expr()->in('o.users', $user->getId()))
            ->setParameter('status', $status)
            ->orderBy('o.created', 'DESC')
        ;

        if ($id !=='') {
            $qb->andWhere($qb->expr()->eq('o.id', $id));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $status
     * @param $user
     * @return float|int|mixed|string
     */
    public function findByStatusPhoneAndCompany($status, $user)
    {
        $role = 'ROLE_COMPANY';
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o')
            ->from(self::ORDER_TABLE, 'o')
            ->where('o.status LIKE :status')
            ->andWhere('o.phone LIKE :phone')
            ->join('o.users', 'u')
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('phone', $user->getPhone())
            ->setParameter('status', $status)
            ->setParameter('roles', '%"'.$role.'"%')
            ->orderBy('o.created', 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findPerfomedByStatus($status, $user, $orderField, $orderDirection, $limit = '')
    {
        $field = 'o.'.$orderField;

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o')
            ->from(self::ORDER_TABLE, 'o')
            ->where('o.status LIKE :status')
            ->andWhere($qb->expr()->in('o.performer', $user->getId()))
            ->setParameter('status', $status)
            ->orderBy($field, $orderDirection)
        ;

        if ($limit) {
            $qb->setMaxResults($limit);
        }

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
