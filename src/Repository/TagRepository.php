<?php
/**
 * Tag Repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;

/**
 * Class TagRepository
 * @package Repository
 */
class TagRepository
{
    /**
     * Doctrine DBAL connection.
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * TagRepository constructor.
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
        $queryBuilder = $this->queryAll();

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find one record by name.
     *
     * @param string $name Name
     *
     * @return array|mixed Result
     */
    public function findOneByName($name)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.name = :name')
            ->setParameter(':name', $name, \PDO::PARAM_STR);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Find tags by Ids.
     *
     * @param array $ids Tags Ids.
     *
     * @return array
     */
    public function findById($ids)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.id IN (:ids)')
            ->setParameter(':ids', $ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

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
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }


    /**
     * Save record.
     *
     * @param array $tag Tag
     *
     * @return mixed Result
     */
    public function save($tag)
    {
        if (isset($tag['id']) && ctype_digit((string) $tag['id'])) {
            // update record
            $id = $tag['id'];
            unset($tag['id']);

            return $this->db->update('tags', $tag, ['id' => $id]);
        } else {
            // add new record
            $this->db->insert('tags', $tag);
            $tag['id'] = $this->db->lastInsertId();

            return $tag;
        }
    }

    /**
     * Remove record.
     *
     * @param array $tag tag
     *
     * @return boolean Result
     */
    public function delete($tag)
    {
        return $this->db->delete('tags', ['id' => $tag['id']]);
    }

    /**
     * Find for uniqueness.
     *
     * @param string          $name Element name
     * @param int|string|null $id   Element id
     *
     * @return array Result
     */
    public function findForUniqueness($name, $id = null)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.name = :name')
            ->setParameter(':name', $name, \PDO::PARAM_STR);
        if ($id) {
            $queryBuilder->andWhere('t.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find out if tag is linked.
     *
     * @param null $id
     * @return bool
     */
    public function findIfTagLinked($id = null)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('st.tags_id')
            ->from('set_has_tag', 'st')
            ->where('st.tags_id = :tag_id')
            ->setParameter(':tag_id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return !empty($result) ;
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('t.id', 't.name')
            ->from('tags', 't');
    }
}
