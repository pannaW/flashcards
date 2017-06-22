<?php
/**
 * Sets data transformer.
 */
namespace Form;

use Repository\SetRepository;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class SetsDataTransformer
 *
 * @package Form
 */
class SetsDataTransformer implements DataTransformerInterface
{
    /**
     * Set repository.
     *
     * @var SetRepository|null $setRepository
     */
    protected $setRepository = null;

    /**
     * SetsDataTransformer constructor.
     *
     * @param SetRepository $setRepository Set repository
     */
    public function __construct(SetRepository $setRepository)
    {
        $this->setRepository = $setRepository;
    }


    /**
     * Transform data from digits to boolean
     *
     * @param mixed $public
     *
     * @return boolean $public
     */
    public function transform($public)
    {
        return $public = (boolean) $public;
    }

    /**
     * Transform data from boolean to digits
     *
     * @param mixed $public
     * @return int
     */
    public function reverseTransform($public)
    {
        return $public = (int) $public;
    }
}
