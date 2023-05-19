<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public const USER_TABLE = 'App\Entity\User';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function add(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string $role
     *
     * @return array
     */
    public function findByRole($role)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from($this->_entityName, 'u')
            ->where('u.roles LIKE :roles')
            ->andWhere('u.isVerified is not NULL')
            ->orderBy('u.id', 'ASC')
            ->setParameter('roles', '%"'.$role.'"%');

        return $qb->getQuery()->getResult();
    }


    /**
     * @param $role
     * @param $city
     * @param $profession
     * @return float|int|mixed|string
     */
    public function findByCityAndProfession($role, $city, $profession)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from(self::USER_TABLE, 'u')
            ->where('u.roles LIKE :roles')
            ->andWhere($qb->expr()->eq('u.city', $city->getId()))
            ->setParameter('roles', '%"'.$role.'"%')
            ->join('u.professions', 'p')
            ->andWhere($qb->expr()->eq('p.id', $profession->getId()))
            ->orderBy('u.id', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $role
     * @param $city
     * @return float|int|mixed|string
     */
    public function findByCity($role, $city)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from(self::USER_TABLE, 'u')
            ->where('u.roles LIKE :roles')
            ->andWhere($qb->expr()->eq('u.city', $city->getId()))
            ->setParameter('roles', '%"'.$role.'"%')
            ->orderBy('u.id', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $role
     * @param $company
     * @return float|int|mixed|string
     */
    public function findByCompany($role, $company)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from(self::USER_TABLE, 'u')
            ->where('u.roles LIKE :roles')
            ->andWhere($qb->expr()->eq('u.master', $company->getId()))
            ->setParameter('roles', '%"'.$role.'"%')
            ->orderBy('u.id', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $role
     * @param $company
     * @return float|int|mixed|string
     */
    public function findByProfessionAndJobType($role, $profession, $jobType)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from(self::USER_TABLE, 'u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%')
            ->orderBy('u.id', 'ASC');

        if (isset($profession) && !empty($profession)) {
            $qb
                ->join('u.professions', 'p')
                ->andWhere($qb->expr()->eq('p.id', $profession->getId()));
        }

        if (isset($jobType) && !empty($jobType)) {
            $qb
                ->leftJoin('u.jobTypes', 'j')
                ->andWhere($qb->expr()->eq('j.id', $jobType->getId()));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $role
     * @param $company
     * @return float|int|mixed|string
     */
    public function findByCompanyProfessionAndJobType($role, $company, $profession, $jobType)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from(self::USER_TABLE, 'u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%')
            ->andWhere($qb->expr()->eq('u.master', $company->getId()))
            ->orderBy('u.id', 'ASC');

        if (isset($profession) && !empty($profession)) {
            $qb
                    ->join('u.professions', 'p')
                    ->andWhere($qb->expr()->eq('p.id', $profession->getId()));
        }

        if (isset($jobType) && !empty($jobType)) {
            $qb
                    ->leftJoin('u.jobTypes', 'j')
                    ->andWhere($qb->expr()->eq('j.id', $jobType->getId()));
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
