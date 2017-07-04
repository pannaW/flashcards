<?php
/**
 * Flashcard Repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class FlashcardRepository
 * @package Repository
 */
class FlashcardRepository
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
     * FlashcardRepository constructor.
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
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * @param $id
     * @param $userId
     * @return bool
     */
    public function checkOwnership($id, $userId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from('sets', 's')
            ->innerJoin('s','flashcards','f', 's.id = f.sets_id')
            ->where('f.id = :id')
            ->andWhere('s.users_id = :users_id')
            ->setParameter(':id', $id, \PDO::PARAM_INT)
            ->setParameter(':users_id', $userId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();

        return !empty($result) ? true : false ;
    }

    /**
     * Save record.
     *
     * @param array $flashcard Flashcard
     *
     * @return boolean Result
     */
    public function save($flashcard)
    {
        if (isset($flashcard['id']) && ctype_digit((string) $flashcard['id'])) {
            // update record
            $id = $flashcard['id'];
            unset($flashcard['id']);

            return $this->db->update('flashcards', $flashcard, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('flashcards', $flashcard);
        }
    }

    /**
     * Remove record.
     *
     * @param array $flashcard flashcard
     *
     * @return boolean Result
     */
    public function delete($flashcard)
    {
        return $this->db->delete('flashcards', ['id' => $flashcard['id']]);
    }

    /**
     * Find for uniqueness.
     *
     * @param string          $word Element word
     * @param int|string|null $id   Element id
     *
     * @return array Result
     */
    public function findForUniqueness($word, $id = null, $userId)
    {
        $queryBuilder = $this->db->createQueryBuilder()
            ->select('*')
            ->from('sets', 's')
            ->innerJoin('s','flashcards','f', 's.id = f.sets_id')
            ->where('f.word = :word')
            ->andWhere('s.users_id = :users_id')
            ->setParameter(':word', $word, \PDO::PARAM_INT)
            ->setParameter(':users_id', $userId, \PDO::PARAM_INT);
        if ($id) {
            $queryBuilder->andWhere('s.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
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
            ->from('flashcards', 't');
    }
}
