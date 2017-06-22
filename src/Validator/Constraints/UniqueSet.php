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
    public $message = '{{ set }} is not unique Set.';
    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $elementId = null;

    /**
     * Set repository.
     *
     * @var null|\Repository\SetRepository $repository
     */
    public $repository = null;

}