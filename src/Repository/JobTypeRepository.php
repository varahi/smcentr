<?php

namespace App\Repository;

use App\Entity\JobType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobType>
 *
 * @method JobType|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobType|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobType[]    findAll()
 * @method JobType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobTypeRepository extends ServiceEntityRepository
{
    /**
     *
     */
    public const TABLE = 'App\Entity\JobType';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobType::class);
    }

    public function add(JobType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(JobType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param array $order
     * @return JobType[]
     */
    public function findAllOrder(array $order)
    {
        return $this->findBy([], $order);
    }

    /**
     * @param $profession
     * @return float|int|mixed|string
     */
    public function findByProfession($profession)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('j')
            ->from(self::TABLE, 'j')
            ->where($qb->expr()->eq('j.profession', $profession->getId()))
            ->orderBy('j.id', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return JobType[] Returns an array of JobType objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('j.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?JobType
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
