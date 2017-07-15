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
     * Set id
     *
     * @var int|string|null $setId
     */
    public $setId = null;

    /**
     * Flashcard repository.
     *
     * @var null|\Repository\FlashcardRepository $repository
     */
    public $repository = null;
}
