<?php

/**
 * Flashcard Type
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
 * Class Flashcard Type
 * @package Form
 */
class FlashcardType extends AbstractType
{
    /**
     *{@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'word',
                TextType::class,
                [
                    'label' => 'label.word',
                    'required' => true,
                    'attr' => [
                        'max_length' => 45,
                    ],
                    'constraints' => [
                        new Assert\NotBlank(
                            ['groups' => ['flashcard-default']]
                        ),
                        new Assert\Length(
                            [
                                'groups' => ['flashcard-default'],
                                'min' => 3,
                                'max' => 45,
                            ]
                        ),
                        new Assert\Type(
                            [
                                'type' => 'string',

                            ]
                        ),
                        new CustomAssert\UniqueFlashcard(
                            [
                                'groups' => ['flashcard-default'],
                                'repository' => isset($options['flashcard_repository']) ? $options['flashcard_repository'] : null,
                                'elementId' => isset($options['data']['id']) ? $options['data']['id'] : null,
                                'userId' => (isset($options['userId']) ? $options['userId'] : null )
                            ]
                        ),
                    ],

                ]
            )
            ->add(
                'definition',
                TextType::class,
                [
                    'label' => 'label.definition',
                    'required' => true,
                    'attr' => [
                        'max_length' => 250,
                    ],
                    'constraints' => [
                        new Assert\NotBlank(
                            ['groups' => ['flashcard_-default']]
                        ),
                        new Assert\Length(
                            [
                                'groups' => ['flashcard_-default'],
                                'min' => 3,
                                'max' => 250,
                            ]
                        ),
                        new Assert\Type(
                            [
                                'type' => 'string',
                            ]
                        ),
                        new Assert\Regex(
                            [
                                'pattern' => '/\d/',
                                'match' => false,
                                'message' => 'Your name cannot contain a number',
                            ]
                        ),
                    ],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'flashcard_type';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'flashcard-default',
                'flashcard_repository' => null,
                'userId' => null,
            ]
        );
    }
}
