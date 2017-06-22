<?php
/**
 * User Repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;

/**
 * Class UserRepository
 * @package Repository
 */
class UserRepository
{
    /**
     * Doctrine DBAL connection.
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * UserRepository constructor.
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch all records;
     * @return array
     */
    public function findAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select('id', 'login')
            ->from('users');

        return $queryBuilder->execute()->fetchAll();
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
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select('id', 'login')
            ->from('users')
            ->where('id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Save record.
     *
     * @param array $user User
     *
     * @return boolean Result
     */
    public function save($user)
    {
        // TODO: wiążemy z user_data
        if (isset($user['id']) && ctype_digit((string) $user['id'])) {
            // update record
            $id = $user['id'];
            unset($user['id']);

            return $this->db->update('users', $user, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('users', $user);
        }
    }

    /**
     * Remove record.
     *
     * @param array $user user
     *
     * @return boolean Result
     */
    public function delete($user)
    {
        // TODO: usuwamy też sety
        return $this->db->delete('users', ['id' => $user['id']]);
    }
}
