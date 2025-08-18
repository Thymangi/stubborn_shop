<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

//    /**
//     * @return Product[] Returns an array of Product objects
//     */
public function findByPriceRange($min, $max)
{    return $this->createQueryBuilder('p')
        ->andWhere('p.price >= :min')
        ->andWhere('p.price <= :max')
        ->setParameter('min', $min)
        ->setParameter('max', $max)
        ->orderBy('p.price', 'ASC')
        ->getQuery()
        ->getResult()
    ;
}

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
