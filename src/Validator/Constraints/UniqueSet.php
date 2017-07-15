<?php
/**
 * Unique Set constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueSet.
 *
 * @package Validator\Constraints
 */
class UniqueSet extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = 'not-unique-set';
    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $elementId = null;

    /**
     * User id.
     *
     * @var int|string|null $UserId
     */
    public $userId = null;

    /**
     * Set repository.
     *
     * @var null|\Repository\SetRepository $repository
     */
    public $repository = null;
}
