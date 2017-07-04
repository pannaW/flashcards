<?php
/**
 * Tag Repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

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
     * Number of items on one page when items paginated
     */
    const NUM_ITEMS = 5;


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
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT t.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
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
            ->setParameter(':ids', $ids, Connection::PARAM_INT_ARRAY);

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
     * Look for tags connected with sets
     *
     * @param null $id
     * @return array|bool
     */
    public function findLinkedTags($id = null)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('st.sets_id')
            ->from('set_has_tag', 'st')
            ->where('st.tags_id = :tag_id')
            ->setParameter(':tag_id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return !empty($result) ? $result : false ;
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
