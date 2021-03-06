<?php

namespace TVF\StoreBundle\Repository;

/**
 * VinylUserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VinylUserRepository extends \Doctrine\ORM\EntityRepository
{
  public function getPopular($limit) {
    return $this->createQueryBuilder('e')
        ->select('sum(e.nb_views) as total_views')
        ->select('e.vinyl_id')
        ->groupBy('e.vinyl_id')
        ->orderBy('total_views', 'DESC')
        ->setMaxResults($limit);
  }
}
