<?php
/**
 * RegisterType
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;

/**
 * Class RegisterType
 * @package Form
 */
class RegisterType extends AbstractType
{
    /**
     *{@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'login',
            TextType::class,
            [
                'label' => 'label.login',
                'required' => true,
                'attr' => [
                    'max_length' => 45,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['register-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['register-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                    new CustomAssert\UniqueUser(
                        [
                            'groups' => ['register-default'],
                            'repository' => isset($options['user_repository']) ? $options['user_repository'] : null,
                            'elementId' => isset($options['data']['id']) ? $options['data']['id'] : null,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'password',
            RepeatedType::class,
            [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'label.password',
                    'required' => true,
                    'constraints' => [
                        new Assert\NotBlank(
                            ['groups' => ['register-default']]
                        ),
                        new Assert\Length(
                            [
                                'groups' => ['register-default'],
                                'min' => 7,
                                'max' => 125,
                            ]
                        ),
                    ],
                ],
                'second_options' => [
                    'label' => 'label.repeat-password',
                    'required' => true,
                ],
            ]
        );
        $builder->add(
            'roles_id',
            HiddenType::class,
            [
                'data' => '2',
            ]
        );
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.name',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['register-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['register-default'],
                            'min' => 3,
                            'max' => 45,
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
        $builder->add(
            'surname',
            TextType::class,
            [
                'label' => 'label.surname',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['user-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['user-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                    new Assert\Regex(
                        [
                            'pattern' => '/\d/',
                            'match' => false,
                            'message' => 'Your surname cannot contain a number',
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'email',
            EmailType::class,
            [
                'label' => 'label.email',
                'required' => true,
                    //                'constraints' => [
                    //                    new CustomAssert\UniqueEmail(
                    //                        //TODO: implement validators, remember about user_data_repository
                    //                        [
                    //                            'groups' => ['register-default'],
                    //                            'repository' => isset($options['user_repository']) ? $options['user_repository'] : null,
                    //                            'elementId' => isset($options['data']['id']) ? $options['data']['id'] : null,
                    //                        ]
                    //                    ),
                    //                ],
            ]
        );
        $builder->add(
            'agreement',
            CheckboxType::class,
            array(
                'label'    => 'label.agreement',
                'required' => true,
                'mapped' => false,
            )
        );
    }
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'user_type';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'user-default',
                'user_repository' => null,
            ]
        );
    }
}
