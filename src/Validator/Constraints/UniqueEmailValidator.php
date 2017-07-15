<?php
/**
 * Unique Email validator.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueEmailValidator.
 *
 * @package Validator\Constraints
 */
class UniqueEmailValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint->repository) {
            return;
        }

        $result = $constraint->repository->findForUniqueEmail(
            $value,
            $constraint->userId
        );

        if ($result && count($result)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ email }}', $value)
                ->addViolation();
        }
    }
}
