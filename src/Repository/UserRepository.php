<?php

namespace App\Repository;

use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public static function notAdmin(): Criteria
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->notIn('role', ['ROLE_ADMIN']));
        return $criteria;
    }

    public static function isUserType(): Criteria
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->isNull('role'));
        return $criteria;
    }

    public static function isRep(): Criteria
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('role', 'ROLE_REP'));
        return $criteria;
    }

    public static function notOwner(int $id): Criteria
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->notIn('id', [$id]));
        return $criteria;
    }



    public function agents(): ORMQueryBuilder
    {
        return $this->createQueryBuilder('user')
            ->leftJoin('user.manager', 'm')
            ->addSelect('m')
            ->addCriteria(self::isRep());
    }

    public function queryOnlyAgents(User $user): ORMQueryBuilder
    {
        return $this->agents()
            ->addCriteria(self::notOwner($user->getId()));
    }

    /**
     * @param ORMQueryBuilder $qb
     * @param int|null $limit
     * @param int|null $start
     * @return ORMQueryBuilder
     */
    public function queryPaginated(ORMQueryBuilder $qb, ?int $limit, ?int $start): ORMQueryBuilder
    {
        $start = $start ?? 1;
        $startFrom = ($start - 1) * (int) $limit;
        return $qb->setFirstResult($startFrom)
            ->setMaxResults($limit);
    }


    public function getResulted(ORMQueryBuilder $qb)
    {
        return $qb->getQuery()
            ->getResult();
    }


    public function querySelectCount(ORMQueryBuilder $qb, string $toCount = 'user.id')
    {
        return $qb->select('COUNT(:toCount)')
            ->setParameter('toCount', $toCount)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function onlyAgents()
    {
        $qb = $this->agents();
        return $this->getResulted($qb);
    }

    public function onlyUsers()
    {
        return $this->createQueryBuilder('user')
            ->addCriteria(self::isUserType())
            ->getQuery()
            ->getResult();
    }

    public function add(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function setAgent(User $entity, ?User $rep = null)
    {
        $entity->setManager($rep);
        $this->getEntityManager()->persist($entity);
    }

    public function getId(User $entity)
    {
        return $entity->getId();
    }

    public function getChildren(User $entity)
    {
        return $entity->getChildren();
    }

    public function getRole(User $entity)
    {
        return $entity->getRole();
    }

    public function getRoles(User $entity)
    {
        return $entity->getRoles();
    }

    public function setLog(User $entity, bool $flush = false): void
    {
        $entity->setLoginTime(new DateTime('now'));
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
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->add($user, true);
    }


    public function getRepDescendants(User $rep): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'WITH RECURSIVE rep_cte AS (
            SELECT * FROM `user` WHERE manager_id = :rep_id AND role = :role_rep
            UNION
            SELECT u.* FROM `user` u
            INNER JOIN rep_cte rc ON u.manager_id = rc.id
            WHERE u.role = :role_rep
        )
        SELECT * FROM rep_cte';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('rep_id', $rep->getId());
        $stmt->bindValue('role_rep', User::ROLE_REP);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getRepDescendantsIdsForExclusion(int $id): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'WITH RECURSIVE rep_cte AS (
            SELECT id, manager_id FROM `user` WHERE manager_id = :rep_id AND role = :role_rep
            UNION
            SELECT u.id, u.manager_id FROM `user` u
            INNER JOIN rep_cte rc ON u.manager_id = rc.id
            WHERE u.role = :role_rep
        )
        SELECT id FROM rep_cte';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('rep_id', $id);
        $stmt->bindValue('role_rep', User::ROLE_REP);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getRepUsers(User $rep): array
    {
        $descendants = $this->getRepDescendants($rep);
        $repIds = array_column($descendants, 'id');
        $repIds[] = $rep->getId(); // include current rep

        return $this->getRepUsersQuery($rep)
            ->getQuery()
            ->getResult();
    }

    public function getRepUsersWithManagerQuery(User $rep): ORMQueryBuilder
    {
        $descendants = $this->getRepDescendants($rep);
        $repIds = array_column($descendants, 'id');
        $repIds[] = $rep->getId(); // include current rep

        return $this->getRepUsersQuery($rep)
            ->andWhere('u.manager IN (:repIds)')
            ->leftJoin('u.manager', 'm')
            ->addSelect(['m.id', 'm.username', 'm.role']);
    }

    public function getAdminUsersForDToQuery(): ORMQueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role IS NULL');
    }

    public function getAdminUsersForDToWithManagerQuery(): ORMQueryBuilder
    {
        return $this->getAdminUsersForDToQuery()
            ->leftJoin('u.manager', 'm')
            ->addSelect(['m.id', 'm.username', 'm.role']);
    }

    public function getRepUsersQuery(User $rep): ORMQueryBuilder
    {
        $descendants = $this->getRepDescendants($rep);
        $repIds = array_column($descendants, 'id');
        $repIds[] = $rep->getId(); // include current rep

        return $this->createQueryBuilder('u')
            ->andWhere('u.manager IN (:repIds)')
            ->setParameter('repIds', $repIds)
            ->andWhere('u.role IS NULL');
    }

    public function queryAvailableManagersForRep(User $rep): ORMQueryBuilder
    {
        $descendants = $this->getRepDescendants($rep);
        $repIds = array_column($descendants, 'id');
        $repIds[] = $rep->getId(); // include self if needed

        return $this->createQueryBuilder('u')
            ->where('u.id IN (:repIds)')
            ->setParameter('repIds', $repIds)
            ->andWhere('u.role = :repRole')
            ->setParameter('repRole', User::ROLE_REP);
    }

    /**
     * for hydration in form
     */
    public function getRepDescendantEntities(User $rep): array
    {
        $rawUsers = $this->getRepDescendants($rep);
        $ids = array_column($rawUsers, 'id');

        return $this->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * for dto
     */
    public function getRepDescendantEntitiesForDTO(User $rep): ORMQueryBuilder
    {
        $rawUsers = $this->getRepDescendants($rep);
        $ids = array_column($rawUsers, 'id');

        return $this->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->leftJoin('u.manager', 'm')
            ->addSelect(['m.id', 'm.username', 'm.role']);
    }

    // new ver
    private function getRepTreeCteSql(): string
    {
        return 'WITH RECURSIVE rep_tree AS (
            SELECT id FROM user WHERE id = :repId AND role = :role_rep
            UNION ALL
            SELECT u.id FROM user u
            INNER JOIN rep_tree rt ON u.manager_id = rt.id
            WHERE u.role = :role_rep
        )';
    }

    public function getUsersUnderRepFlat(User $rep, int $offset = 0, int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $repId = $rep->getId();

        $sql = $this->getRepTreeCteSql() . "\n" .'SELECT 
            u.id,
            u.username,
            u.manager_id,
            m.username AS display_name,
            u.login_time,
            u.role
        FROM user u
        LEFT JOIN user m ON m.id = u.manager_id
        WHERE u.manager_id IN (SELECT id FROM rep_tree)
          AND u.role IS NULL
        ORDER BY u.id
        LIMIT :limit OFFSET :offset';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('repId', $repId);
        $stmt->bindValue('role_rep', User::ROLE_REP);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getAgentsUnderRepFlat(User $rep, int $offset = 0, int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $repId = $rep->getId();
        $sql = $this->getRepTreeCteSql()  . "\n" .
        'SELECT 
            u.id,
            u.username,
            u.manager_id,
            m.username AS display_name,
            u.login_time,
            u.role
        FROM user u
        JOIN user m ON m.id = u.manager_id
        WHERE u.id IN (
            SELECT id FROM rep_tree WHERE id != :repId
        )
        ORDER BY u.id
        LIMIT :limit OFFSET :offset';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('repId', $repId);
        $stmt->bindValue('role_rep', User::ROLE_REP);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function countRepAgents(User $rep): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = $this->getRepTreeCteSql()  . "\n" . 'SELECT COUNT(*) FROM rep_tree WHERE id != :repId';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('repId', $rep->getId());
        $stmt->bindValue('role_rep', User::ROLE_REP);

        return (int) $stmt->executeQuery()->fetchOne();
    }


    public function countUsersUnderRep(User $rep): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $repId = $rep->getId();

        $sql = $this->getRepTreeCteSql() . "\n" . 'SELECT COUNT(*) FROM user
        WHERE manager_id IN (SELECT id FROM rep_tree)
          AND role IS NULL';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('repId', $repId);
        $stmt->bindValue('role_rep', User::ROLE_REP);

        return (int) $stmt->executeQuery()->fetchOne();
    }



    public function getRepAssignableManagers(User $rep, User $target, bool $excludeSelf = true): array
    {
        $repRole = $rep->getRole();
        $descendants = $repRole === 'ROLE_ADMIN' ? $this->onlyAgents() : $this->getRepDescendantEntities($rep);

        // Get IDs of all descendants including target
        $invalidManagers = $excludeSelf ? [$target->getId()] : [];
        if ($target->getRole() === 'ROLE_REP') {
            foreach ($target->getHierarchialManegers() as $mgr) {
                $invalidManagers[] = $mgr->getId();
            }
        }


        // Filter only those allowed to be selected as manager
        return array_filter($descendants, function (User $candidate) use ($invalidManagers) {
            return !in_array($candidate->getId(), $invalidManagers);
        });
    }

    public function getRepAssignableManagersIds(array $assignibles): array
    {
        $managers = [];
        foreach ($assignibles as $mgr) {
            $managers[] = $mgr->getId();
        }
        return $managers;
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
