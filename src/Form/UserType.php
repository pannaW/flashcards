<?php
/**
 * User form.
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UserType
 *
 * @package Form
 */
class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
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
                    new Assert\NotBlank(),
                    new Assert\Length(
                        [
                            'min' => 3,
                            'max' => 32,
                        ]
                    ),
                    new CustomAssert\UniqueUser(
                        [
                            'groups' => ['user-default'],
                            'repository' => isset($options['user_repository']) ? $options['user_repository'] : null,
                            'userId' => isset($options['userId']) ? $options['userId'] : null,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'roles_id',
            ChoiceType::class,
            [
                'label' => 'user.role',
                'required' => true,
                'attr' => [
                    'max_length' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['user-default']]
                    ),
                ],
                'choices' => [
                    'ROLE_ADMIN' => 1,
                    'ROLE_USER' => 2,
                ],
            ]
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
                'userId' => null,
            ]
        );
    }
}
