<?php
/**
 * User repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Utils\Paginator;

/**
 * Class UserRepository.
 *
 * @package Repository
 */
class UserRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * Number of items on one page when items paginated
     */
    const NUM_ITEMS = 5;
    /**
     * Set repository.
     *
     * @var null|\Repository\SetRepository $setRepository
     */
    protected $setRepository = null;

    /**
     * UserRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->setRepository = new SetRepository($db);
    }

    /**
     * Check if user is an owner of a set
     *
     * @param $id
     * @param $userId
     * @return bool
     */
    public function checkOwnership($id, $userId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('id, users_id')
            ->from('sets', 's')
            ->where('s.id = :id')
            ->andWhere('s.users_id = :users_id')
            ->setParameter(':id', $id, \PDO::PARAM_INT)
            ->setParameter(':users_id', $userId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return !empty($result) ? true : false ;
    }

    /**
     * Fetch all records.
     *
     * @return array Result
     */
    public function findAll()
    {
        $queryBuilder = $this->queryAll();

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT u.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Finds user data by users id.
     *
     * @param $userId
     * @return mixed
     */
    public function findUserDataByUserId($userId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from('users_data')
            ->where('users_id = :users_id')
            ->setParameter(':users_id', $userId,\PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return $result;
    }

    /**
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll()
            ->where('id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Update record user
     *
     * @param $user
     * @return int
     */
    public function updateUser($user) {
            $userId = $user['id'];
            unset($user['id']);
            return $this->db->update('users', $user, ['id' => $userId]);
    }

    /**
     * Saving user into DB with user data
     *
     * @param $user
     * @throws DBALException
     */
    public function save($user)
    {
        $this->db->beginTransaction();

        try {
                $this->db->insert(
                    'users',
                    [
                        'login' => $user['login'],
                        'password' => $user['password'],
                        'roles_id' => $user['roles_id'],
                    ]
                );
                $userId = $this->db->lastInsertId();
                $this->db->insert(
                    'users_data',
                    [
                        'name' => $user['name'],
                        'surname' => $user['surname'],
                        'email' => $user['email'],
                        'users_id' => $userId,
                    ]
                );
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Resets passwords
     *
     * @param $data
     * @return int
     */
    public function resetPassword($data) {
        return $this->db->update('users', $data, ['id' => $data['id']]);
    }

    /**
     * Remove record.
     *
     * @param array $user User
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     */
    public function delete($user)
    {
        $this->db->beginTransaction();
        try {
            $userId = $user['id'];
            unset($user['id']);
            $this->RemoveUserData($userId);
            $sets = $this->findLinkedSets($userId);
           foreach ($sets as $set) {
               $this->setRepository->delete($set);
           }
            $this->db->delete('users', ['id' => $userId]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Edits user's data
     *
     * @param $user
     * @return int
     */
    public function editUserData($user)
    {
        if (isset($user['id']) && ($user['id'] != '') && ctype_digit((string)$user['id'])) {
            $userId = $user['users_id'];
            unset($user['id']);

            return $this->db->update('users_data', $user, array('users_id' => $userId));
        }
    }

    /**
     * Loads user by login.
     *
     * @param string $login User login
     * @throws UsernameNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function loadUserByLogin($login)
    {
        try {
            $user = $this->getUserByLogin($login);

            if (!$user || !count($user)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            $roles = $this->getUserRoles($user['id']);

            if (!$roles || !count($roles)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            return [
                'login' => $user['login'],
                'password' => $user['password'],
                'role' => $roles,
            ];
        } catch (DBALException $exception) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        } catch (UsernameNotFoundException $exception) {
            throw $exception;
        }
    }

    /**
     * Gets user data by login.
     *
     * @param string $login User login
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserByLogin($login)
    {
        try {
            $queryBuilder = $this->queryAll()
                ->where('u.login = :login')
                ->setParameter(':login', $login, \PDO::PARAM_STR);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
    }

    /**
     * Gets user roles by User ID.
     *
     * @param integer $userId User ID
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserRoles($userId)
    {
        $roles = [];

        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('r.role')
                ->from('users', 'u')
                ->innerJoin('u', 'roles', 'r', 'u.roles_id = r.id')
                ->where('u.id = :id')
                ->setParameter(':id', $userId, \PDO::PARAM_INT);
            $result = $queryBuilder->execute()->fetchAll();

            if ($result) {
                $roles = array_column($result, 'role');
            }

            return $roles;
        } catch (DBALException $exception) {
            return $roles;
        }
    }

    /**
     * Find for uniqueness.
     *
     * @param string          $login Element login
     * @param int|string|null $id    Element id
     *
     * @return array Result
     */
    public function findForUniqueness($login, $id = null)
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select('login', 'id')
            ->from('users', 'u')
            ->where('u.login = :login')
            ->setParameter(':login', $login, \PDO::PARAM_STR);
        if ($id) {
            $queryBuilder->andWhere('u.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Finds if email is unique
     *
     * @param $email
     * @param null $userId
     * @return array
     */
    public function findForUniqueEmail($email, $userId = null)
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select('email')
            ->from('users_data', 'ud')
            ->where('ud.email = :email')
            ->setParameter(':email', $email, \PDO::PARAM_STR);
        if ($userId) {
            $queryBuilder->andWhere('ud.users_id <> :users_id')
                ->setParameter(':users_id', $userId, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * @param $userId
     * @return int
     */
    public function RemoveUserData($userId)
    {
        return $this->db->delete('users_data', ['users_id' => $userId]);
    }
    /**
     * Find linked sets.
     *
     * @param int $userId User Id
     *
     * @return array Result
     */

    public function findLinkedSets($userId)
    {
        $queryBuilder = $this->queryAll()
            ->where('users_id = :users_id')
            ->setParameter('users_id', $userId,\PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return $result;
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('*')
            ->from('users', 'u');
    }
}
