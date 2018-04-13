<?php

namespace TVF\RecordBundle\Repository;

/**
 * VinylRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VinylRepository extends \Doctrine\ORM\EntityRepository
{
  public function search($query, $client_id = null)
  {
    $qb = $this->createQueryBuilder('e')
          ->innerJoin('e.artists', 'a')
          ->innerJoin('e.category', 'c')
          ->where("c.slug = 'vinyle'")
          ->andWhere('a.name LIKE :query_artist OR e.name LIKE :query')
          ->setParameter('query_artist', '%'.$query.'%')
          ->setParameter('query', '%'.$query.'%')
    ;
    if($client_id){
      $qb = $qb->innerJoin('e.client', 'r')
               ->andWhere("r.id = 6")
      ;
    }
    return $qb->orderBy('e.id', 'DESC')
           ->getQuery()
           ->getResult()
    ;
  }
  public function getVinyls($limit=(1024*1024*1024)){
    return $this->createQueryBuilder('e')
          ->innerJoin('e.category', 'c')
          ->where("c.slug = 'vinyle'")
          ->orderBy('e.id', 'DESC')
          ->setMaxResults($limit)
          ->getQuery()
          ->getResult()
    ;
  }
  public function getVinylsFromClient($client_id){
    return $this->createQueryBuilder('e')
          ->innerJoin('e.category', 'c')
          ->innerJoin('e.client', 'r')
          ->where("c.slug = 'vinyle'")
          ->andWhere("r.id = :client_id")
          ->setParameter('client_id', $client_id)
          ->orderBy('e.id', 'DESC')
          ->getQuery()
          ->getResult()
    ;
  }
}
