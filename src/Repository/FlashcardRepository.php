<?php
/**
 * Flashcard Repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;

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
    public function findForUniqueness($word, $id = null)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('t.word = :word')
            ->setParameter(':word', $word, \PDO::PARAM_STR);
        if ($id) {
            $queryBuilder->andWhere('t.id <> :id')
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

        return $queryBuilder->select('t.id', 't.word', 't.definition')
            ->from('flashcards', 't');
    }
}
