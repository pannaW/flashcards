<?php
/**
 * Set Repository
 */

namespace Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class SetRepository
 * @package Repository
 */
class SetRepository
{
    /**
     * Doctrine DBAL connection.
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * Tag repository.
     *
     * @var null|\Repository\TagRepository $tagRepository
     */
    protected $tagRepository = null;

    /**
     * Flashcard repository.
     *
     * @var null|\Repository\FlashcardRepository $flashcardRepository
     */
    protected $flashcardRepository = null;

    /**
     *Number of items on one page when items paginated
     */
    const NUM_ITEMS = 3;

    /**
     * SetRepository constructor.
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->tagRepository = new TagRepository($db);
        $this->flashcardRepository = new FlashcardRepository($db);
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
            ->select('COUNT(DISTINCT s.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
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
        $queryBuilder->where('s.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        if ($result) {
            $result['tags'] = $this->findLinkedTags($result['id']);
        }

        return $result;
    }



    /**
     * Save record.
     *
     * @param array $set Set
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function save($set)
    {
        $this->db->beginTransaction();

        try {
            $currentDateTime = new \DateTime();
            $set['modified_at'] = $currentDateTime->format('Y-m-d H:i:s');
            $tagsIds = isset($set['tags']) ? array_column($set['tags'], 'id') : [];
            unset($set['tags']);

            if (isset($set['id']) && ctype_digit((string) $set['id'])) {
                // update record
                $setId = $set['id'];
                unset($set['id']);
                $this->removeLinkedTags($setId);
                $this->addLinkedTags($setId, $tagsIds);
                $this->db->update('sets', $set, ['id' => $setId]);
            } else {
                // add new record
                $set['created_at'] = $currentDateTime->format('Y-m-d H:i:s');

                $this->db->insert('sets', $set);
                $setId = $this->db->lastInsertId();
                $this->addLinkedTags($setId, $tagsIds);
            }
            $this->db->commit();
            return $setId;
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Remove record.
     *
     * @param array $set Set
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return boolean Result
     */
    public function delete($set)
    {
        $this->db->beginTransaction();

        try {
            $this->removeLinkedTags($set['id']);
            $this->deleteConnectedFlashcards($set['id']);
            $this->db->delete('sets', ['id' => $set['id']]);
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Find for uniqueness.
     *
     * @param string          $name Element name
     * @param int|string|null $id   Element id
     *
     * @return array Result
     */
    public function findForUniqueness($name, $id = null, $userId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('s.name = :name')
            ->andWhere('s.users_id = :users_id')
            ->setParameter(':name', $name, \PDO::PARAM_STR)
            ->setParameter(':users_id', $userId,\PDO::PARAM_INT);
        if ($id) {
            $queryBuilder->andWhere('s.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Find linked tags.
     *
     * @param int $setId Set Id
     *
     * @return array Result
     */
    public function findLinkedTags($setId)
    {
        $tagsIds = $this->findLinkedTagsIds($setId);

        return is_array($tagsIds)
            ? $this->tagRepository->findById($tagsIds)
            : [];
    }

    /**
     * @param $setId
     * @return array
     */
    public function findLinkedFlashcards($setId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('id, word, definition')
            ->from('flashcards', 'f')
            ->where('f.sets_id = :sets_id')
            ->setParameter(':sets_id', $setId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? $result : [];
    }

    /**
     * @param $userId
     * @return array
     */
    public function loadUserSets($userId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from('sets', 's')
            ->where('s.users_id = :users_id')
            ->setParameter(':users_id', $userId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? $result : [];
    }

    /**
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
     * Remove linked tags.
     *
     * @param int $setIds Set Ids
     *
     * @return boolean Result
     */
    public function removeLinkedTags($setId)
    {
        return $this->db->delete('set_has_tag', ['sets_id' => $setId]);
    }

    /**
     * Finds linked tags Ids.
     *
     * @param int $setId Set Id
     *
     * @return array Result
     */
    protected function findLinkedTagsIds($setId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('st.tags_id')
            ->from('set_has_tag', 'st')
            ->where('st.sets_id = :sets_id')
            ->setParameter(':sets_id', $setId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return isset($result) ? array_column($result, 'tags_id') : [];
    }


    /**
     * @param $setId
     * @return int
     */
    protected function deleteConnectedFlashcards($setId)
    {
        return $this->db->delete('flashcards', ['sets_id' => $setId]);
    }

    /**
     * Add linked tags.
     *
     * @param int $setId Set Id
     * @param array $tagsIds Tags Ids
     */
    protected function addLinkedTags($setId, $tagsIds)
    {
        if (!is_array($tagsIds)) {
            $tagsIds = [$tagsIds];
        }

        foreach ($tagsIds as $tagId) {
            $this->db->insert(
                'set_has_tag',
                [
                    'sets_id' => $setId,
                    'tags_id' => $tagId,
                ]
            );
        }
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select(
            's.id',
            's.name',
            's.users_id',
            's.public',
            's.created_at',
            's.modified_at'
        )->from('sets', 's');
    }
}
