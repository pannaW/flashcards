<?php

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordType extends AbstractType
{
    /**
     *{@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
            $builder->add(
            'new_password',
            RepeatedType::class,
            [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'label.new-password',
                    'required' => true,
                    'constraints' => [
                        new Assert\NotBlank(
                            ['groups' => ['reset-password-default']]
                        ),
                        new Assert\Length(
                            [
                                'groups' => ['reset-password-default'],
                                'min' => 7,
                                'max' => 125,
                            ]
                        ),
                    ],
                ],
                'second_options' => [
                    'label' => 'label.repeat-new-password',
                    'required' => true,
                ],
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'resetPassword_type';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'reset-password-default',
            ]
        );
    }
}