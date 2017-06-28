<?php
/**
 * Unique Email constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueEmail.
 *
 * @package Validator\Constraints
 */
class UniqueEmail extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = '{{ email }} is not unique Email.';
    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $userId = null;

    /**
     * User repository.
     *
     * @var null|\Repository\UserRepository $repository
     */
    public $repository = null;
}
