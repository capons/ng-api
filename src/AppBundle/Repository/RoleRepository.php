<?php

namespace AppBundle\Repository;

/**
 * RoleRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RoleRepository extends \Doctrine\ORM\EntityRepository
{
    public function loadRoleByRolename($role_name)
    {
        return $this->createQueryBuilder('u')
           // ->select('u.id')            // for user permision group (Entity Group)
            ->where('u.role = :role')
            ->setParameter('role', $role_name)
            ->getQuery()
            ->getOneOrNullResult();
    }

}