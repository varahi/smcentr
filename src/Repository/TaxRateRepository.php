<?php

namespace App\Repository;

use App\Entity\City;
use App\Entity\Profession;
use App\Entity\TaxRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaxRate>
 *
 * @method TaxRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaxRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaxRate[]    findAll()
 * @method TaxRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaxRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxRate::class);
    }

    public function add(TaxRate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TaxRate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param City $city
     * @param Profession $profession
     * @return float|int|mixed|string
     */
    public function findByCityAndProfession(City $city, Profession $profession)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('t')
            ->from($this->_entityName, 't')
            ->where($qb->expr()->eq('t.city', $city->getId()))
            ->setMaxResults(1)
            ->andWhere($qb->expr()->eq('t.profession', $profession->getId()))
        ;
        return $qb->getQuery()->getOneOrNullResult();
    }

//    /**
//     * @return TaxRate[] Returns an array of TaxRate objects
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

//    public function findOneBySomeField($value): ?TaxRate
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
