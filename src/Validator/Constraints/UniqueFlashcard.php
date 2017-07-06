<?php
/**
 * Unique Flashcard constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueFlashcard.
 *
 * @package Validator\Constraints
 */
class UniqueFlashcard extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = 'not-unique-flashcard';
    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $elementId = null;


    /**
     * User id
     *
     * @var int|string|null $userId
     */
    public $userId = null;

    /**
     * Flashcard repository.
     *
     * @var null|\Repository\FlashcardRepository $repository
     */
    public $repository = null;

}