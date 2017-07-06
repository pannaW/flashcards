<?php

/**
 * Tag Type
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;

/**
 * Class TagType
 * @package Form
 */
class TagType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.name',
                'required' => true,
                'attr' => [
                'max_length' => 45,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['tag-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['tag-default'],
                            'min' => 3,
                            'max' => 128,
                        ]
                    ),
                    new Assert\Type(
                        [
                            'type' => 'string',

                        ]
                    ),
                    new CustomAssert\UniqueTag(
                        [
                            'groups' => ['tag-default'],
                            'repository' => isset($options['tag_repository']) ? $options['tag_repository'] : null,
                            'elementId' => isset($options['data']['id']) ? $options['data']['id'] : null,
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'tag-default',
                'tag_repository' => null,
            ]
        );
    }
}
